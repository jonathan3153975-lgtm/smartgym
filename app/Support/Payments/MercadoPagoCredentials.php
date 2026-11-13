<?php

namespace App\Support\Payments;

use App\Models\Network;
use App\Models\PaymentGatewaySetting;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class MercadoPagoCredentials
{
    public function forNetwork(Network $network): ?PaymentGatewaySetting
    {
        return $this->baseQuery($network->id)->whereNull('unit_id')->first();
    }

    public function forUnit(Network $network, Unit $unit): ?PaymentGatewaySetting
    {
        return $this->baseQuery($network->id)->where('unit_id', $unit->id)->first();
    }

    /**
     * Unit credentials take precedence; when the unit has none, the network
     * credentials are used.
     */
    public function resolve(?Network $network, ?Unit $unit): ?PaymentGatewaySetting
    {
        if (! $network) {
            return null;
        }

        if ($unit) {
            $unitSetting = $this->forUnit($network, $unit);

            if ($unitSetting?->isUsable()) {
                return $unitSetting;
            }
        }

        $networkSetting = $this->forNetwork($network);

        return $networkSetting?->isUsable() ? $networkSetting : null;
    }

    /**
     * @return array{unit_configured: bool, network_configured: bool, source: ?string, environment: ?string}
     */
    public function summary(Network $network, ?Unit $unit): array
    {
        $resolved = $this->resolve($network, $unit);

        return [
            'unit_configured' => $unit ? (bool) $this->forUnit($network, $unit)?->isUsable() : false,
            'network_configured' => (bool) $this->forNetwork($network)?->isUsable(),
            'source' => $resolved ? ($resolved->unit_id ? 'unit' : 'network') : null,
            'environment' => $resolved?->environment,
        ];
    }

    /**
     * Persist the Mercado Pago credentials of a network or unit.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(Network $network, ?Unit $unit, array $data): ?PaymentGatewaySetting
    {
        if (! $this->hasPaymentFields($data)) {
            return $this->current($network, $unit);
        }

        $existing = $this->current($network, $unit);

        if (! empty($data['mercado_pago_clear'])) {
            $existing?->delete();

            return null;
        }

        $attributes = [];

        if (array_key_exists('mercado_pago_active', $data)) {
            $attributes['is_active'] = filter_var($data['mercado_pago_active'], FILTER_VALIDATE_BOOL);
        }

        if (! empty($data['mercado_pago_environment'])) {
            $attributes['environment'] = $data['mercado_pago_environment'];
        }

        if (! empty($data['mercado_pago_public_key'])) {
            $attributes['public_key'] = trim((string) $data['mercado_pago_public_key']);
        }

        if (! empty($data['mercado_pago_access_token'])) {
            $attributes['access_token'] = trim((string) $data['mercado_pago_access_token']);
        }

        if (! empty($data['mercado_pago_webhook_secret'])) {
            $attributes['webhook_secret'] = trim((string) $data['mercado_pago_webhook_secret']);
        }

        $hasToken = filled($attributes['access_token'] ?? null) || filled($existing?->access_token);
        $isActive = $attributes['is_active'] ?? $existing?->is_active ?? false;

        if ($isActive && ! $hasToken && $existing) {
            throw ValidationException::withMessages([
                'mercado_pago_access_token' => 'Informe o Access Token para ativar o Mercado Pago.',
            ]);
        }

        if (! $hasToken && ! $existing) {
            return null;
        }

        if ($existing) {
            $existing->update($attributes);

            return $existing->fresh();
        }

        return PaymentGatewaySetting::create(array_merge([
            'network_id' => $network->id,
            'unit_id' => $unit?->id,
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADO_PAGO,
            'environment' => 'sandbox',
            'is_active' => true,
        ], $attributes));
    }

    private function current(Network $network, ?Unit $unit): ?PaymentGatewaySetting
    {
        return $unit ? $this->forUnit($network, $unit) : $this->forNetwork($network);
    }

    private function baseQuery(int $networkId): Builder
    {
        return PaymentGatewaySetting::query()
            ->where('network_id', $networkId)
            ->where('provider', PaymentGatewaySetting::PROVIDER_MERCADO_PAGO);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function hasPaymentFields(array $data): bool
    {
        foreach ([
            'mercado_pago_active',
            'mercado_pago_environment',
            'mercado_pago_public_key',
            'mercado_pago_access_token',
            'mercado_pago_webhook_secret',
            'mercado_pago_clear',
        ] as $key) {
            if (array_key_exists($key, $data)) {
                return true;
            }
        }

        return false;
    }
}
