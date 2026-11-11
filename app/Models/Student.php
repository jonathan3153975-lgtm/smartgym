<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
