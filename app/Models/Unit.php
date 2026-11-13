<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Unit extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'network_id',
        'region_id',
        'name',
        'identifier',
        'address',
        'zip_code',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'phone',
        'email',
        'status',
        'latitude',
        'longitude',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
        'deleted_at' => 'datetime',
    ];

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function userVinculos(): HasMany
    {
        return $this->hasMany(UserVinculo::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(StudentUnit::class);
    }

    public function paymentGatewaySettings(): HasMany
    {
        return $this->hasMany(PaymentGatewaySetting::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
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

    public function getSettingsAttribute($value): array
    {
        return $value ? json_decode($value, true) : [];
    }

    public function setSettingsAttribute($value): void
    {
        $this->attributes['settings'] = is_array($value) ? json_encode($value) : $value;
    }
}
