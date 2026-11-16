<?php

namespace App\Support\Settings;

use App\Models\DayPassPriceHistory;
use App\Models\Network;
use App\Models\Region;
use App\Models\Unit;
use App\Support\TenantContext;
use Illuminate\Support\Collection;

class DayPassPrice
{
    public const KEY = 'day_pass_price';

    /**
     * @return array{price: ?float, source: ?string, unit_price: ?float, region_price: ?float, network_price: ?float}
     */
    public function resolve(?Unit $unit): array
    {
        $network = $unit?->network;
        $region = $unit?->region;

        $unitPrice = $this->priceFrom($unit?->settings);
        $regionPrice = $this->priceFrom($region?->settings);
        $networkPrice = $this->priceFrom($network?->settings);

        if ($unitPrice !== null) {
            return [
                'price' => $unitPrice,
                'source' => 'unit',
                'unit_price' => $unitPrice,
                'region_price' => $regionPrice,
                'network_price' => $networkPrice,
            ];
        }

        if ($regionPrice !== null) {
            return [
                'price' => $regionPrice,
                'source' => 'region',
                'unit_price' => null,
                'region_price' => $regionPrice,
                'network_price' => $networkPrice,
            ];
        }

        return [
            'price' => $networkPrice,
            'source' => $networkPrice !== null ? 'network' : null,
            'unit_price' => null,
            'region_price' => $regionPrice,
            'network_price' => $networkPrice,
        ];
    }

    /**
     * @return array{price: ?float, source: ?string}
     */
    public function resolveForTenant(TenantContext $tenant): array
    {
        $network = $tenant->network();

        if (! $network) {
            return ['price' => null, 'source' => null];
        }

        $unit = $tenant->unitId() ? Unit::find($tenant->unitId()) : null;
        $region = $tenant->regionId() ? Region::find($tenant->regionId()) : null;

        if ($unit) {
            $resolved = $this->resolve($unit);

            return ['price' => $resolved['price'], 'source' => $resolved['source']];
        }

        if ($region) {
            $regionPrice = $this->priceFrom($region->settings);

            if ($regionPrice !== null) {
                return ['price' => $regionPrice, 'source' => 'region'];
            }

            $networkPrice = $this->priceFrom($network->settings);

            return ['price' => $networkPrice, 'source' => $networkPrice !== null ? 'network' : null];
        }

        $networkPrice = $this->priceFrom($network->settings);

        return ['price' => $networkPrice, 'source' => $networkPrice !== null ? 'network' : null];
    }

    public function setUnitPrice(Unit $unit, ?float $price, ?int $userId): void
    {
        $this->persist($unit, $price);

        DayPassPriceHistory::create([
            'network_id' => $unit->network_id,
            'unit_id' => $unit->id,
            'price' => $price,
            'changed_by' => $userId,
        ]);
    }

    public function setRegionPrice(Region $region, ?float $price, ?int $userId): void
    {
        $this->persist($region, $price);

        DayPassPriceHistory::create([
            'network_id' => $region->network_id,
            'region_id' => $region->id,
            'price' => $price,
            'changed_by' => $userId,
        ]);
    }

    public function setNetworkPrice(Network $network, ?float $price, ?int $userId): void
    {
        $this->persist($network, $price);

        DayPassPriceHistory::create([
            'network_id' => $network->id,
            'price' => $price,
            'changed_by' => $userId,
        ]);
    }

    /**
     * @return Collection<int, DayPassPriceHistory>
     */
    public function historyForUnit(Unit $unit): Collection
    {
        return DayPassPriceHistory::query()
            ->where('network_id', $unit->network_id)
            ->where(function ($query) use ($unit) {
                $query->where('unit_id', $unit->id);

                if ($unit->region_id) {
                    $query->orWhere(fn ($regionQuery) => $regionQuery
                        ->whereNull('unit_id')
                        ->where('region_id', $unit->region_id));
                }

                $query->orWhere(fn ($networkQuery) => $networkQuery
                    ->whereNull('unit_id')
                    ->whereNull('region_id'));
            })
            ->with('changedBy')
            ->latest()
            ->limit(30)
            ->get();
    }

    /**
     * @return Collection<int, DayPassPriceHistory>
     */
    public function historyForRegion(Region $region): Collection
    {
        return DayPassPriceHistory::query()
            ->where('network_id', $region->network_id)
            ->where(function ($query) use ($region) {
                $query->where(fn ($regionQuery) => $regionQuery
                    ->whereNull('unit_id')
                    ->where('region_id', $region->id))
                    ->orWhere(fn ($networkQuery) => $networkQuery
                        ->whereNull('unit_id')
                        ->whereNull('region_id'));
            })
            ->with('changedBy')
            ->latest()
            ->limit(30)
            ->get();
    }

    private function persist(Unit|Region|Network $model, ?float $price): void
    {
        $settings = $model->settings;

        if ($price === null) {
            unset($settings[self::KEY]);
        } else {
            $settings[self::KEY] = round($price, 2);
        }

        $model->update(['settings' => $settings]);
    }

    /**
     * @param  array<string, mixed>|null  $settings
     */
    private function priceFrom(?array $settings): ?float
    {
        if (! is_array($settings) || ! array_key_exists(self::KEY, $settings)) {
            return null;
        }

        $value = $settings[self::KEY];

        return $value === null || $value === '' ? null : (float) $value;
    }
}
