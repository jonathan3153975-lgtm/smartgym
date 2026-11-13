<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use App\Models\Network;
use App\Models\StudentAccount;
use App\Models\StudentSubscription;
use App\Models\Unit;
use App\Support\Payments\MercadoPagoException;
use App\Support\Payments\SubscriptionCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentCheckoutController extends Controller
{
    public function __construct(private readonly SubscriptionCheckoutService $checkout) {}

    public function store(Network $network, MembershipPlan $membershipPlan): RedirectResponse
    {
        $account = $this->account($network);
        $unit = $this->primaryUnit($account);

        abort_unless($unit instanceof Unit, 403, 'Nenhuma unidade vinculada ao aluno.');

        $eligible = MembershipPlan::query()
            ->eligibleForUnit($unit)
            ->whereKey($membershipPlan->id)
            ->exists();

        abort_unless($eligible, 404);

        try {
            $initPoint = $this->checkout->start($account, $membershipPlan, $unit);
        } catch (MercadoPagoException $exception) {
            Log::warning('Falha ao iniciar o checkout do Mercado Pago.', [
                'student_id' => $account->student_id,
                'plan_id' => $membershipPlan->id,
                'error' => $exception->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'plan' => 'Não foi possível iniciar o pagamento agora. Tente novamente em instantes.',
            ]);
        }

        return redirect()->away($initPoint);
    }

    public function status(Request $request, Network $network, StudentSubscription $studentSubscription): View
    {
        $account = $this->account($network);

        abort_unless(
            (int) $studentSubscription->student_id === (int) $account->student_id
            && (int) $studentSubscription->network_id === (int) $network->id,
            404
        );

        $paymentId = trim((string) ($request->query('payment_id') ?? $request->query('collection_id') ?? ''));

        if ($paymentId !== '') {
            try {
                $this->checkout->syncPayment($studentSubscription, $paymentId);
            } catch (MercadoPagoException $exception) {
                Log::warning('Não foi possível confirmar o pagamento do Mercado Pago.', [
                    'subscription_id' => $studentSubscription->id,
                    'payment_id' => $paymentId,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $studentSubscription->refresh();

        return view('pages.student-portal.checkout', [
            'network' => $network,
            'account' => $account,
            'subscription' => $studentSubscription,
            'paymentId' => $paymentId,
        ]);
    }

    private function account(Network $network): StudentAccount
    {
        /** @var StudentAccount|null $account */
        $account = Auth::guard('student')->user();

        abort_unless(
            $account instanceof StudentAccount && (int) $account->network_id === (int) $network->id,
            403
        );

        $account->loadMissing(['student.units.unit', 'network']);

        return $account;
    }

    private function primaryUnit(StudentAccount $account): ?Unit
    {
        $student = $account->student;

        if (! $student) {
            return null;
        }

        $studentUnit = $student->units->firstWhere('is_primary') ?? $student->units->first();

        return $studentUnit?->unit;
    }
}
