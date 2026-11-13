<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModalityCategory extends Model
{
    protected $fillable = [
        'network_id',
        'name',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function modalities(): HasMany
    {
        return $this->hasMany(Modality::class);
    }
}
