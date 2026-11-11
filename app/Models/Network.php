<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Network extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'document',
        'zip_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'responsible_name',
        'responsible_cpf',
        'responsible_email',
        'responsible_phone',
        'status',
        'plan_id',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'deleted_at' => 'datetime',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SaasPlan::class, 'plan_id');
    }

    public function subscription(): HasMany
    {
        return $this->hasMany(SaasSubscription::class);
    }

    public function regions(): HasMany
    {
        return $this->hasMany(Region::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(UserVinculo::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getRegionsCountAttribute(): int
    {
        return $this->regions()->count();
    }

    public function getUnitsCountAttribute(): int
    {
        return $this->units()->count();
    }

    public function getUsersCountAttribute(): int
    {
        return $this->users()->count();
    }

    public function getSubscriptionsCountAttribute(): int
    {
        return $this->subscription()->count();
    }

    public function getSettingsAttribute($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setSettingsAttribute($value): void
    {
        $this->attributes['settings'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getFullAddressAttribute(): string
    {
        $street = trim(collect([$this->street, $this->number])->filter()->implode(', '));
        $city = trim(collect([$this->city, $this->state])->filter()->implode(' - '));

        $parts = array_filter([
            $street,
            $this->complement,
            $this->neighborhood,
            $city,
            $this->zip_code ? 'CEP '.$this->zip_code : null,
        ]);

        return implode(', ', $parts);
    }
}
