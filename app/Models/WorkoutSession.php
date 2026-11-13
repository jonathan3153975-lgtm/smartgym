<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutSession extends Model
{
    protected $fillable = [
        'workout_plan_id',
        'name',
        'day_label',
        'order',
        'notes',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(WorkoutPlan::class, 'workout_plan_id');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class)->orderBy('order')->orderBy('id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkoutLog::class);
    }

    public function getExerciseCountAttribute(): int
    {
        return $this->exercises()->count();
    }
}
