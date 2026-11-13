<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exercise extends Model
{
    use SoftDeletes;

    /**
     * @var array<string, string>
     */
    public const MUSCLE_GROUPS = [
        'chest' => 'Peito',
        'back' => 'Costas',
        'shoulders' => 'Ombros',
        'biceps' => 'Bíceps',
        'triceps' => 'Tríceps',
        'legs' => 'Pernas',
        'glutes' => 'Glúteos',
        'calves' => 'Panturrilhas',
        'core' => 'Core / Abdômen',
        'cardio' => 'Cardio',
        'full_body' => 'Corpo inteiro',
    ];

    /**
     * @var array<string, string>
     */
    public const EQUIPMENT_OPTIONS = [
        'none' => 'Nenhum',
        'plate' => 'Anilha',
        'barbell' => 'Barra',
        'resistance_band' => 'Faixa elástica',
        'suspension_strap' => 'Fita de suspensão',
        'dumbbell' => 'Halter',
        'kettlebell' => 'Kettlebell',
        'machine' => 'Máquina',
        'other' => 'Outro',
    ];

    protected $fillable = [
        'network_id',
        'name',
        'muscle_group',
        'equipment',
        'instructions',
        'image',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'image_url',
        'equipment_label',
        'muscle_group_label',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function workoutExercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getMuscleGroupLabelAttribute(): string
    {
        return self::MUSCLE_GROUPS[$this->muscle_group] ?? $this->muscle_group;
    }

    public function getEquipmentLabelAttribute(): string
    {
        return self::EQUIPMENT_OPTIONS[$this->equipment] ?? ($this->equipment ?: 'Nenhum');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return route('exercise.image', [
            'exercise' => $this,
            'v' => substr(md5($this->image), 0, 8),
        ]);
    }
}
