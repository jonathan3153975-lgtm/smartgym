<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewaySetting extends Model
{
    public const PROVIDER_MERCADO_PAGO = 'mercado_pago';

    /**
     * @var array<string, string>
     */
    public const PROVIDERS = [
        self::PROVIDER_MERCADO_PAGO => 'Mercado Pago',
    ];

    /**
     * @var array<string, string>
     */
    public const ENVIRONMENTS = [
        'sandbox' => 'Sandbox (testes)',
        'production' => 'Produção',
    ];

    protected $fillable = [
        'network_id',
        'unit_id',
        'provider',
        'environment',
        'public_key',
        'access_token',
        'webhook_secret',
        'is_active',
    ];

    protected $hidden = [
        'access_token',
        'webhook_secret',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'access_token' => 'encrypted',
            'webhook_secret' => 'encrypted',
        ];
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function isUsable(): bool
    {
        return $this->is_active && filled($this->access_token);
    }

    public function getEnvironmentLabelAttribute(): string
    {
        return self::ENVIRONMENTS[$this->environment] ?? $this->environment;
    }

    public function getScopeLabelAttribute(): string
    {
        return $this->unit_id ? 'Unidade' : 'Rede';
    }
}
