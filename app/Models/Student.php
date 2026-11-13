<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'network_id',
        'name',
        'social_name',
        'cpf',
        'cpf_raw',
        'birth_date',
        'gender',
        'email',
        'phone',
        'whatsapp',
        'address',
        'zip_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'avatar',
        'admin_notes',
        'source',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'deleted_at' => 'datetime',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(StudentUnit::class);
    }

    public function primaryUnit(): HasMany
    {
        return $this->hasMany(StudentUnit::class)->where('is_primary', true);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(StudentContact::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(StudentAddress::class);
    }

    public function observations(): HasMany
    {
        return $this->hasMany(StudentObservation::class);
    }

    public function account(): HasOne
    {
        return $this->hasOne(StudentAccount::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(StudentSubscription::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(StudentAssessment::class)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');
    }

    public function assessment(): HasOne
    {
        return $this->hasOne(StudentAssessment::class)->latestOfMany();
    }

    public function workoutPlans(): HasMany
    {
        return $this->hasMany(WorkoutPlan::class);
    }

    public function workoutLogs(): HasMany
    {
        return $this->hasMany(WorkoutLog::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(BodyMeasurement::class)->orderByDesc('measured_at');
    }

    public function checkins(): HasMany
    {
        return $this->hasMany(StudentCheckin::class)->orderByDesc('checked_in_at');
    }

    public function classBookings(): HasMany
    {
        return $this->hasMany(ClassBooking::class)->orderByDesc('booked_for');
    }

    public function activeSubscription(): ?StudentSubscription
    {
        return $this->subscriptions()
            ->active()
            ->latest('starts_at')
            ->first();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        return route('student.avatar', ['student' => $this, 'v' => substr(md5($this->avatar), 0, 8)]);
    }

    public function getGenderLabelAttribute(): string
    {
        return match ($this->gender) {
            'male' => 'Masculino',
            'female' => 'Feminino',
            'other' => 'Outro',
            'prefer_not_to_say' => 'Prefiro não informar',
            default => '—',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'Ativo',
            'inactive' => 'Inativo',
            'pending' => 'Pendente',
            default => '—',
        };
    }

    public function getFormattedCpfAttribute(): ?string
    {
        if (! $this->cpf_raw) {
            return null;
        }

        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->cpf_raw);
    }

    public function getAgeAttribute(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return $this->birth_date->age;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
