<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutExercise extends Model
{
    protected $fillable = [
        'workout_session_id',
        'exercise_id',
        'exercise_name',
        'muscle_group',
        'order',
        'sets',
        'reps',
        'rest_seconds',
        'suggested_load',
        'notes',
    ];

    protected $casts = [
        'order' => 'integer',
        'sets' => 'integer',
        'reps' => 'integer',
        'rest_seconds' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class, 'workout_session_id');
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
