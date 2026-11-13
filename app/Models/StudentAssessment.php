<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAssessment extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_COMPLETED = 'completed';

    public const GOALS = [
        'weight_loss' => 'Perda de peso',
        'muscle_gain' => 'Ganho de massa muscular',
        'conditioning' => 'Condicionamento físico',
        'health' => 'Saúde e qualidade de vida',
        'performance' => 'Desempenho esportivo',
        'other' => 'Outro',
    ];

    public const EXPERIENCE_LEVELS = [
        'beginner' => 'Iniciante',
        'intermediate' => 'Intermediário',
        'advanced' => 'Avançado',
    ];

    public const ALCOHOL_CONSUMPTION = [
        'never' => 'Não consumo',
        'social' => 'Socialmente',
        'frequent' => 'Frequentemente',
    ];

    /**
     * Simplified PAR-Q questions. Any positive answer indicates the student
     * should seek medical clearance before starting an exercise program.
     *
     * @var array<string, string>
     */
    public const PARQ_QUESTIONS = [
        'heart_condition' => 'Algum médico já disse que você possui problema cardíaco ou pressão alta?',
        'chest_pain' => 'Você sente dor no peito ao praticar atividade física?',
        'dizziness' => 'Você perde o equilíbrio por tontura ou já perdeu a consciência nos últimos 12 meses?',
        'bone_joint' => 'Você possui algum problema ósseo ou articular que possa piorar com atividade física?',
        'blood_pressure_medication' => 'Você toma algum medicamento para pressão arterial ou coração?',
        'other_medical_reason' => 'Existe algum outro motivo médico pelo qual você não deveria praticar atividade física?',
        'physical_limitation' => 'Alguma condição física limita sua participação em atividades físicas?',
    ];

    protected $fillable = [
        'network_id',
        'student_id',
        'status',
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
        'parq_answers',
        'parq_positive',
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
        'consent',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'smoker' => 'boolean',
            'parq_answers' => 'array',
            'parq_positive' => 'boolean',
            'consent' => 'boolean',
            'training_frequency' => 'integer',
            'sleep_hours' => 'integer',
            'stress_level' => 'integer',
            'weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'body_fat_percent' => 'decimal:1',
            'waist_cm' => 'decimal:2',
            'hip_cm' => 'decimal:2',
            'chest_cm' => 'decimal:2',
            'arm_cm' => 'decimal:2',
            'thigh_cm' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getGoalLabelAttribute(): string
    {
        return self::GOALS[$this->goal] ?? '—';
    }

    public function getExperienceLevelLabelAttribute(): string
    {
        return self::EXPERIENCE_LEVELS[$this->experience_level] ?? '—';
    }

    public function getAlcoholConsumptionLabelAttribute(): string
    {
        return self::ALCOHOL_CONSUMPTION[$this->alcohol_consumption] ?? '—';
    }

    public function getBmiAttribute(): ?float
    {
        $weight = (float) $this->weight_kg;
        $height = (float) $this->height_cm;

        if ($weight <= 0 || $height <= 0) {
            return null;
        }

        return round($weight / (($height / 100) ** 2), 1);
    }
}
