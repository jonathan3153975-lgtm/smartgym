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
        'payment_gateway_setting_id',
        'status',
        'type',
        'booked_for',
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
}
