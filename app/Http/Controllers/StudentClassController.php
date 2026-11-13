<?php

namespace App\Http\Controllers;

use App\Models\ClassBooking;
use App\Models\Network;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentCheckin;
use App\Support\Payments\ClassBookingCheckoutService;
use App\Support\Payments\MercadoPagoException;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentClassController extends Controller
{
    public function __construct(private readonly ClassBookingCheckoutService $checkout) {}

    public function index(Request $request, Network $network): View
    {
        $account = $this->account($network);
        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $weekday = (int) $request->query('weekday', now()->dayOfWeek);

        if ($weekday < 0 || $weekday > 6) {
            $weekday = now()->dayOfWeek;
        }

        $classes = SchoolClass::query()
            ->with(['unit:id,name,street,number,neighborhood,city,state', 'modality:id,name', 'teacher:id,name'])
            ->where('network_id', $network->id)
            ->where('weekday', $weekday)
            ->where('status', 'active')
            ->whereHas('unit', fn ($query) => $query->where('status', 'active'))
            ->orderBy('start_time')
            ->get();

        $nextDates = $classes->mapWithKeys(fn (SchoolClass $class) => [
            $class->id => $this->nextDateForWeekday($class->weekday),
        ]);

        $bookings = $student->classBookings()
            ->whereIn('status', [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED])
            ->whereDate('booked_for', '>=', now()->toDateString())
            ->get()
            ->keyBy(fn (ClassBooking $booking) => $booking->class_id.'-'.$booking->booked_for->format('Y-m-d'));

        $activeSubscription = $student->activeSubscription();
        $trialUsed = $this->trialUsed($student);

        return view('pages.student-portal.classes.index', compact(
            'network',
            'account',
            'student',
            'classes',
            'nextDates',
            'bookings',
            'activeSubscription',
            'trialUsed',
            'weekday'
        ));
    }

    public function checkout(Network $network, SchoolClass $schoolClass): RedirectResponse
    {
        $account = $this->account($network);

        abort_unless((int) $schoolClass->network_id === (int) $network->id, 404);

        try {
            $initPoint = $this->checkout->start($account, $schoolClass);
        } catch (MercadoPagoException $exception) {
            Log::warning('Falha ao iniciar o pagamento de aula avulsa.', [
                'student_id' => $account->student_id,
                'class_id' => $schoolClass->id,
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'class' => 'Não foi possível iniciar o pagamento agora. Tente novamente em instantes.',
            ]);
        }

        return redirect()->away($initPoint);
    }

    public function reserve(Network $network, SchoolClass $schoolClass): RedirectResponse
    {
        $account = $this->account($network);
        $student = $account->student;

        abort_unless($student instanceof Student, 403);
        abort_unless((int) $schoolClass->network_id === (int) $network->id && $schoolClass->isActive(), 404);
        abort_unless($student->activeSubscription(), 403, 'Você precisa de um plano ativo para reservar esta aula.');

        $bookedFor = $this->nextDateForWeekday($schoolClass->weekday);

        $occupied = ClassBooking::query()
            ->where('class_id', $schoolClass->id)
            ->whereDate('booked_for', $bookedFor->toDateString())
            ->whereIn('status', [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED])
            ->count();

        if ($occupied >= $schoolClass->vacancies) {
            return redirect()
                ->route('student.portal.classes.index', $network)
                ->withErrors(['class' => 'Esta aula está sem vagas para a data selecionada.']);
        }

        $existing = ClassBooking::query()
            ->where('student_id', $student->id)
            ->where('class_id', $schoolClass->id)
            ->whereDate('booked_for', $bookedFor->toDateString())
            ->first();

        if ($existing && in_array($existing->status, [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED], true)) {
            return redirect()
                ->route('student.portal.classes.index', $network)
                ->withErrors(['class' => 'Você já possui uma reserva para esta aula nesta data.']);
        }

        $data = [
            'network_id' => $network->id,
            'unit_id' => $schoolClass->unit_id,
            'student_id' => $student->id,
            'class_id' => $schoolClass->id,
            'payment_gateway_setting_id' => null,
            'status' => ClassBooking::STATUS_CONFIRMED,
            'type' => ClassBooking::TYPE_PLAN,
            'booked_for' => $bookedFor->toDateString(),
            'amount' => 0,
            'paid_at' => now(),
        ];

        if ($existing) {
            $existing->update($data);
        } else {
            ClassBooking::create($data);
        }

        return redirect()
            ->route('student.portal.classes.index', $network)
            ->with('success', 'Aula reservada com sucesso!');
    }

    public function bookingStatus(Request $request, Network $network, ClassBooking $classBooking): View
    {
        $account = $this->account($network);

        abort_unless(
            (int) $classBooking->student_id === (int) $account->student_id
            && (int) $classBooking->network_id === (int) $network->id,
            404
        );

        $paymentId = trim((string) ($request->query('payment_id') ?? $request->query('collection_id') ?? ''));

        if ($paymentId !== '') {
            try {
                $this->checkout->syncPayment($classBooking, $paymentId);
            } catch (MercadoPagoException $exception) {
                Log::warning('Não foi possível confirmar o pagamento da aula avulsa.', [
                    'class_booking_id' => $classBooking->id,
                    'payment_id' => $paymentId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $classBooking->refresh();

        return view('pages.student-portal.classes.booking', [
            'network' => $network,
            'account' => $account,
            'booking' => $classBooking,
            'paymentId' => $paymentId,
        ]);
    }

    private function trialUsed(Student $student): bool
    {
        return $student->checkins()
            ->where('type', StudentCheckin::TYPE_TRIAL)
            ->exists();
    }

    private function nextDateForWeekday(int $weekday): Carbon
    {
        $date = now()->startOfDay();

        while ($date->dayOfWeek !== $weekday) {
            $date->addDay();
        }

        return $date;
    }

    private function account(Network $network): StudentAccount
    {
        /** @var StudentAccount|null $account */
        $account = Auth::guard('student')->user();

        abort_unless(
            $account instanceof StudentAccount && (int) $account->network_id === (int) $network->id,
            403
        );

        $account->loadMissing('student');

        return $account;
    }
}
