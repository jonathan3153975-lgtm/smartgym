<?php

namespace App\Support;

use App\Models\StudentAssessment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentAssessmentForm
{
    /**
     * Validation rules shared by the student portal and the staff form.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'goal' => ['required', Rule::in(array_keys(StudentAssessment::GOALS))],
            'goal_notes' => ['nullable', 'string', 'max:2000'],
            'experience_level' => ['required', Rule::in(array_keys(StudentAssessment::EXPERIENCE_LEVELS))],
            'training_frequency' => ['required', 'integer', 'between:1,7'],
            'medical_conditions' => ['nullable', 'string', 'max:2000'],
            'medications' => ['nullable', 'string', 'max:2000'],
            'injuries' => ['nullable', 'string', 'max:2000'],
            'surgeries' => ['nullable', 'string', 'max:2000'],
            'smoker' => ['nullable', 'boolean'],
            'alcohol_consumption' => ['nullable', Rule::in(array_keys(StudentAssessment::ALCOHOL_CONSUMPTION))],
            'sleep_hours' => ['nullable', 'integer', 'between:1,16'],
            'stress_level' => ['nullable', 'integer', 'between:1,5'],
            'parq_answers' => ['required', 'array'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:20'],
            'weight_kg' => ['nullable', 'numeric', 'between:20,400'],
            'height_cm' => ['nullable', 'numeric', 'between:80,250'],
            'body_fat_percent' => ['nullable', 'numeric', 'between:1,70'],
            'waist_cm' => ['nullable', 'numeric', 'between:20,250'],
            'hip_cm' => ['nullable', 'numeric', 'between:20,250'],
            'chest_cm' => ['nullable', 'numeric', 'between:20,250'],
            'arm_cm' => ['nullable', 'numeric', 'between:10,100'],
            'thigh_cm' => ['nullable', 'numeric', 'between:20,150'],
            'observations' => ['nullable', 'string', 'max:4000'],
            'consent' => ['accepted'],
        ];
    }

    /**
     * Convert masked measurements ("80,500") into dot decimals before validation.
     */
    public static function normalizeDecimals(Request $request): void
    {
        $request->merge(collect([
            'weight_kg',
            'height_cm',
            'body_fat_percent',
            'waist_cm',
            'hip_cm',
            'chest_cm',
            'arm_cm',
            'thigh_cm',
        ])->mapWithKeys(fn (string $field) => [
            $field => self::normalizeDecimal($request->input($field)),
        ])->all());
    }

    /**
     * @param  array<string, mixed>  $answers
     * @return array<string, bool>
     */
    public static function parqAnswers(array $answers): array
    {
        $normalized = [];
        $missing = false;

        foreach (array_keys(StudentAssessment::PARQ_QUESTIONS) as $key) {
            if (! array_key_exists($key, $answers)) {
                $missing = true;
            }

            $normalized[$key] = filter_var($answers[$key] ?? false, FILTER_VALIDATE_BOOL);
        }

        if ($missing) {
            throw ValidationException::withMessages([
                'parq_answers' => 'Responda todas as perguntas do questionário de saúde.',
            ]);
        }

        return $normalized;
    }

    private static function normalizeDecimal(mixed $value): mixed
    {
        if (! is_string($value) || ! str_contains($value, ',')) {
            return $value;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return str_replace(['.', ','], ['', '.'], $value);
    }
}
