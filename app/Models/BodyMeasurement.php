<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyMeasurement extends Model
{
    protected $fillable = [
        'network_id',
        'student_id',
        'measured_at',
        'weight_kg',
        'body_fat_percent',
        'waist_cm',
        'hip_cm',
        'chest_cm',
        'arm_cm',
        'thigh_cm',
        'calf_cm',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'measured_at' => 'date',
            'weight_kg' => 'decimal:2',
            'body_fat_percent' => 'decimal:1',
            'waist_cm' => 'decimal:2',
            'hip_cm' => 'decimal:2',
            'chest_cm' => 'decimal:2',
            'arm_cm' => 'decimal:2',
            'thigh_cm' => 'decimal:2',
            'calf_cm' => 'decimal:2',
        ];
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function bmiForHeight(?float $heightCm): ?float
    {
        $weight = (float) $this->weight_kg;

        if ($weight <= 0 || ! $heightCm || $heightCm <= 0) {
            return null;
        }

        return round($weight / (($heightCm / 100) ** 2), 1);
    }

    public function getWeightLabelAttribute(): string
    {
        return number_format((float) $this->weight_kg, 2, ',', '.').' kg';
    }
}
