<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassBooking extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELED = 'canceled';

    public const STATUS_FAILED = 'failed';

    public const TYPE_SINGLE = 'single';

    public const TYPE_PLAN = 'plan';

    protected $fillable = [
        'network_id',
        'unit_id',
        'student_id',
        'class_id',
        'class_schedule_id',
        'payment_gateway_setting_id',
        'status',
        'type',
        'booked_for',
        'early_minutes',
        'checkin_opens_at',
        'checkin_closes_at',
        'checked_in_at',
        'credit_available',
        'credit_granted_at',
        'amount',
        'preference_id',
        'gateway_payment_id',
        'init_point',
        'paid_at',
        'gateway_payload',
    ];

    protected function casts(): array
    {
        return [
            'booked_for' => 'date',
            'early_minutes' => 'integer',
            'checkin_opens_at' => 'datetime',
            'checkin_closes_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'credit_available' => 'boolean',
            'credit_granted_at' => 'datetime',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'gateway_payload' => 'array',
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

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class, 'class_schedule_id');
    }

    public function gatewaySetting(): BelongsTo
    {
        return $this->belongsTo(PaymentGatewaySetting::class, 'payment_gateway_setting_id');
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Aguardando pagamento',
            self::STATUS_CONFIRMED => 'Confirmada',
            self::STATUS_CANCELED => 'Cancelada',
            self::STATUS_FAILED => 'Falhou',
            default => '—',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_PLAN => 'Incluída no plano',
            default => 'Aula avulsa',
        };
    }

    public function hasCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    public function isCheckinOpen(): bool
    {
        if (! $this->isConfirmed() || ! $this->checkin_opens_at || ! $this->checkin_closes_at || $this->hasCheckedIn()) {
            return false;
        }

        return now()->between($this->checkin_opens_at, $this->checkin_closes_at);
    }

    public function getCheckinWindowLabelAttribute(): string
    {
        if (! $this->checkin_opens_at || ! $this->checkin_closes_at) {
            return 'Janela não definida';
        }

        return $this->checkin_opens_at->format('H:i').' às '.$this->checkin_closes_at->format('H:i');
    }
}
