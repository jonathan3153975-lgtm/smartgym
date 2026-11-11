<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'level',
        'description',
        'is_system',
        'network_id',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function userVinculos(): HasMany
    {
        return $this->hasMany(UserVinculo::class);
    }

    public function hasPermission(string $permissionKey): bool
    {
        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('key', $permissionKey);
        }

        return $this->permissions()->where('key', $permissionKey)->exists();
    }

    /**
     * Whether the given user can edit/delete this role.
     */
    public function canBeManagedBy(User $user): bool
    {
        if ($this->is_system) {
            return false;
        }

        if ($user->hasPermission('admin.access')) {
            return true;
        }

        return $this->network_id !== null
            && $this->network_id === $user->getActiveVinculo()?->network_id;
    }
}
