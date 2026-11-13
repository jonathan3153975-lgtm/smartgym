<?php

namespace App\Models;

use App\Support\RecordScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MembershipPlan extends Model
{
    use HasFactory, SoftDeletes;

    public const PERIODS = [
        'daily' => 1,
        'monthly' => 30,
        'quarterly' => 90,
        'semiannual' => 180,
        'annual' => 365,
    ];

    public const PERIOD_LABELS = [
        'daily' => 'Diária',
        'monthly' => 'Mensal',
        'quarterly' => 'Trimestral',
        'semiannual' => 'Semestral',
        'annual' => 'Anual',
    ];

    protected $fillable = [
        'network_id',
        'region_id',
        'unit_id',
        'created_level',
        'name',
        'description',
        'price',
        'period',
        'duration_days',
        'weekly_accesses',
        'daily_checkins',
        'all_units',
        'all_modalities',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'all_units' => 'boolean',
        'all_modalities' => 'boolean',
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

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'membership_plan_units')->withTimestamps();
    }

    public function modalities(): BelongsToMany
    {
        return $this->belongsToMany(Modality::class, 'membership_plan_modalities')->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getCreatedLevelLabelAttribute(): string
    {
        return RecordScope::levelLabel($this->created_level);
    }

    public function getPeriodLabelAttribute(): string
    {
        return self::PERIOD_LABELS[$this->period] ?? $this->period;
    }

    public function getUnitAccessLabelAttribute(): string
    {
        if ($this->all_units) {
            return 'Todas as unidades';
        }

        return $this->units->count().' unidade(s)';
    }

    public function getModalityAccessLabelAttribute(): string
    {
        if ($this->all_modalities) {
            return 'Todas as modalidades';
        }

        return $this->modalities->count().' modalidade(s)';
    }

    public static function durationForPeriod(string $period): int
    {
        return self::PERIODS[$period] ?? 30;
    }

    /**
     * Plans a student linked to the given unit may subscribe to, respecting
     * the level the plan was created at and the "all units" flag.
     */
    public function scopeEligibleForUnit(Builder $query, Unit $unit): Builder
    {
        return $query
            ->where('network_id', $unit->network_id)
            ->where(function (Builder $level) use ($unit) {
                $level->where('created_level', 'network')
                    ->orWhere(fn (Builder $region) => $region
                        ->where('created_level', 'region')
                        ->where('region_id', $unit->region_id))
                    ->orWhere(fn (Builder $unitQuery) => $unitQuery
                        ->where('created_level', 'unit')
                        ->where('unit_id', $unit->id));
            })
            ->where(function (Builder $scope) use ($unit) {
                $scope->where('all_units', true)
                    ->orWhereHas('units', fn (Builder $units) => $units->where('units.id', $unit->id));
            });
    }

    /**
     * Units included in the plan scope when "all units" is selected.
     */
    public function availableUnitsQuery()
    {
        $query = Unit::query()->where('network_id', $this->network_id);

        return match ($this->created_level) {
            'region' => $query->where('region_id', $this->region_id),
            'unit' => $query->whereKey($this->unit_id),
            default => $query,
        };
    }
}
