<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SaasPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'limits',
        'features',
        'price',
        'billing_cycle',
        'status',
    ];

    protected $casts = [
        'limits' => 'array',
        'features' => 'array',
        'price' => 'decimal:2',
    ];

    public function networks(): HasMany
    {
        return $this->hasMany(Network::class, 'plan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(SaasSubscription::class);
    }

    public function parameters(): HasMany
    {
        return $this->hasMany(SaasPlanParameter::class, 'saas_plan_id')->orderBy('description');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getLimitsAttribute($value): array
    {
        return $this->decodeJsonArray($value);
    }

    public function setLimitsAttribute($value): void
    {
        $this->attributes['limits'] = is_array($value) ? json_encode($value) : $value;
    }

    public function getFeaturesAttribute($value): array
    {
        return $this->decodeJsonArray($value);
    }

    public function setFeaturesAttribute($value): void
    {
        $this->attributes['features'] = is_array($value) ? json_encode($value) : $value;
    }

    /**
     * Decode a JSON column that may have been encoded more than once.
     */
    private function decodeJsonArray(mixed $value): array
    {
        while (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            if ($decoded === null) {
                break;
            }

            $value = $decoded;
        }

        return is_array($value) ? $value : [];
    }
}
