<?php

namespace App\Http\Controllers;

use App\Models\ClassBooking;
use App\Models\PaymentGatewaySetting;
use App\Models\StudentCharge;
use App\Models\StudentSubscription;
use App\Support\Payments\ClassBookingCheckoutService;
use App\Support\Payments\MercadoPagoCredentials;
use App\Support\Payments\MercadoPagoException;
use App\Support\Payments\StudentChargeService;
use App\Support\Payments\SubscriptionCheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function __construct(
        private readonly SubscriptionCheckoutService $checkout,
        private readonly ClassBookingCheckoutService $classCheckout,
        private readonly StudentChargeService $charges,
        private readonly MercadoPagoCredentials $credentials,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $type = (string) $request->input('type', '');
        $action = (string) $request->input('action', '');

        if ($type !== '' && $type !== 'payment' && ! str_starts_with($action, 'payment.')) {
            return response()->json(['ignored' => true]);
        }

        $paymentId = trim((string) ($request->input('data.id') ?? $request->input('payment_id', '')));

        if ($paymentId === '') {
            return response()->json(['ignored' => true]);
        }

        $chargeId = (int) $request->query('charge', 0);

        if (! $chargeId) {
            $chargeId = (int) StudentChargeService::chargeIdFromReference(
                $request->input('external_reference') ?? $request->input('data.external_reference')
            );
        }

        if ($chargeId) {
            return $this->handleCharge($request, $chargeId, $paymentId);
        }

        $classBookingId = (int) $request->query('class_booking', 0);

        if (! $classBookingId) {
            $classBookingId = (int) ClassBookingCheckoutService::bookingIdFromReference(
                $request->input('external_reference') ?? $request->input('data.external_reference')
            );
        }

        if ($classBookingId) {
            return $this->handleClassBooking($request, $classBookingId, $paymentId);
        }

        $subscription = $this->findSubscription($request);

        if (! $subscription) {
            return response()->json(['ignored' => true]);
        }

        $setting = $subscription->paymentGatewaySetting
            ?? $this->credentials->resolve($subscription->network, $subscription->unit);

        if (! $setting instanceof PaymentGatewaySetting) {
            Log::warning('Webhook do Mercado Pago sem credenciais configuradas.', [
                'subscription_id' => $subscription->id,
            ]);

            return response()->json(['error' => 'credentials_unavailable'], 422);
        }

        if (! $this->signatureIsValid($request, $setting, $paymentId)) {
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        try {
            $payment = $this->checkout->syncPayment($subscription, $paymentId);
        } catch (MercadoPagoException $exception) {
            Log::warning('Falha ao processar o webhook do Mercado Pago.', [
                'subscription_id' => $subscription->id,
                'payment_id' => $paymentId,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['error' => 'payment_sync_failed'], 500);
        }

        return response()->json([
            'received' => true,
            'status' => $payment->status,
        ]);
    }

    private function handleCharge(Request $request, int $chargeId, string $paymentId): JsonResponse
    {
        $charge = StudentCharge::find($chargeId);

        if (! $charge) {
            return response()->json(['ignored' => true]);
        }

        $setting = $charge->gatewaySetting
            ?? $this->credentials->resolve($charge->network, $charge->unit);

        if (! $setting instanceof PaymentGatewaySetting) {
            Log::warning('Webhook de cobrança sem credenciais configuradas.', [
                'charge_id' => $charge->id,
            ]);

            return response()->json(['error' => 'credentials_unavailable'], 422);
        }

        if (! $this->signatureIsValid($request, $setting, $paymentId)) {
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        try {
            $charge = $this->charges->syncPixPayment($charge, $paymentId);
        } catch (MercadoPagoException $exception) {
            Log::warning('Falha ao processar o webhook de cobrança.', [
                'charge_id' => $charge->id,
                'payment_id' => $paymentId,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['error' => 'payment_sync_failed'], 500);
        }

        return response()->json([
            'received' => true,
            'status' => $charge->status,
        ]);
    }

    private function handleClassBooking(Request $request, int $classBookingId, string $paymentId): JsonResponse
    {
        $booking = ClassBooking::find($classBookingId);

        if (! $booking) {
            return response()->json(['ignored' => true]);
        }

        $setting = $booking->gatewaySetting
            ?? $this->credentials->resolve($booking->network, $booking->unit);

        if (! $setting instanceof PaymentGatewaySetting) {
            Log::warning('Webhook de aula avulsa sem credenciais configuradas.', [
                'class_booking_id' => $booking->id,
            ]);

            return response()->json(['error' => 'credentials_unavailable'], 422);
        }

        if (! $this->signatureIsValid($request, $setting, $paymentId)) {
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        try {
            $booking = $this->classCheckout->syncPayment($booking, $paymentId);
        } catch (MercadoPagoException $exception) {
            Log::warning('Falha ao processar o webhook de aula avulsa.', [
                'class_booking_id' => $booking->id,
                'payment_id' => $paymentId,
                'error' => $exception->getMessage(),
            ]);

            return response()->json(['error' => 'payment_sync_failed'], 500);
        }

        return response()->json([
            'received' => true,
            'status' => $booking->status,
        ]);
    }

    private function findSubscription(Request $request): ?StudentSubscription
    {
        $subscriptionId = (int) $request->query('subscription', 0);

        if (! $subscriptionId) {
            $subscriptionId = (int) SubscriptionCheckoutService::subscriptionIdFromReference(
                $request->input('external_reference')
                    ?? $request->input('data.external_reference')
            );
        }

        if ($subscriptionId) {
            return StudentSubscription::find($subscriptionId);
        }

        $paymentId = trim((string) ($request->input('data.id') ?? $request->input('payment_id', '')));

        if ($paymentId === '') {
            return null;
        }

        return StudentSubscription::query()
            ->whereHas('payments', fn ($query) => $query->where('gateway_payment_id', $paymentId))
            ->first();
    }

    private function signatureIsValid(Request $request, PaymentGatewaySetting $setting, string $paymentId): bool
    {
        $secret = $setting->webhook_secret;

        if (blank($secret)) {
            return true;
        }

        $signature = (string) $request->header('x-signature', '');
        $requestId = (string) $request->header('x-request-id', '');

        if ($signature === '' || ! preg_match('/ts=([^,]+)/', $signature, $timestamp)) {
            return false;
        }

        if (! preg_match('/v1=([^,]+)/', $signature, $hash)) {
            return false;
        }

        $manifest = 'id:'.rawurldecode($paymentId)
            .';request-id:'.$requestId
            .';ts:'.$timestamp[1].';';

        return hash_equals(hash_hmac('sha256', $manifest, $secret), $hash[1]);
    }
}
