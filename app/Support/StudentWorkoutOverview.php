<?php

namespace App\Support;

use App\Models\Exercise;
use App\Models\Student;
use App\Models\WorkoutLog;
use App\Models\WorkoutLogSet;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StudentWorkoutOverview
{
    /**
     * Workout history and muscle-group breakdown for a student.
     *
     * @return array{
     *     logs: Collection<int, WorkoutLog>,
     *     recent_logs: Collection<int, WorkoutLog>,
     *     last_four_weeks_logs: Collection<int, WorkoutLog>,
     *     summary: array{workouts: int, sets: int, reps: int, volume: float, last_workout: Carbon|null, last_30: int},
     *     muscle_groups: array<int, array{group: string, label: string, reps: int, sets: int, volume: float, percent: int}>,
     *     muscle_group_exercises: array<string, array<int, array{name: string, reps: int, max_weight_label: string, percent: int}>>,
     *     weekly_frequency: array<int, array{label: string, count: int, percent: int}>,
     *     frequency_summary: array{total: int, average: float},
     *     top_exercises: array<int, array{name: string, sets: int, max_weight: float, volume: float}>,
     *     exercise_progress: array<int, array{name: string, muscle_group: string, sets: int, reps: int, max_weight: float, max_weight_label: string, last_date_label: string|null, evolution: array<int, array{date: string, value: float, label: string}>}>,
     *     overall_evolution: array<int, array{date: string, value: float, label: string, sets: int}>,
     *     workout_extracts: array<int, array{id: int, date: string|null, name: string, duration: string, volume: string, exercises: array<int, array{name: string, muscle_group: string, image_url: string|null, planned_sets: int|null, planned_reps: int|null, planned_load: string|null, executed: bool, sets: array<int, array{number: int, reps: int|null, weight_label: string|null, distance_label: string|null, cardio_label: string|null}>}>}>
     * }
     */
    public static function for(Student $student): array
    {
        $logs = $student->workoutLogs()
            ->where('status', WorkoutLog::STATUS_COMPLETED)
            ->with(['sets.exercise', 'session.exercises.exercise'])
            ->orderByDesc('started_at')
            ->get();

        $allSets = $logs->flatMap->sets;
        $totalReps = max(1, (int) $allSets->sum(fn ($set) => (int) $set->reps));

        $muscleGroups = $allSets
            ->groupBy('muscle_group')
            ->map(function (Collection $sets, $group) use ($totalReps) {
                $reps = (int) $sets->sum(fn ($set) => (int) $set->reps);

                return [
                    'group' => $group,
                    'label' => Exercise::MUSCLE_GROUPS[$group] ?? $group,
                    'reps' => $reps,
                    'sets' => $sets->count(),
                    'volume' => round($sets->sum(fn ($set) => ((int) $set->reps) * ((float) $set->weight_kg)), 2),
                    'percent' => (int) round(($reps / $totalReps) * 100),
                ];
            })
            ->sortByDesc('reps')
            ->values()
            ->all();

        $muscleGroupExercises = $allSets
            ->groupBy('muscle_group')
            ->map(function (Collection $sets) {
                $groupReps = max(1, (int) $sets->sum(fn ($set) => (int) $set->reps));

                return $sets
                    ->groupBy('exercise_name')
                    ->map(function (Collection $exerciseSets, $name) use ($groupReps) {
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

        $fourWeeksStart = now()->startOfWeek()->subWeeks(3);

        $weeklyFrequency = collect(range(3, 0))->values()->map(function (int $weeksAgo, int $index) use ($logs) {
            $start = now()->startOfWeek()->subWeeks($weeksAgo);
            $end = $start->copy()->endOfWeek();

            return [
                'label' => 'Semana '.($index + 1).' ('.$start->format('d/m').')',
                'count' => $logs->filter(fn (WorkoutLog $log) => $log->started_at->between($start, $end))->count(),
            ];
        });

        $maxWeekly = max(1, (int) ($weeklyFrequency->max('count') ?? 1));
        $weeklyFrequency = $weeklyFrequency
            ->map(function (array $item) use ($maxWeekly) {
                $item['percent'] = (int) round(($item['count'] / $maxWeekly) * 100);

                return $item;
            })
            ->values()
            ->all();

        $lastFourWeeksLogs = $logs
            ->filter(fn (WorkoutLog $log) => $log->started_at && $log->started_at->gte($fourWeeksStart))
            ->values();

        $frequencyTotal = collect($weeklyFrequency)->sum('count');

        $topExercises = $allSets
            ->groupBy('exercise_name')
            ->map(fn (Collection $sets, $name) => [
                'name' => $name,
                'sets' => $sets->count(),
                'max_weight' => (float) $sets->max(fn ($set) => (float) $set->weight_kg),
                'volume' => round($sets->sum(fn ($set) => ((int) $set->reps) * ((float) $set->weight_kg)), 2),
            ])
            ->sortByDesc('volume')
            ->take(10)
            ->values()
            ->all();

        return [
            'logs' => $logs,
            'recent_logs' => $logs,
            'last_four_weeks_logs' => $lastFourWeeksLogs,
            'summary' => [
                'workouts' => $logs->count(),
                'sets' => $allSets->count(),
                'reps' => (int) $allSets->sum(fn ($set) => (int) $set->reps),
                'volume' => round($allSets->sum(fn ($set) => ((int) $set->reps) * ((float) $set->weight_kg)), 2),
                'last_workout' => $logs->first()?->started_at,
                'last_30' => $logs->filter(fn (WorkoutLog $log) => $log->started_at->gte(now()->subDays(30)))->count(),
            ],
            'muscle_groups' => $muscleGroups,
            'muscle_group_exercises' => $muscleGroupExercises,
            'weekly_frequency' => $weeklyFrequency,
            'frequency_summary' => [
                'total' => $frequencyTotal,
                'average' => round($frequencyTotal / 4, 1),
            ],
            'top_exercises' => $topExercises,
            'exercise_progress' => self::exerciseProgress($logs),
            'overall_evolution' => self::overallEvolution($logs),
            'workout_extracts' => self::workoutExtracts($logs),
        ];
    }

    /**
     * Load evolution grouped by exercise.
     *
     * @param  Collection<int, WorkoutLog>  $logs
     * @return array<int, array{name: string, muscle_group: string, sets: int, reps: int, max_weight: float, max_weight_label: string, last_date_label: string|null, evolution: array<int, array{date: string, value: float, label: string}>}>
     */
    private static function exerciseProgress(Collection $logs): array
    {
        $exercises = [];

        foreach ($logs as $log) {
            foreach ($log->sets as $set) {
                $name = $set->exercise_name;

                if (! isset($exercises[$name])) {
                    $exercises[$name] = [
                        'name' => $name,
                        'muscle_group' => Exercise::MUSCLE_GROUPS[$set->muscle_group] ?? $set->muscle_group,
                        'sets' => 0,
                        'reps' => 0,
                        'max_weight' => 0.0,
                        'last_date' => null,
                        'evolution' => [],
                    ];
                }

                $exercises[$name]['sets']++;
                $exercises[$name]['reps'] += (int) $set->reps;
                $exercises[$name]['max_weight'] = max($exercises[$name]['max_weight'], (float) $set->weight_kg);

                if ($log->started_at) {
                    $dateKey = $log->started_at->toDateString();
                    $current = $exercises[$name]['evolution'][$dateKey]['max_weight'] ?? 0.0;
                    $exercises[$name]['evolution'][$dateKey] = [
                        'date' => $dateKey,
                        'max_weight' => max($current, (float) $set->weight_kg),
                    ];

                    if ($exercises[$name]['last_date'] === null || $log->started_at->gt($exercises[$name]['last_date'])) {
                        $exercises[$name]['last_date'] = $log->started_at;
                    }
                }
            }
        }

        return collect($exercises)
            ->map(function (array $exercise) {
                ksort($exercise['evolution']);

                $exercise['max_weight_label'] = $exercise['max_weight'] > 0
                    ? number_format($exercise['max_weight'], 2, ',', '.').' kg'
                    : '—';
                $exercise['last_date_label'] = $exercise['last_date']?->format('d/m/Y');
                $exercise['evolution'] = collect($exercise['evolution'])
                    ->map(fn (array $point) => [
                        'date' => Carbon::parse($point['date'])->format('d/m/Y'),
                        'value' => round($point['max_weight'], 2),
                        'label' => $point['max_weight'] > 0
                            ? number_format($point['max_weight'], 2, ',', '.').' kg'
                            : '—',
                    ])
                    ->values()
                    ->all();

                unset($exercise['last_date']);

                return $exercise;
            })
            ->sortByDesc('sets')
            ->values()
            ->all();
    }

    /**
     * Overall training-load evolution grouped by workout date.
     *
     * @param  Collection<int, WorkoutLog>  $logs
     * @return array<int, array{date: string, value: float, label: string, sets: int}>
     */
    private static function overallEvolution(Collection $logs): array
    {
        return $logs
            ->filter(fn (WorkoutLog $log) => $log->started_at !== null)
            ->groupBy(fn (WorkoutLog $log) => $log->started_at->toDateString())
            ->sortKeys()
            ->map(function (Collection $dayLogs, string $date) {
                $volume = round($dayLogs->sum(fn (WorkoutLog $log) => $log->total_volume), 2);

                return [
                    'date' => Carbon::parse($date)->format('d/m/Y'),
                    'value' => $volume,
                    'label' => number_format($volume, 0, ',', '.').' kg',
                    'sets' => $dayLogs->sum(fn (WorkoutLog $log) => $log->sets->count()),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, WorkoutLog>  $logs
     * @return array<int, array{id: int, date: string|null, name: string, duration: string, volume: string, exercises: array<int, array{name: string, muscle_group: string, image_url: string|null, planned_sets: int|null, planned_reps: int|null, planned_load: string|null, executed: bool, sets: array<int, array{number: int, reps: int|null, weight_label: string|null, distance_label: string|null, cardio_label: string|null}>}>}>
     */
    private static function workoutExtracts(Collection $logs): array
    {
        return $logs
            ->mapWithKeys(function (WorkoutLog $log) {
                $plannedExercises = $log->session?->exercises ?? collect();
                $setsByExercise = $log->sets->groupBy('workout_exercise_id');
                $exercises = [];

                foreach ($plannedExercises as $plannedExercise) {
                    $sets = $setsByExercise->get($plannedExercise->id, collect());

                    $exercises[] = [
                        'name' => $plannedExercise->exercise_name,
                        'muscle_group' => $plannedExercise->muscle_group_label,
                        'image_url' => $plannedExercise->exercise?->image_url,
                        'planned_sets' => $plannedExercise->sets,
                        'planned_reps' => $plannedExercise->reps,
                        'planned_load' => $plannedExercise->suggested_load,
                        'executed' => $sets->isNotEmpty(),
                        'sets' => self::setRows($sets),
                    ];
                }

                $extraSets = $log->sets->filter(
                    fn (WorkoutLogSet $set) => ! $plannedExercises->contains(
                        fn ($exercise) => (int) $exercise->id === (int) $set->workout_exercise_id
                    )
                );

                foreach ($extraSets->groupBy('exercise_name') as $name => $sets) {
                    $exercises[] = [
                        'name' => $name,
                        'muscle_group' => Exercise::MUSCLE_GROUPS[$sets->first()->muscle_group] ?? $sets->first()->muscle_group,
                        'image_url' => $sets->first()->exercise?->image_url,
                        'planned_sets' => null,
                        'planned_reps' => null,
                        'planned_load' => null,
                        'executed' => true,
                        'sets' => self::setRows($sets),
                    ];
                }

                return [
                    $log->id => [
                        'id' => $log->id,
                        'date' => $log->started_at?->format('d/m/Y H:i'),
                        'name' => $log->session_name,
                        'duration' => $log->duration_label,
                        'volume' => number_format($log->total_volume, 2, ',', '.').' kg',
                        'exercises' => $exercises,
                    ],
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, WorkoutLogSet>  $sets
     * @return array<int, array{number: int, reps: int|null, weight_label: string|null, distance_label: string|null, cardio_label: string|null}>
     */
    private static function setRows(Collection $sets): array
    {
        return $sets
            ->sortBy('set_number')
            ->map(function (WorkoutLogSet $set) {
                $cardioSeconds = (int) $set->cardio_seconds;

                return [
                    'number' => (int) $set->set_number,
                    'reps' => $set->reps !== null ? (int) $set->reps : null,
                    'weight_label' => $set->weight_kg !== null
                        ? number_format((float) $set->weight_kg, 2, ',', '.').' kg'
                        : null,
                    'distance_label' => $set->distance_km !== null
                        ? number_format((float) $set->distance_km, 3, ',', '.').' km'
                        : null,
                    'cardio_label' => $cardioSeconds > 0
                        ? sprintf('%d min %02d s', intdiv($cardioSeconds, 60), $cardioSeconds % 60)
                        : null,
                ];
            })
            ->values()
            ->all();
    }
}
