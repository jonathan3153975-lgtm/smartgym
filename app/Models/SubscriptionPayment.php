<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_IN_PROCESS = 'in_process';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'student_subscription_id',
        'network_id',
        'unit_id',
        'payment_gateway_setting_id',
        'provider',
        'status',
        'amount',
        'external_reference',
        'preference_id',
        'gateway_payment_id',
        'init_point',
        'paid_at',
        'gateway_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'gateway_payload' => 'array',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(StudentSubscription::class, 'student_subscription_id');
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function gatewaySetting(): BelongsTo
    {
        return $this->belongsTo(PaymentGatewaySetting::class, 'payment_gateway_setting_id');
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_APPROVED => 'Aprovado',
            self::STATUS_IN_PROCESS => 'Em processamento',
            self::STATUS_REJECTED => 'Rejeitado',
            self::STATUS_CANCELLED => 'Cancelado',
            self::STATUS_REFUNDED => 'Estornado',
            self::STATUS_FAILED => 'Falhou',
            default => '—',
        };
    }
}
