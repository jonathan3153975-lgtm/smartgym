<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class StudentSaleItem extends Model
{
    public const TYPE_PLAN = 'plan';

    public const TYPE_DAY_PASS = 'day_pass';

    public const TYPE_CLASS = 'class';

    public const TYPE_OTHER = 'other';

    protected $fillable = [
        'student_sale_id',
        'type',
        'membership_plan_id',
        'class_id',
        'class_schedule_id',
        'unit_id',
        'description',
        'amount',
        'launched_at',
        'starts_at',
        'ends_at',
        'weekly_accesses',
        'daily_checkins',
        'checkin_advance_minutes',
        'auto_renew',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'launched_at' => 'date',
            'starts_at' => 'date',
            'ends_at' => 'date',
            'weekly_accesses' => 'integer',
            'daily_checkins' => 'integer',
            'checkin_advance_minutes' => 'integer',
            'auto_renew' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(StudentSale::class, 'student_sale_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(StudentSaleItemSlot::class)->orderBy('weekday')->orderBy('start_time');
    }

    public function charge(): HasOne
    {
        return $this->hasOne(StudentCharge::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(StudentSubscription::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PLAN => 'Plano',
            self::TYPE_DAY_PASS => 'Diária',
            self::TYPE_CLASS => 'Aula avulsa',
            self::TYPE_OTHER => 'Produto/serviço',
            default => '—',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendente',
            'paid' => 'Pago',
            'canceled' => 'Cancelado',
            default => '—',
        };
    }
}
