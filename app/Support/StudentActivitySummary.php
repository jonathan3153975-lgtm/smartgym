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
     *     workout_extracts: array<int, array{id: int, date: string|null, name: string, duration: string, volume: string, exercises: array<int, array{name: string, muscle_group: string, max_weight: float, max_weight_label: string, previous_load_label: string|null, load_increased: bool, sets: array<int, array{number: int, reps: int|null, weight_label: string|null, distance_label: string|null, cardio_label: string|null}>}>}>,
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
            'workout_extracts' => self::workoutExtracts($workoutLogs),
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
     * Detailed workout extract between assessments, including load evolution per exercise.
     *
     * @param  Collection<int, WorkoutLog>  $logs
     * @return array<int, array{id: int, date: string|null, name: string, duration: string, volume: string, exercises: array<int, array{name: string, muscle_group: string, max_weight: float, max_weight_label: string, previous_load_label: string|null, load_increased: bool, sets: array<int, array{number: int, reps: int|null, weight_label: string|null, distance_label: string|null, cardio_label: string|null}>}>}>
     */
    private static function workoutExtracts(Collection $logs): array
    {
        $previousLoads = [];

        return $logs
            ->sortBy('started_at')
            ->map(function (WorkoutLog $log) use (&$previousLoads) {
                $exercises = $log->sets
                    ->groupBy('exercise_name')
                    ->map(function (Collection $sets, string $name) use (&$previousLoads) {
                        $maxWeight = (float) $sets->max(fn ($set) => (float) $set->weight_kg);
                        $previousLoad = $previousLoads[$name] ?? null;
                        $loadIncreased = $previousLoad !== null && $maxWeight > $previousLoad + 0.0001;

                        $previousLoads[$name] = max($previousLoad ?? 0.0, $maxWeight);

                        return [
                            'name' => $name,
                            'muscle_group' => Exercise::MUSCLE_GROUPS[$sets->first()->muscle_group] ?? $sets->first()->muscle_group,
                            'max_weight' => $maxWeight,
                            'max_weight_label' => $maxWeight > 0 ? number_format($maxWeight, 2, ',', '.').' kg' : '—',
                            'previous_load_label' => $previousLoad !== null && $previousLoad > 0
                                ? number_format($previousLoad, 2, ',', '.').' kg'
                                : null,
                            'load_increased' => $loadIncreased,
                            'sets' => $sets
                                ->sortBy('set_number')
                                ->map(fn ($set) => [
                                    'number' => (int) $set->set_number,
                                    'reps' => $set->reps !== null ? (int) $set->reps : null,
                                    'weight_label' => $set->weight_kg !== null
                                        ? number_format((float) $set->weight_kg, 2, ',', '.').' kg'
                                        : null,
                                    'distance_label' => $set->distance_km !== null
                                        ? number_format((float) $set->distance_km, 3, ',', '.').' km'
                                        : null,
                                    'cardio_label' => $set->cardio_seconds
                                        ? sprintf('%d min %02d s', intdiv((int) $set->cardio_seconds, 60), (int) $set->cardio_seconds % 60)
                                        : null,
                                ])
                                ->values()
                                ->all(),
                        ];
                    })
                    ->values()
                    ->all();

                return [
                    'id' => $log->id,
                    'date' => $log->started_at?->format('d/m/Y H:i'),
                    'name' => $log->session_name,
                    'duration' => $log->duration_label,
                    'volume' => number_format($log->total_volume, 2, ',', '.').' kg',
                    'exercises' => $exercises,
                ];
            })
            ->reverse()
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
