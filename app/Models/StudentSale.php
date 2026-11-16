<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentSale extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'network_id',
        'unit_id',
        'student_id',
        'code',
        'status',
        'launched_at',
        'notes',
        'total',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'launched_at' => 'date',
            'total' => 'decimal:2',
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

    public function items(): HasMany
    {
        return $this->hasMany(StudentSaleItem::class)->orderBy('id');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(StudentCharge::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StudentSalePayment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Aguardando pagamento',
            self::STATUS_PARTIAL => 'Parcialmente pago',
            self::STATUS_PAID => 'Pago',
            self::STATUS_CANCELED => 'Cancelado',
            default => '—',
        };
    }

    public function refreshStatus(): void
    {
        $charges = $this->charges()->get();

        if ($charges->isEmpty()) {
            return;
        }

        $status = match (true) {
            $charges->every(fn (StudentCharge $charge) => $charge->status === StudentCharge::STATUS_CANCELED) => self::STATUS_CANCELED,
            $charges->contains(fn (StudentCharge $charge) => $charge->status === StudentCharge::STATUS_PENDING) && $charges->contains(fn (StudentCharge $charge) => $charge->status === StudentCharge::STATUS_PAID) => self::STATUS_PARTIAL,
            $charges->contains(fn (StudentCharge $charge) => $charge->status === StudentCharge::STATUS_PENDING) => self::STATUS_PENDING,
            default => self::STATUS_PAID,
        };

        $this->update(['status' => $status]);
    }
}
