<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutLog extends Model
{
    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELED = 'canceled';

    protected $fillable = [
        'network_id',
        'student_id',
        'workout_plan_id',
        'workout_session_id',
        'session_name',
        'status',
        'started_at',
        'completed_at',
        'duration_seconds',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(WorkoutPlan::class, 'workout_plan_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class, 'workout_session_id');
    }

    public function sets(): HasMany
    {
        return $this->hasMany(WorkoutLogSet::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_IN_PROGRESS => 'Em andamento',
            self::STATUS_COMPLETED => 'Concluído',
            self::STATUS_CANCELED => 'Cancelado',
            default => '—',
        };
    }

    public function getTotalVolumeAttribute(): float
    {
        return round($this->sets->sum(
            fn (WorkoutLogSet $set) => ((int) $set->reps) * ((float) $set->weight_kg)
        ), 2);
    }

    public function getDurationLabelAttribute(): string
    {
        if (! $this->duration_seconds) {
            return '—';
        }

        $minutes = intdiv($this->duration_seconds, 60);
        $seconds = $this->duration_seconds % 60;

        return $minutes > 0
            ? sprintf('%d min %02d s', $minutes, $seconds)
            : sprintf('%d s', $seconds);
    }
}
