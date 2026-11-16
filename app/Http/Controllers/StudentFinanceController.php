<?php

namespace App\Http\Controllers;

use App\Models\ClassBooking;
use App\Models\Network;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentCharge;
use App\Support\Payments\MercadoPagoException;
use App\Support\Payments\StudentChargeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentFinanceController extends Controller
{
    public function __construct(private readonly StudentChargeService $charges) {}

    public function index(Network $network): View
    {
        $account = $this->account($network);
        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $student->load([
            'charges' => fn ($query) => $query->orderByDesc('created_at'),
            'subscriptions.payments',
            'classBookings.schoolClass',
        ]);

        $pendingCharges = $student->charges->where('status', StudentCharge::STATUS_PENDING);

        return view('pages.student-portal.finance.index', [
            'network' => $network,
            'account' => $account,
            'student' => $student,
            'pendingCharges' => $pendingCharges,
            'subscriptions' => $student->subscriptions,
            'classBookings' => $student->classBookings,
            'pendingTotal' => (float) $pendingCharges->sum('amount'),
        ]);
    }

    public function pix(Network $network, StudentCharge $charge): RedirectResponse
    {
        $account = $this->account($network);

        abort_unless(
            (int) $charge->student_id === (int) $account->student_id
            && (int) $charge->network_id === (int) $network->id,
            404
        );

        try {
            $this->charges->createPix($charge);
        } catch (MercadoPagoException $exception) {
            return back()->withErrors(['charge' => $exception->getMessage()]);
        }

        return back()->with('success', 'QR Code Pix gerado.');
    }

    public function checkin(Network $network, ClassBooking $classBooking): RedirectResponse
    {
        $account = $this->account($network);

        abort_unless(
            (int) $classBooking->student_id === (int) $account->student_id
            && (int) $classBooking->network_id === (int) $network->id,
            404
        );

        if (! $classBooking->isConfirmed()) {
            return back()->withErrors(['checkin' => 'Esta reserva ainda não está confirmada.']);
        }

        if ($classBooking->hasCheckedIn()) {
            return back()->withErrors(['checkin' => 'O check-in desta aula já foi realizado.']);
        }

        if (! $classBooking->isCheckinOpen()) {
            return back()->withErrors([
                'checkin' => 'Fora da janela de check-in. A janela é '.$classBooking->checkin_window_label.'.',
            ]);
        }

        $classBooking->update(['checked_in_at' => now()]);

        return back()->with('success', 'Check-in realizado. Bom treino!');
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
