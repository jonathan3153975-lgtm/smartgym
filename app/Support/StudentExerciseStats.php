<?php

namespace App\Support;

use App\Models\Student;
use App\Models\WorkoutLog;
use App\Models\WorkoutLogSet;
use Illuminate\Support\Collection;

class StudentExerciseStats
{
    /**
     * Last and best records of a student per exercise.
     *
     * @param  Collection<int, int|null>  $exerciseIds
     * @return array<int, array{
     *     last_weight: float|null,
     *     max_weight: float|null,
     *     last_weight_label: string|null,
     *     max_weight_label: string|null,
     *     last_seconds: int,
     *     max_seconds: int,
     *     last_seconds_label: string|null,
     *     max_seconds_label: string|null,
     *     last_distance: float|null,
     *     max_distance: float|null,
     *     last_distance_label: string|null,
     *     max_distance_label: string|null
     * }>
     */
    public static function for(Student $student, Collection $exerciseIds): array
    {
        $ids = $exerciseIds->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $sets = WorkoutLogSet::query()
            ->whereIn('exercise_id', $ids)
            ->whereHas('log', function ($query) use ($student) {
                $query->where('student_id', $student->id)
                    ->where('status', WorkoutLog::STATUS_COMPLETED);
            })
            ->with('log:id,started_at')
            ->get();

        return $sets
            ->groupBy('exercise_id')
            ->map(function (Collection $exerciseSets) {
                $last = $exerciseSets
                    ->sortByDesc(fn (WorkoutLogSet $set) => sprintf(
                        '%010d-%03d',
                        $set->log?->started_at?->timestamp ?? 0,
                        $set->set_number
                    ))
                    ->first();

                $lastWeight = self::positiveFloat($last?->weight_kg);
                $maxWeight = self::positiveFloat($exerciseSets->max(fn (WorkoutLogSet $set) => (float) $set->weight_kg));
                $lastSeconds = (int) ($last?->cardio_seconds ?? 0);
                $maxSeconds = (int) $exerciseSets->max(fn (WorkoutLogSet $set) => (int) $set->cardio_seconds);
                $lastDistance = self::positiveFloat($last?->distance_km);
                $maxDistance = self::positiveFloat($exerciseSets->max(fn (WorkoutLogSet $set) => (float) $set->distance_km));

                return [
                    'last_weight' => $lastWeight,
                    'max_weight' => $maxWeight,
                    'last_weight_label' => self::weightLabel($lastWeight),
                    'max_weight_label' => self::weightLabel($maxWeight),
                    'last_seconds' => $lastSeconds,
                    'max_seconds' => $maxSeconds,
                    'last_seconds_label' => self::timeLabel($lastSeconds),
                    'max_seconds_label' => self::timeLabel($maxSeconds),
                    'last_distance' => $lastDistance,
                    'max_distance' => $maxDistance,
                    'last_distance_label' => self::distanceLabel($lastDistance),
                    'max_distance_label' => self::distanceLabel($maxDistance),
                ];
            })
            ->all();
    }

    private static function positiveFloat(mixed $value): ?float
    {
        $number = (float) $value;

        return $number > 0 ? $number : null;
    }

    private static function weightLabel(?float $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $formatted = rtrim(rtrim(number_format($value, 2, ',', '.'), '0'), ',');

        return $formatted.' kg';
    }

    private static function timeLabel(int $seconds): ?string
    {
        if ($seconds <= 0) {
            return null;
        }

        return $seconds >= 60
            ? sprintf('%d min %02d s', intdiv($seconds, 60), $seconds % 60)
            : $seconds.' s';
    }

    private static function distanceLabel(?float $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $formatted = rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');

        return $formatted.' km';
    }
}
