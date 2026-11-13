<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\ClassBooking;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentSubscription;
use App\Support\UnitPanelContext;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(Request $request): View
    {
        $unit = UnitPanelContext::unit($request);
        $units = UnitPanelContext::units();

        $weekday = (int) $request->query('weekday', now()->dayOfWeek);

        if ($weekday < 0 || $weekday > 6) {
            $weekday = now()->dayOfWeek;
        }

        $classes = SchoolClass::query()
            ->where('unit_id', $unit->id)
            ->where('weekday', $weekday)
            ->where('status', 'active')
            ->with(['modality:id,name', 'teacher:id,name'])
            ->orderBy('start_time')
            ->get();

        $nextDates = $classes->mapWithKeys(fn (SchoolClass $class) => [
            $class->id => $this->nextDateForWeekday($class->weekday),
        ]);

        $classBookings = ClassBooking::query()
            ->whereIn('class_id', $classes->pluck('id'))
            ->whereDate('booked_for', '>=', now()->toDateString())
            ->whereIn('status', [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED])
            ->get()
            ->groupBy(fn (ClassBooking $booking) => $booking->class_id.'-'.$booking->booked_for->format('Y-m-d'));

        $upcomingBookings = ClassBooking::query()
            ->where('unit_id', $unit->id)
            ->whereDate('booked_for', '>=', now()->toDateString())
            ->with(['student:id,name', 'schoolClass:id,name'])
            ->orderBy('booked_for')
            ->take(50)
            ->get();

        $students = Student::query()
            ->whereHas('units', fn ($builder) => $builder->where('unit_id', $unit->id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $classOptions = SchoolClass::query()
            ->where('unit_id', $unit->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'weekday', 'start_time']);

        return view('pages.unit.classes.index', compact(
            'unit',
            'units',
            'classes',
            'nextDates',
            'classBookings',
            'upcomingBookings',
            'students',
            'classOptions',
            'weekday'
        ));
    }

    public function storeBooking(Request $request): RedirectResponse
    {
        $unit = UnitPanelContext::unit($request);

        $validated = $request->validate([
            'class_id' => ['required', 'integer', 'exists:classes,id'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'type' => ['required', 'in:plan,single'],
        ]);

        $class = SchoolClass::query()
            ->where('unit_id', $unit->id)
            ->where('status', 'active')
            ->whereKey($validated['class_id'])
            ->firstOrFail();

        $student = Student::query()
            ->whereHas('units', fn ($builder) => $builder->where('unit_id', $unit->id))
            ->whereKey($validated['student_id'])
            ->firstOrFail();

        $bookedFor = $this->nextDateForWeekday($class->weekday);

        $occupied = ClassBooking::query()
            ->where('class_id', $class->id)
            ->whereDate('booked_for', $bookedFor->toDateString())
            ->whereIn('status', [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED])
            ->count();

        if ($occupied >= $class->vacancies) {
            return back()->withErrors(['booking' => 'Esta aula está sem vagas para a data selecionada.']);
        }

        $existing = ClassBooking::query()
            ->where('student_id', $student->id)
            ->where('class_id', $class->id)
            ->whereDate('booked_for', $bookedFor->toDateString())
            ->first();

        if ($existing && in_array($existing->status, [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED], true)) {
            return back()->withErrors(['booking' => 'Este aluno já possui uma reserva nesta aula e data.']);
        }

        if ($validated['type'] === 'plan' && ! $this->hasActivePlan($student)) {
            return back()->withErrors(['booking' => 'O aluno não possui plano ativo para reservar esta aula.']);
        }

        $data = [
            'network_id' => $unit->network_id,
            'unit_id' => $unit->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'payment_gateway_setting_id' => null,
            'status' => ClassBooking::STATUS_CONFIRMED,
            'type' => $validated['type'] === 'plan' ? ClassBooking::TYPE_PLAN : ClassBooking::TYPE_SINGLE,
            'booked_for' => $bookedFor->toDateString(),
            'amount' => $validated['type'] === 'plan' ? 0 : (float) ($class->drop_in_price ?? 0),
            'paid_at' => now(),
        ];

        if ($existing) {
            $existing->update($data);
        } else {
            ClassBooking::create($data);
        }

        return back()->with('success', 'Reserva registrada com sucesso.');
    }

    public function cancelBooking(Request $request, ClassBooking $booking): RedirectResponse
    {
        $unit = UnitPanelContext::unit($request);

        abort_unless((int) $booking->unit_id === (int) $unit->id, 404);

        $booking->update(['status' => ClassBooking::STATUS_CANCELED]);

        return back()->with('success', 'Reserva cancelada.');
    }

    public function confirmBooking(Request $request, ClassBooking $booking): RedirectResponse
    {
        $unit = UnitPanelContext::unit($request);

        abort_unless((int) $booking->unit_id === (int) $unit->id, 404);

        if ($booking->status !== ClassBooking::STATUS_PENDING) {
            return back()->withErrors(['booking' => 'Esta reserva não está pendente.']);
        }

        $booking->update([
            'status' => ClassBooking::STATUS_CONFIRMED,
            'paid_at' => now(),
            'gateway_payload' => array_merge($booking->gateway_payload ?? [], [
                'manual_confirmation' => true,
                'confirmed_by' => auth()->id(),
            ]),
        ]);

        return back()->with('success', 'Pagamento da reserva confirmado manualmente.');
    }

    private function hasActivePlan(Student $student): bool
    {
        return StudentSubscription::query()
            ->where('student_id', $student->id)
            ->active()
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
}
