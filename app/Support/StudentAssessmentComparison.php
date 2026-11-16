<?php

namespace App\Support;

use App\Models\StudentAssessment;

class StudentAssessmentComparison
{
    /**
     * Whether each assessment field changed compared to the previous assessment.
     *
     * @return array<string, bool>
     */
    public static function changes(StudentAssessment $current, ?StudentAssessment $previous): array
    {
        if (! $previous) {
            return [];
        }

        $fields = [
            'goal',
            'goal_notes',
            'experience_level',
            'training_frequency',
            'medical_conditions',
            'medications',
            'injuries',
            'surgeries',
            'smoker',
            'alcohol_consumption',
            'sleep_hours',
            'stress_level',
            'emergency_contact_name',
            'emergency_contact_phone',
            'weight_kg',
            'height_cm',
            'body_fat_percent',
            'waist_cm',
            'hip_cm',
            'chest_cm',
            'arm_cm',
            'thigh_cm',
            'observations',
        ];

        $changes = [];

        foreach ($fields as $field) {
            $changes[$field] = self::normalize($current->{$field}) !== self::normalize($previous->{$field});
        }

        $currentParq = $current->parq_answers ?? [];
        $previousParq = $previous->parq_answers ?? [];

        foreach (array_keys(StudentAssessment::PARQ_QUESTIONS) as $key) {
            $changes['parq_'.$key] = filter_var($currentParq[$key] ?? false, FILTER_VALIDATE_BOOL)
                !== filter_var($previousParq[$key] ?? false, FILTER_VALIDATE_BOOL);
        }

        return $changes;
    }

    private static function normalize(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return rtrim(rtrim((string) (float) $value, '0'), '.');
        }

        return trim((string) $value);
    }
}
