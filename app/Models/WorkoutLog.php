<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    /**
     * Share of the workout exercises that involve each muscle group,
     * ordered from the most to the least exercised group.
     *
     * @return Collection<int, array{
     *     group: string,
     *     label: string,
     *     exercises: int,
     *     percent: int,
     *     cardio_seconds: int,
     *     cardio_label: string|null
     * }>
     */
    public function getMuscleGroupSummaryAttribute(): Collection
    {
        $this->loadMissing('sets');

        $exerciseKey = fn (WorkoutLogSet $set): string => $set->workout_exercise_id
            ? 'exercise:'.$set->workout_exercise_id
            : 'name:'.$set->exercise_name;

        $totalExercises = $this->sets
            ->map($exerciseKey)
            ->unique()
            ->count();

        return $this->sets
            ->groupBy('muscle_group')
            ->map(function ($sets, $group) use ($exerciseKey, $totalExercises) {
                $exercises = $sets->map($exerciseKey)->unique()->count();
                $cardioSeconds = (int) $sets->sum(fn (WorkoutLogSet $set) => (int) $set->cardio_seconds);

                $percent = $totalExercises > 0
                    ? (int) round(($exercises / $totalExercises) * 100)
                    : 0;

                return [
                    'group' => $group,
                    'label' => Exercise::MUSCLE_GROUPS[$group] ?? (string) $group,
                    'exercises' => $exercises,
                    'percent' => $percent,
                    'cardio_seconds' => $cardioSeconds,
                    'cardio_label' => $cardioSeconds > 0
                        ? sprintf('%d min %02d s', intdiv($cardioSeconds, 60), $cardioSeconds % 60)
                        : null,
                ];
            })
            ->sortBy([
                ['percent', 'desc'],
                ['exercises', 'desc'],
            ])
            ->values();
    }
}
