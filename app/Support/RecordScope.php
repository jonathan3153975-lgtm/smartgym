<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Controls visibility and management of records created at different
 * organizational levels (network, region or unit).
 */
class RecordScope
{
    public const LEVELS = ['network', 'region', 'unit'];

    public static function level(TenantContext $tenant): string
    {
        return $tenant->level();
    }

    /**
     * Apply the visibility rules for the current user.
     */
    public static function query(Builder $query, TenantContext $tenant): Builder
    {
        if ($tenant->isPlatform()) {
            return $query;
        }

        $networkId = $tenant->networkId();

        if (! $networkId) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('network_id', $networkId);

        if ($unitId = $tenant->unitId()) {
            $regionId = $tenant->vinculo()?->unit?->region_id;

            return $query->where(function ($inner) use ($unitId, $regionId) {
                $inner->where('created_level', 'network')
                    ->orWhere(fn ($unitQuery) => $unitQuery
                        ->where('created_level', 'unit')
                        ->where('unit_id', $unitId));

                if ($regionId) {
                    $inner->orWhere(fn ($regionQuery) => $regionQuery
                        ->where('created_level', 'region')
                        ->where('region_id', $regionId));
                }
            });
        }

        if ($regionId = $tenant->regionId()) {
            return $query->where(function ($inner) use ($regionId) {
                $inner->where('created_level', 'network')
                    ->orWhere(fn ($regionQuery) => $regionQuery
                        ->where('created_level', 'region')
                        ->where('region_id', $regionId))
                    ->orWhere(fn ($unitQuery) => $unitQuery
                        ->where('created_level', 'unit')
                        ->whereHas('unit', fn ($relation) => $relation->where('region_id', $regionId)));
            });
        }

        return $query;
    }

    /**
     * Only users at the same organizational level that created the record
     * (and within the same scope) may edit or delete it.
     */
    public static function canManage(Model $record, TenantContext $tenant): bool
    {
        if ($tenant->isPlatform()) {
            return true;
        }

        if ((int) $record->network_id !== (int) $tenant->networkId()) {
            return false;
        }

        $level = $tenant->level();

        if ($record->created_level !== $level) {
            return false;
        }

        return match ($level) {
            'region' => (int) $record->region_id === (int) $tenant->regionId(),
            'unit' => (int) $record->unit_id === (int) $tenant->unitId(),
            default => true,
        };
    }

    /**
     * Attributes stamped on a record when it is created.
     *
     * @return array<string, mixed>
     */
    public static function creationAttributes(TenantContext $tenant): array
    {
        $level = $tenant->level();
        $regionId = $tenant->regionId();

        if ($level === 'unit' && ! $regionId) {
            $regionId = $tenant->vinculo()?->unit?->region_id;
        }

        return [
            'network_id' => $tenant->networkId(),
            'region_id' => $level === 'network' ? null : $regionId,
            'unit_id' => $level === 'unit' ? $tenant->unitId() : null,
            'created_level' => $level,
        ];
    }

    public static function levelLabel(?string $level): string
    {
        return match ($level) {
            'network' => 'Rede',
            'region' => 'Região',
            'unit' => 'Unidade',
            default => '—',
        };
    }
}
