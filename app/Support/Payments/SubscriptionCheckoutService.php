<?php

namespace App\Support\Payments;

use App\Models\MembershipPlan;
use App\Models\PaymentGatewaySetting;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentSubscription;
use App\Models\SubscriptionPayment;
use App\Models\Unit;
use App\Support\NetworkTheme;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SubscriptionCheckoutService
{
    public function __construct(private readonly MercadoPagoCredentials $credentials) {}

    /**
     * Create a pending subscription and its Mercado Pago preference.
     *
     * @return string The Mercado Pago checkout URL.
     */
    public function start(StudentAccount $account, MembershipPlan $plan, Unit $unit): string
    {
        $student = $account->student;

        if (! $student instanceof Student) {
            throw ValidationException::withMessages([
                'plan' => 'Não foi possível identificar o aluno para esta assinatura.',
            ]);
        }

        if ($student->activeSubscription()) {
            throw ValidationException::withMessages([
                'plan' => 'Você já possui um plano ativo.',
            ]);
        }

        $hasRecentPendingCheckout = $student->subscriptions()
            ->where('status', StudentSubscription::STATUS_PENDING)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->exists();

        if ($hasRecentPendingCheckout) {
            throw ValidationException::withMessages([
                'plan' => 'Você já iniciou uma assinatura. Conclua o pagamento ou aguarde 15 minutos para tentar novamente.',
            ]);
        }

        if ($plan->price === null || (float) $plan->price <= 0) {
            throw ValidationException::withMessages([
                'plan' => 'Este plano não está disponível para assinatura online.',
            ]);
        }

        $setting = $this->credentials->resolve($student->network, $unit);

        if (! $setting) {
            throw ValidationException::withMessages([
                'plan' => 'O pagamento online ainda não está configurado para esta unidade.',
            ]);
        }

        [$subscription, $payment] = DB::transaction(function () use ($account, $plan, $unit, $setting) {
            $network = $account->network;

            $subscription = StudentSubscription::create([
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'student_id' => $account->student_id,
                'membership_plan_id' => $plan->id,
                'payment_gateway_setting_id' => $setting->id,
                'status' => StudentSubscription::STATUS_PENDING,
                'gateway_provider' => PaymentGatewaySetting::PROVIDER_MERCADO_PAGO,
                'plan_name' => $plan->name,
                'price' => $plan->price,
                'period' => $plan->period,
                'duration_days' => $plan->duration_days ?: MembershipPlan::durationForPeriod($plan->period),
            ]);

            $payment = $subscription->payments()->create([
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'payment_gateway_setting_id' => $setting->id,
                'provider' => PaymentGatewaySetting::PROVIDER_MERCADO_PAGO,
                'status' => SubscriptionPayment::STATUS_PENDING,
                'amount' => $plan->price,
                'external_reference' => self::externalReference($subscription),
            ]);

            return [$subscription, $payment];
        });

        try {
            $preference = MercadoPagoClient::forSetting($setting)
                ->createPreference($this->preferencePayload($subscription, $plan, $account));

            $preferenceId = isset($preference['id']) ? (string) $preference['id'] : null;
            $initPoint = $this->initPoint($setting, $preference);

            if (! $preferenceId || ! $initPoint) {
                throw new MercadoPagoException('O Mercado Pago não retornou o link de pagamento.');
            }
        } catch (MercadoPagoException $exception) {
            $payment->update([
                'status' => SubscriptionPayment::STATUS_FAILED,
                'gateway_payload' => ['error' => $exception->getMessage()],
            ]);
            $subscription->update(['status' => StudentSubscription::STATUS_FAILED]);

            Log::warning('Falha ao criar a preferência do Mercado Pago.', [
                'subscription_id' => $subscription->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $subscription->update(['gateway_reference' => $preferenceId]);
        $payment->update([
            'preference_id' => $preferenceId,
            'init_point' => $initPoint,
            'gateway_payload' => $preference,
        ]);

        return $initPoint;
    }

    /**
     * Fetch the payment from Mercado Pago and activate the subscription when approved.
     */
    public function syncPayment(StudentSubscription $subscription, string $paymentId): SubscriptionPayment
    {
        $setting = $subscription->paymentGatewaySetting;

        if (! $setting?->isUsable()) {
            $setting = $this->credentials->resolve($subscription->network, $subscription->unit);
        }

        if (! $setting) {
            throw new MercadoPagoException('Não há credenciais do Mercado Pago para confirmar o pagamento.');
        }

        $payload = MercadoPagoClient::forSetting($setting)->getPayment($paymentId);

        $this->ensurePaymentBelongsToSubscription($subscription, $payload);

        return DB::transaction(function () use ($subscription, $paymentId, $setting, $payload) {
            $status = (string) ($payload['status'] ?? SubscriptionPayment::STATUS_PENDING);

            $payment = $subscription->payments()->firstOrNew([
                'gateway_payment_id' => $paymentId,
            ]);

            $payment->fill([
                'network_id' => $subscription->network_id,
                'unit_id' => $subscription->unit_id,
                'payment_gateway_setting_id' => $setting->id,
                'provider' => PaymentGatewaySetting::PROVIDER_MERCADO_PAGO,
                'status' => $status,
                'amount' => $payload['transaction_amount'] ?? $payment->amount ?? $subscription->price,
                'external_reference' => $payload['external_reference'] ?? $payment->external_reference ?? self::externalReference($subscription),
                'paid_at' => $status === SubscriptionPayment::STATUS_APPROVED ? now() : $payment->paid_at,
                'gateway_payload' => $payload,
            ]);

            $payment->save();

            if ($payment->status === SubscriptionPayment::STATUS_APPROVED) {
                $this->activate($subscription, $paymentId);
            }

            return $payment;
        });
    }

    public static function externalReference(StudentSubscription $subscription): string
    {
        return 'subscription:'.$subscription->id;
    }

    public static function subscriptionIdFromReference(?string $reference): ?int
    {
        if (! $reference || ! preg_match('/^subscription:(\d+)$/', $reference, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    /**
     * @return array<string, mixed>
     */
    private function preferencePayload(StudentSubscription $subscription, MembershipPlan $plan, StudentAccount $account): array
    {
        $redirectUrl = route('student.portal.checkout.status', [
            'network' => $subscription->network,
            'studentSubscription' => $subscription,
        ]);

        return [
            'items' => [
                array_filter([
                    'id' => (string) $plan->id,
                    'title' => $plan->name,
                    'description' => $plan->description ? Str::limit($plan->description, 250) : null,
                    'quantity' => 1,
                    'currency_id' => 'BRL',
                    'unit_price' => round((float) $plan->price, 2),
                ], fn ($value) => $value !== null && $value !== ''),
            ],
            'payer' => array_filter([
                'name' => $subscription->student?->name,
                'email' => $account->email,
            ]),
            'external_reference' => self::externalReference($subscription),
            'notification_url' => route('webhooks.mercado-pago', ['subscription' => $subscription->id]),
            'back_urls' => [
                'success' => $redirectUrl,
                'pending' => $redirectUrl,
                'failure' => $redirectUrl,
            ],
            'auto_return' => 'approved',
            'statement_descriptor' => mb_substr(NetworkTheme::companyName($subscription->network), 0, 22),
            'metadata' => [
                'network_id' => $subscription->network_id,
                'unit_id' => $subscription->unit_id,
                'student_id' => $subscription->student_id,
                'subscription_id' => $subscription->id,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $preference
     */
    private function initPoint(PaymentGatewaySetting $setting, array $preference): ?string
    {
        $production = $preference['init_point'] ?? null;
        $sandbox = $preference['sandbox_init_point'] ?? null;

        return $setting->environment === 'production'
            ? ($production ?? $sandbox)
            : ($sandbox ?? $production);
    }

    /**
     * Prevent an approved payment from another subscription from being replayed.
     *
     * @param  array<string, mixed>  $payload
     */
    private function ensurePaymentBelongsToSubscription(StudentSubscription $subscription, array $payload): void
    {
        $reference = $payload['external_reference'] ?? null;
        $metadataId = $payload['metadata']['subscription_id'] ?? null;

        $matchesReference = $reference === self::externalReference($subscription);
        $matchesMetadata = $metadataId !== null && (string) $metadataId === (string) $subscription->id;

        if (! $matchesReference && ! $matchesMetadata) {
            throw new MercadoPagoException('O pagamento informado não pertence a esta assinatura.');
        }
    }

    private function activate(StudentSubscription $subscription, string $paymentId): void
    {
        if ($subscription->isActive()) {
            $subscription->update(['gateway_payment_id' => $paymentId]);

            return;
        }

        $hasOtherActiveSubscription = $subscription->student->subscriptions()
            ->active()
            ->whereKeyNot($subscription->id)
            ->exists();

        if ($hasOtherActiveSubscription) {
            $subscription->update([
                'status' => StudentSubscription::STATUS_CANCELED,
                'canceled_at' => now(),
            ]);

            Log::warning('Pagamento aprovado para uma assinatura já coberta por outro plano ativo.', [
                'subscription_id' => $subscription->id,
                'student_id' => $subscription->student_id,
                'payment_id' => $paymentId,
            ]);

            return;
        }

        $days = max(1, (int) ($subscription->duration_days ?: 30));

        $subscription->update([
            'status' => StudentSubscription::STATUS_ACTIVE,
            'gateway_payment_id' => $paymentId,
            'starts_at' => now(),
            'ends_at' => now()->addDays($days),
            'paid_at' => now(),
        ]);
    }
}
