<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Region extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'network_id',
        'name',
        'description',
        'status',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function userVinculos(): HasMany
    {
        return $this->hasMany(UserVinculo::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
