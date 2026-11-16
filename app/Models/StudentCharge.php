<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentCharge extends Model
{
    public const TYPE_PLAN = 'plan';

    public const TYPE_DAY_PASS = 'day_pass';

    public const TYPE_CLASS = 'class';

    public const TYPE_OTHER = 'other';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'network_id',
        'unit_id',
        'student_id',
        'student_sale_id',
        'student_sale_item_id',
        'type',
        'description',
        'amount',
        'status',
        'payment_method',
        'reference_type',
        'reference_id',
        'due_date',
        'metadata',
        'payment_gateway_setting_id',
        'gateway_payment_id',
        'pix_qr_code',
        'pix_qr_code_base64',
        'pix_generated_at',
        'gateway_payload',
        'paid_at',
        'canceled_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'metadata' => 'array',
            'gateway_payload' => 'array',
            'due_date' => 'date',
            'pix_generated_at' => 'datetime',
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

    public function sale(): BelongsTo
    {
        return $this->belongsTo(StudentSale::class, 'student_sale_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(StudentSaleItem::class, 'student_sale_item_id');
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(StudentSubscription::class, 'reference_id');
    }

    public function classBooking(): BelongsTo
    {
        return $this->belongsTo(ClassBooking::class, 'reference_id');
    }

    public function gatewaySetting(): BelongsTo
    {
        return $this->belongsTo(PaymentGatewaySetting::class, 'payment_gateway_setting_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
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
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_PAID => 'Pago',
            self::STATUS_CANCELED => 'Cancelado',
            default => '—',
        };
    }
}
