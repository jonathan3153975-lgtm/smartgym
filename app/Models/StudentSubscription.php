<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentSubscription extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'network_id',
        'unit_id',
        'student_id',
        'membership_plan_id',
        'payment_gateway_setting_id',
        'status',
        'gateway_provider',
        'gateway_reference',
        'gateway_payment_id',
        'plan_name',
        'price',
        'period',
        'duration_days',
        'starts_at',
        'ends_at',
        'paid_at',
        'canceled_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_days' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'paid_at' => 'datetime',
            'canceled_at' => 'datetime',
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

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function membershipPlan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class);
    }

    public function paymentGatewaySetting(): BelongsTo
    {
        return $this->belongsTo(PaymentGatewaySetting::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $inner) {
                $inner->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    public function isActive(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        return $this->ends_at === null || ! $this->ends_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Aguardando pagamento',
            self::STATUS_ACTIVE => 'Ativo',
            self::STATUS_FAILED => 'Falhou',
            self::STATUS_CANCELED => 'Cancelado',
            self::STATUS_EXPIRED => 'Expirado',
            default => '—',
        };
    }

    public function getPeriodLabelAttribute(): string
    {
        return MembershipPlan::PERIOD_LABELS[$this->period] ?? ($this->period ?? '—');
    }

    public function getPriceLabelAttribute(): string
    {
        if ($this->price === null) {
            return 'Sob consulta';
        }

        return 'R$ '.number_format((float) $this->price, 2, ',', '.');
    }
}
