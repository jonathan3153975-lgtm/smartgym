<?php

namespace App\Support\Payments;

use App\Models\PaymentGatewaySetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class MercadoPagoClient
{
    private const BASE_URL = 'https://api.mercadopago.com';

    public function __construct(private readonly string $accessToken) {}

    public static function forSetting(PaymentGatewaySetting $setting): self
    {
        if (blank($setting->access_token)) {
            throw new MercadoPagoException('As credenciais do Mercado Pago não estão configuradas.');
        }

        return new self($setting->access_token);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function createPreference(array $payload): array
    {
        return $this->post('/checkout/preferences', $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayment(string $paymentId): array
    {
        return $this->get('/v1/payments/'.rawurlencode($paymentId));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function post(string $path, array $payload): array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->acceptJson()
                ->asJson()
                ->timeout(20)
                ->post(self::BASE_URL.$path, $payload);
        } catch (ConnectionException $exception) {
            throw new MercadoPagoException('Não foi possível conectar ao Mercado Pago.', previous: $exception);
        }

        return $this->decode($response->status(), $response->json(), $response->body());
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $path): array
    {
        try {
            $response = Http::withToken($this->accessToken)
                ->acceptJson()
                ->timeout(20)
                ->get(self::BASE_URL.$path);
        } catch (ConnectionException $exception) {
            throw new MercadoPagoException('Não foi possível conectar ao Mercado Pago.', previous: $exception);
        }

        return $this->decode($response->status(), $response->json(), $response->body());
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>
     */
    private function decode(int $status, ?array $payload, string $body): array
    {
        if ($status >= 400) {
            $message = $payload['message'] ?? $payload['error'] ?? null;

            throw new MercadoPagoException(
                $message
                    ? 'Mercado Pago recusou a requisição: '.$message
                    : 'Mercado Pago retornou o status HTTP '.$status.'.'
            );
        }

        return $payload ?? ['raw' => $body];
    }
}
