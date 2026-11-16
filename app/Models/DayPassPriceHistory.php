<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DayPassPriceHistory extends Model
{
    protected $fillable = [
        'network_id',
        'region_id',
        'unit_id',
        'price',
        'changed_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function getScopeLabelAttribute(): string
    {
        if ($this->unit_id) {
            return 'Unidade';
        }

        return $this->region_id ? 'Região' : 'Rede';
    }

    public function getPriceLabelAttribute(): string
    {
        return $this->price !== null
            ? 'R$ '.number_format((float) $this->price, 2, ',', '.')
            : 'Herdar da esfera superior';
    }
}
