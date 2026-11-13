<?php

namespace App\Models;

use App\Support\RecordScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Modality extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'network_id',
        'region_id',
        'unit_id',
        'modality_category_id',
        'created_level',
        'name',
        'image',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

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

    public function category(): BelongsTo
    {
        return $this->belongsTo(ModalityCategory::class, 'modality_category_id');
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'modality_units')->withTimestamps();
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'modality_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getCreatedLevelLabelAttribute(): string
    {
        return RecordScope::levelLabel($this->created_level);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return route('modality.image', ['modality' => $this, 'v' => substr(md5($this->image), 0, 8)]);
    }

    /**
     * Units available for this modality, based on where it was created.
     */
    public function availableUnitsQuery()
    {
        $query = Unit::query()->where('network_id', $this->network_id);

        return match ($this->created_level) {
            'region' => $query->where('region_id', $this->region_id),
            'unit' => $query->whereKey($this->unit_id),
            default => $query,
        };
    }
}
