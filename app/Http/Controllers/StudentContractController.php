<?php

namespace App\Http\Controllers;

use App\Mail\StudentChargeMail;
use App\Models\ClassBooking;
use App\Models\Student;
use App\Models\StudentCharge;
use App\Models\Unit;
use App\Support\Payments\MercadoPagoException;
use App\Support\Payments\StudentChargeService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class StudentContractController extends Controller
{
    public function __construct(private readonly StudentChargeService $charges) {}

    public function index(Student $student): View
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessStudent($student), 404);

        $student->load([
            'subscriptions.payments',
            'classBookings.schoolClass',
            'charges',
            'sales.items.plan',
            'sales.items.schoolClass',
            'sales.items.slots.schedule.schoolClass',
            'sales.items.charge',
        ]);

        $unit = $this->primaryUnit($student);

        $pendingCharges = $student->charges->where('status', StudentCharge::STATUS_PENDING);
        $pendingTotal = (float) $pendingCharges->sum('amount');

        $subscriptionPayments = $student->subscriptions
            ->flatMap->payments
            ->where('status', 'approved');
        $paidSubscriptions = (float) $subscriptionPayments->sum('amount');
        $paidClasses = (float) $student->classBookings
            ->where('status', ClassBooking::STATUS_CONFIRMED)
            ->where('type', ClassBooking::TYPE_SINGLE)
            ->sum('amount');
        $paidOtherCharges = (float) $student->charges
            ->where('status', StudentCharge::STATUS_PAID)
            ->whereNull('reference_type')
            ->sum('amount');

        $metrics = [
            'pending_total' => $pendingTotal,
            'paid_total' => $paidSubscriptions + $paidClasses + $paidOtherCharges,
            'pending_count' => $pendingCharges->count(),
            'contracts' => $student->subscriptions->count() + $student->classBookings->count(),
        ];

        $sales = $student->sales;

        return view('pages.students.contracts', compact(
            'student',
            'unit',
            'pendingCharges',
            'metrics',
            'sales'
        ));
    }

    public function pay(Request $request, Student $student, StudentCharge $charge): RedirectResponse
    {
        $this->ensureCharge($student, $charge);

        $this->charges->markPaid($charge, 'manual', auth()->id());

        return back()->with('success', 'Pagamento registrado e contrato ativado.');
    }

    public function pix(Request $request, Student $student, StudentCharge $charge): RedirectResponse
    {
        $this->ensureCharge($student, $charge);

        try {
            $this->charges->createPix($charge);
        } catch (MercadoPagoException $exception) {
            return back()->withErrors(['charge' => $exception->getMessage()]);
        }

        return back()->with('success', 'QR Code Pix gerado.');
    }

    public function sendEmail(Request $request, Student $student, StudentCharge $charge): RedirectResponse
    {
        $this->ensureCharge($student, $charge);

        if (! $student->email) {
            return back()->withErrors(['charge' => 'O aluno não possui e-mail cadastrado.']);
        }

        $portalUrl = route('student.portal.finance.index', $student->network);

        Mail::to($student->email)->send(new StudentChargeMail($charge, $student, $portalUrl));

        $charge->update([
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Cobrança enviada por e-mail.');
    }

    public function cancel(Request $request, Student $student, StudentCharge $charge): RedirectResponse
    {
        $this->ensureCharge($student, $charge);

        $this->charges->cancel($charge, auth()->id());

        return back()->with('success', 'Cobrança cancelada.');
    }

    private function ensureCharge(Student $student, StudentCharge $charge): void
    {
        $tenant = $this->tenant();

        abort_unless($tenant->canAccessStudent($student), 404);
        abort_unless(
            (int) $charge->student_id === (int) $student->id
            && (int) $charge->network_id === (int) $student->network_id,
            404
        );
    }

    private function primaryUnit(Student $student): ?Unit
    {
        $studentUnit = $student->units()->where('is_primary', true)->first()
            ?? $student->units()->first();

        return $studentUnit?->unit;
    }

    private function tenant(): TenantContext
    {
        $tenant = TenantContext::current();

        abort_if($tenant->isPlatform(), 403, 'Selecione uma rede para gerenciar contratos.');
        abort_unless($tenant->networkId(), 403, 'Selecione uma rede para gerenciar contratos.');

        return $tenant;
    }
}
