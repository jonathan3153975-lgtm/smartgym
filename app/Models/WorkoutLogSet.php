<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutLogSet extends Model
{
    protected $fillable = [
        'workout_log_id',
        'workout_exercise_id',
        'exercise_id',
        'exercise_name',
        'muscle_group',
        'set_number',
        'reps',
        'weight_kg',
        'rest_seconds',
    ];

    protected $casts = [
        'set_number' => 'integer',
        'reps' => 'integer',
        'weight_kg' => 'decimal:2',
        'rest_seconds' => 'integer',
    ];

    public function log(): BelongsTo
    {
        return $this->belongsTo(WorkoutLog::class, 'workout_log_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function getMuscleGroupLabelAttribute(): string
    {
        return Exercise::MUSCLE_GROUPS[$this->muscle_group] ?? $this->muscle_group;
    }
}
