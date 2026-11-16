<?php

namespace App\Support;

use App\Models\Exercise;
use App\Models\Student;
use App\Models\WorkoutLog;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class StudentActivitySummary
{
    /**
     * Build the activity extract between two assessments.
     *
     * @return array{
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     workouts: int,
     *     classes: int,
     *     checkins: int,
     *     activity_days: int,
     *     total_sets: int,
     *     workout_logs: array<int, array{date: string, name: string, duration: string, sets: int, volume: string}>,
     *     muscle_groups: array<int, array{group: string, label: string, reps: int, percent: int}>,
     *     muscle_group_exercises: array<string, array<int, array{name: string, reps: int, max_weight_label: string, percent: int}>>
     * }
     */
    public static function for(Student $student, CarbonInterface $from, CarbonInterface $to): array
    {
        $workoutLogs = $student->workoutLogs()
            ->where('status', WorkoutLog::STATUS_COMPLETED)
            ->whereBetween('started_at', [$from, $to])
            ->with('sets')
            ->orderByDesc('started_at')
            ->get();

        $checkins = $student->checkins()
            ->whereBetween('checked_in_at', [$from, $to])
            ->get();

        $classCheckins = $student->classBookings()
            ->whereNotNull('checked_in_at')
            ->whereBetween('checked_in_at', [$from, $to])
            ->with(['schoolClass.modality:id,name'])
            ->get();

        $activityDates = collect()
            ->merge($workoutLogs->pluck('started_at'))
            ->merge($checkins->pluck('checked_in_at'))
            ->merge($classCheckins->pluck('checked_in_at'))
            ->filter()
            ->map(fn ($date) => $date->toDateString())
            ->unique();

        $allSets = $workoutLogs->flatMap->sets;
        $totalReps = max(1, (int) $allSets->sum(fn ($set) => (int) $set->reps));

        return [
            'from' => $from,
            'to' => $to,
            'workouts' => $workoutLogs->count(),
            'classes' => $classCheckins->count(),
            'checkins' => $checkins->count(),
            'activity_days' => $activityDates->count(),
            'total_sets' => $allSets->count(),
            'workout_logs' => self::workoutLogs($workoutLogs),
            'muscle_groups' => self::muscleGroups($allSets, $totalReps),
            'muscle_group_exercises' => self::muscleGroupExercises($allSets),
        ];
    }

    /**
     * @param  Collection<int, WorkoutLog>  $logs
     * @return array<int, array{date: string, name: string, duration: string, sets: int, volume: string}>
     */
    private static function workoutLogs(Collection $logs): array
    {
        return $logs
            ->map(fn (WorkoutLog $log) => [
                'date' => $log->started_at?->format('d/m/Y') ?? '—',
                'name' => $log->session_name,
                'duration' => $log->duration_label,
                'sets' => $log->sets->count(),
                'volume' => number_format($log->total_volume, 2, ',', '.').' kg',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, mixed>  $sets
     * @return array<int, array{group: string, label: string, reps: int, percent: int}>
     */
    private static function muscleGroups(Collection $sets, int $totalReps): array
    {
        return $sets
            ->groupBy('muscle_group')
            ->map(function (Collection $groupSets, $group) use ($totalReps) {
                $reps = (int) $groupSets->sum(fn ($set) => (int) $set->reps);

                return [
                    'group' => $group,
                    'label' => Exercise::MUSCLE_GROUPS[$group] ?? $group,
                    'reps' => $reps,
                    'percent' => (int) round(($reps / $totalReps) * 100),
                ];
            })
            ->sortByDesc('reps')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, mixed>  $sets
     * @return array<string, array<int, array{name: string, reps: int, max_weight_label: string, percent: int}>>
     */
    private static function muscleGroupExercises(Collection $sets): array
    {
        return $sets
            ->groupBy('muscle_group')
            ->map(function (Collection $groupSets) {
                $groupReps = max(1, (int) $groupSets->sum(fn ($set) => (int) $set->reps));

                return $groupSets
                    ->groupBy('exercise_name')
                    ->map(function (Collection $exerciseSets, string $name) use ($groupReps) {
                        $reps = (int) $exerciseSets->sum(fn ($set) => (int) $set->reps);
                        $maxWeight = (float) $exerciseSets->max(fn ($set) => (float) $set->weight_kg);

                        return [
                            'name' => $name,
                            'reps' => $reps,
                            'max_weight_label' => $maxWeight > 0
                                ? rtrim(rtrim(number_format($maxWeight, 2, ',', '.'), '0'), ',').' kg'
                                : '—',
                            'percent' => (int) round(($reps / $groupReps) * 100),
                        ];
                    })
                    ->sortByDesc('reps')
                    ->values()
                    ->all();
            })
            ->all();
    }
}
