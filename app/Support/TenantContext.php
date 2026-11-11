<?php

namespace App\Support;

use App\Models\Network;
use App\Models\Permission;
use App\Models\Region;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserVinculo;
use Illuminate\Database\Eloquent\Builder;

class TenantContext
{
    private ?UserVinculo $vinculo = null;

    private bool $resolved = false;

    public function __construct(private readonly User $user) {}

    public static function current(): self
    {
        return new self(auth()->user());
    }

    public function user(): User
    {
        return $this->user;
    }

    public function vinculo(): ?UserVinculo
    {
        if (! $this->resolved) {
            $this->vinculo = $this->user->getActiveVinculo();
            $this->resolved = true;
        }

        return $this->vinculo;
    }

    public function isPlatform(): bool
    {
        return $this->vinculo()?->role?->level === 'platform';
    }

    public function networkId(): ?int
    {
        return $this->isPlatform() ? null : $this->vinculo()?->network_id;
    }

    public function regionId(): ?int
    {
        return $this->isPlatform() ? null : $this->vinculo()?->region_id;
    }

    public function unitId(): ?int
    {
        return $this->isPlatform() ? null : $this->vinculo()?->unit_id;
    }

    public function network(): ?Network
    {
        return $this->isPlatform() ? null : $this->vinculo()?->network;
    }

    /**
     * Apply the network scope to a query.
     */
    public function scopeNetworks(Builder $query, string $column = 'id'): Builder
    {
        if ($this->isPlatform()) {
            return $query;
        }

        $networkId = $this->networkId();

        return $networkId
            ? $query->where($column, $networkId)
            : $query->whereRaw('1 = 0');
    }

    /**
     * Apply the network (and region, when present) scope to a query.
     */
    public function scopeRegions(Builder $query, string $column = 'network_id'): Builder
    {
        if ($this->isPlatform()) {
            return $query;
        }

        $networkId = $this->networkId();

        if (! $networkId) {
            return $query->whereRaw('1 = 0');
        }

        $query->where($column, $networkId);

        if ($regionId = $this->regionId()) {
            $query->whereKey($regionId);
        }

        return $query;
    }

    /**
     * Apply the network/region/unit scope to a query.
     */
    public function scopeUnits(Builder $query): Builder
    {
        if ($this->isPlatform()) {
            return $query;
        }

        $networkId = $this->networkId();

        if (! $networkId) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('network_id', $networkId);

        if ($unitId = $this->unitId()) {
            return $query->whereKey($unitId);
        }

        if ($regionId = $this->regionId()) {
            $query->where('region_id', $regionId);
        }

        return $query;
    }

    /**
     * Apply the organizational scope to a user query.
     */
    public function scopeUsers(Builder $query): Builder
    {
        if ($this->isPlatform()) {
            return $query;
        }

        $networkId = $this->networkId();

        if (! $networkId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('vinculos', function ($vinculoQuery) use ($networkId) {
            $vinculoQuery->where('network_id', $networkId);

            if ($regionId = $this->regionId()) {
                $vinculoQuery->where('region_id', $regionId);
            }

            if ($unitId = $this->unitId()) {
                $vinculoQuery->where('unit_id', $unitId);
            }
        });
    }

    /**
     * Apply the scope to a user vinculo query.
     */
    public function scopeUserVinculos(Builder $query): Builder
    {
        if ($this->isPlatform()) {
            return $query;
        }

        $networkId = $this->networkId();

        if (! $networkId) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('network_id', $networkId);

        if ($regionId = $this->regionId()) {
            $query->where('region_id', $regionId);
        }

        if ($unitId = $this->unitId()) {
            $query->where('unit_id', $unitId);
        }

        return $query;
    }

    /**
     * Roles visible to the current user: system roles plus roles from their network.
     */
    public function scopeRoles(Builder $query): Builder
    {
        if ($this->isPlatform()) {
            return $query;
        }

        $networkId = $this->networkId();

        return $query->where(function ($roleQuery) use ($networkId) {
            $roleQuery->whereNull('network_id');

            if ($networkId) {
                $roleQuery->orWhere('network_id', $networkId);
            }
        });
    }

    /**
     * Roles that can be assigned to users managed by the current user.
     */
    public function roleOptionsQuery(): Builder
    {
        $query = Role::query();

        if ($this->isPlatform()) {
            return $query;
        }

        $networkId = $this->networkId();

        return $query
            ->where('level', '!=', 'platform')
            ->where(function ($roleQuery) use ($networkId) {
                $roleQuery->whereNull('network_id')
                    ->when($networkId, fn ($inner) => $inner->orWhere('network_id', $networkId));
            });
    }

    /**
     * Permissions that the current user can delegate to roles.
     */
    public function permissionOptionsQuery(): Builder
    {
        $query = Permission::query();

        if ($this->isPlatform()) {
            return $query;
        }

        return $query->whereNotIn('key', [
            'admin.access',
            'audit.view',
            'networks.manage',
            'networks.view',
            'networks.create',
            'networks.edit',
            'networks.delete',
            'plans.manage',
            'users.impersonate',
        ]);
    }

    public function networksQuery(): Builder
    {
        return $this->scopeNetworks(Network::query())->orderBy('name');
    }

    public function regionsQuery(): Builder
    {
        return $this->scopeRegions(Region::query())->orderBy('name');
    }

    public function unitsQuery(): Builder
    {
        return $this->scopeUnits(Unit::query())->orderBy('name');
    }

    public function canAccessNetwork(Network $network): bool
    {
        return $this->isPlatform() || $network->id === $this->networkId();
    }

    public function canAccessRegion(Region $region): bool
    {
        if ($this->isPlatform()) {
            return true;
        }

        if ($region->network_id !== $this->networkId() || $this->unitId()) {
            return false;
        }

        if ($regionId = $this->regionId()) {
            return $region->id === $regionId;
        }

        return true;
    }

    public function canAccessUnit(Unit $unit): bool
    {
        if ($this->isPlatform()) {
            return true;
        }

        if ($unit->network_id !== $this->networkId()) {
            return false;
        }

        if ($unitId = $this->unitId()) {
            return $unit->id === $unitId;
        }

        if ($regionId = $this->regionId()) {
            return $unit->region_id === $regionId;
        }

        return true;
    }

    public function canAccessUser(User $user): bool
    {
        if ($this->isPlatform()) {
            return true;
        }

        return $this->scopeUserVinculos($user->vinculos()->getQuery())->exists();
    }

    public function canDeleteUser(User $user): bool
    {
        if ($this->isPlatform()) {
            return true;
        }

        $vinculos = $user->vinculos()->with('role')->get();

        return $vinculos->isNotEmpty()
            && $vinculos->every(fn (UserVinculo $vinculo) => $vinculo->network_id === $this->networkId()
                && $vinculo->role?->level !== 'platform');
    }

    public function canManageRole(Role $role): bool
    {
        return $this->isPlatform()
            || (! $role->is_system && $role->network_id === $this->networkId());
    }

    public function canViewRole(Role $role): bool
    {
        return $this->isPlatform()
            || $role->network_id === null
            || $role->network_id === $this->networkId();
    }
}
