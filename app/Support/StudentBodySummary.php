<?php

namespace App\Support;

use App\Models\BodyMeasurement;
use App\Models\Student;
use App\Models\StudentAssessment;

class StudentBodySummary
{
    /**
     * Summary cards for the latest assessment, falling back to the latest body measurement.
     *
     * @return array{
     *     current: StudentAssessment|null,
     *     previous: StudentAssessment|null,
     *     latest_measurement: BodyMeasurement|null,
     *     metrics: array<string, array{label: string, value: string, percent: float|null, percent_label: string|null, direction: string|null}>,
     *     cards: array<string, array{value: string, comparison: string|null, meta: string}>
     * }
     */
    public static function for(Student $student): array
    {
        $student->loadMissing(['assessment', 'assessments', 'measurements']);

        $current = $student->assessment;
        $previous = $student->assessments->skip(1)->first();
        $first = $student->assessments->last();
        $latestMeasurement = $student->measurements->first();
        $firstMeasurement = $student->measurements->last();

        $height = self::toFloat($current?->height_cm);
        $weight = self::toFloat($current?->weight_kg) ?? self::toFloat($latestMeasurement?->weight_kg);
        $previousWeight = self::toFloat($previous?->weight_kg);
        $firstWeight = self::toFloat($first?->weight_kg) ?? self::toFloat($firstMeasurement?->weight_kg);

        $bmi = $current?->bmi;

        if ($bmi === null && $latestMeasurement && $height) {
            $bmi = $latestMeasurement->bmiForHeight($height);
        }

        $previousBmi = $previous?->bmi;
        $bodyFat = self::toFloat($current?->body_fat_percent) ?? self::toFloat($latestMeasurement?->body_fat_percent);
        $previousBodyFat = self::toFloat($previous?->body_fat_percent);
        $waist = self::toFloat($current?->waist_cm) ?? self::toFloat($latestMeasurement?->waist_cm);

        return [
            'current' => $current,
            'previous' => $previous,
            'latest_measurement' => $latestMeasurement,
            'metrics' => AssessmentMetrics::for($current, $previous),
            'cards' => [
                'weight' => [
                    'value' => self::format($weight, 2, 'kg'),
                    'comparison' => self::comparison($weight, $previousWeight, 2, 'kg'),
                    'meta' => $current?->submitted_at
                        ? 'Avaliação de '.$current->submitted_at->format('d/m/Y')
                        : ($latestMeasurement?->measured_at
                            ? 'Medida de '.$latestMeasurement->measured_at->format('d/m/Y')
                            : 'Sem registros'),
                ],
                'total' => [
                    'value' => self::comparison($weight, $firstWeight, 2, 'kg') ?? '—',
                    'comparison' => null,
                    'meta' => $first ? 'desde a primeira avaliação' : 'desde o primeiro registro',
                ],
                'bmi' => [
                    'value' => $bmi !== null ? number_format($bmi, 1, ',', '.') : '—',
                    'comparison' => self::comparison($bmi, $previousBmi, 1, ''),
                    'meta' => $height
                        ? 'altura '.number_format($height, 0, ',', '.').' cm'
                        : 'Altura não informada',
                ],
                'fat_waist' => [
                    'value' => $bodyFat !== null ? number_format($bodyFat, 1, ',', '.').'%' : '—',
                    'comparison' => self::comparison($bodyFat, $previousBodyFat, 1, 'p.p.'),
                    'meta' => $waist !== null
                        ? 'cintura '.number_format($waist, 2, ',', '.').' cm'
                        : 'Cintura não informada',
                ],
            ],
        ];
    }

    private static function toFloat(mixed $value): ?float
    {
        return $value === null || $value === '' ? null : (float) $value;
    }

    private static function format(?float $value, int $decimals, string $unit): string
    {
        if ($value === null) {
            return '—';
        }

        return number_format($value, $decimals, ',', '.').($unit !== '' ? ' '.$unit : '');
    }

    private static function comparison(?float $current, ?float $previous, int $decimals, string $unit): ?string
    {
        if ($current === null || $previous === null) {
            return null;
        }

        $difference = $current - $previous;

        if (abs($difference) < 0.0001) {
            return 'Sem alteração';
        }

        $absolute = ($difference > 0 ? '+' : '').number_format($difference, $decimals, ',', '.')
            .($unit !== '' ? ' '.$unit : '');
        $percent = $previous !== 0.0 ? round(($difference / $previous) * 100, 1) : null;

        return $percent === null
            ? $absolute
            : $absolute.' ('.($percent > 0 ? '+' : '').number_format($percent, 1, ',', '.').'%)';
    }
}
