<?php

namespace App\Support;

use App\Models\StudentAssessment;

class AssessmentMetrics
{
    /**
     * Measurements and percentage variation against the previous assessment.
     *
     * @return array<string, array{label: string, value: string, percent: float|null, percent_label: string|null, direction: string|null}>
     */
    public static function for(?StudentAssessment $current, ?StudentAssessment $previous): array
    {
        if (! $current) {
            return [];
        }

        $fields = [
            'weight_kg' => ['label' => 'Peso', 'unit' => 'kg', 'decimals' => 2],
            'height_cm' => ['label' => 'Altura', 'unit' => 'cm', 'decimals' => 2],
            'body_fat_percent' => ['label' => 'Gordura', 'unit' => '%', 'decimals' => 1],
            'bmi' => ['label' => 'IMC', 'unit' => 'kg/m²', 'decimals' => 1],
            'waist_cm' => ['label' => 'Cintura', 'unit' => 'cm', 'decimals' => 2],
            'hip_cm' => ['label' => 'Quadril', 'unit' => 'cm', 'decimals' => 2],
            'chest_cm' => ['label' => 'Tórax', 'unit' => 'cm', 'decimals' => 2],
            'arm_cm' => ['label' => 'Braço', 'unit' => 'cm', 'decimals' => 2],
            'thigh_cm' => ['label' => 'Coxa', 'unit' => 'cm', 'decimals' => 2],
        ];

        $result = [];

        foreach ($fields as $field => $config) {
            $value = $current->{$field};
            $previousValue = $previous?->{$field};
            $percent = null;

            if ($value !== null && $previousValue !== null && (float) $previousValue !== 0.0) {
                $percent = round((((float) $value - (float) $previousValue) / (float) $previousValue) * 100, 1);
            }

            $result[$field] = [
                'label' => $config['label'],
                'value' => $value !== null
                    ? number_format((float) $value, $config['decimals'], ',', '.').' '.$config['unit']
                    : '—',
                'percent' => $percent,
                'percent_label' => $percent === null
                    ? null
                    : ($percent > 0 ? '+' : '').number_format($percent, 1, ',', '.').'%',
                'direction' => $percent === null ? null : ($percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'same')),
            ];
        }

        return $result;
    }
}
