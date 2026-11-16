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
        'student_sale_item_id',
        'payment_gateway_setting_id',
        'status',
        'gateway_provider',
        'gateway_reference',
        'gateway_payment_id',
        'plan_name',
        'price',
        'period',
        'duration_days',
        'plan_type',
        'modality_ids',
        'weekly_accesses',
        'daily_checkins',
        'checkin_advance_minutes',
        'auto_renew',
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
            'modality_ids' => 'array',
            'weekly_accesses' => 'integer',
            'daily_checkins' => 'integer',
            'checkin_advance_minutes' => 'integer',
            'auto_renew' => 'boolean',
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

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(StudentSaleItem::class, 'student_sale_item_id');
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
                $inner->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
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

        if ($this->starts_at && $this->starts_at->isFuture()) {
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

    public function getPlanTypeLabelAttribute(): string
    {
        return MembershipPlan::TYPE_LABELS[$this->plan_type] ?? MembershipPlan::TYPE_LABELS[MembershipPlan::TYPE_BASIC];
    }

    public function includesClasses(): bool
    {
        return in_array($this->plan_type, [MembershipPlan::TYPE_CLASSES, MembershipPlan::TYPE_MIXED], true);
    }

    /**
     * Whether the plan includes classes for the given modality.
     */
    public function includesModality(?int $modalityId): bool
    {
        if (! $this->includesClasses()) {
            return false;
        }

        if ($modalityId === null) {
            return true;
        }

        $modalityIds = collect($this->modality_ids ?? []);
        $plan = $this->membershipPlan;

        if ($plan?->all_modalities) {
            return true;
        }

        if ($modalityIds->isEmpty() && $plan) {
            $modalityIds = $plan->modalities()->pluck('modalities.id');
        }

        if ($modalityIds->isEmpty()) {
            // Legacy or removed plans have no snapshot; do not block access.
            return $plan === null;
        }

        return $modalityIds->contains(fn ($id) => (int) $id === $modalityId);
    }

    public function getWeeklyAccessLabelAttribute(): string
    {
        return $this->plan_type === MembershipPlan::TYPE_CLASSES ? 'dia(s) com aula/semana' : 'dia(s) de acesso/semana';
    }

    public function getDailyCheckinLabelAttribute(): string
    {
        return $this->plan_type === MembershipPlan::TYPE_CLASSES ? 'aula(s)/dia' : 'acesso(s)/dia';
    }

    public function getPriceLabelAttribute(): string
    {
        if ($this->price === null) {
            return 'Sob consulta';
        }

        return 'R$ '.number_format((float) $this->price, 2, ',', '.');
    }
}
