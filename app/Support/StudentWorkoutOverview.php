<?php

namespace App\Support;

use App\Models\Exercise;
use App\Models\Student;
use App\Models\WorkoutLog;
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
     *     summary: array{workouts: int, sets: int, reps: int, volume: float, last_workout: Carbon|null, last_30: int},
     *     muscle_groups: array<int, array{group: string, label: string, reps: int, sets: int, volume: float, percent: int}>,
     *     muscle_group_exercises: array<string, array<int, array{name: string, reps: int, max_weight_label: string, percent: int}>>,
     *     weekly_frequency: array<int, array{label: string, count: int, percent: int}>,
     *     top_exercises: array<int, array{name: string, sets: int, max_weight: float, volume: float}>
     * }
     */
    public static function for(Student $student): array
    {
        $logs = $student->workoutLogs()
            ->where('status', WorkoutLog::STATUS_COMPLETED)
            ->with('sets')
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

        $weeklyFrequency = collect(range(7, 0))->map(function (int $weeksAgo) use ($logs) {
            $start = now()->startOfWeek()->subWeeks($weeksAgo);
            $end = $start->copy()->endOfWeek();

            return [
                'label' => $start->format('d/m'),
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
            'top_exercises' => $topExercises,
        ];
    }
}
