<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'avatar'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function vinculos(): HasMany
    {
        return $this->hasMany(UserVinculo::class);
    }

    public function activeVinculos(): HasMany
    {
        return $this->vinculos()->where('status', 'active');
    }

    private ?UserVinculo $resolvedActiveVinculo = null;

    private bool $activeVinculoResolved = false;

    public function getActiveVinculo(): ?UserVinculo
    {
        if ($this->activeVinculoResolved) {
            return $this->resolvedActiveVinculo;
        }

        $this->resolvedActiveVinculo = $this->activeVinculos()
            ->with(['network', 'region', 'unit', 'role.permissions'])
            ->first();

        $this->activeVinculoResolved = true;

        return $this->resolvedActiveVinculo;
    }

    public function hasPermission(string $permissionKey): bool
    {
        $vinculo = $this->getActiveVinculo();

        if (! $vinculo?->role) {
            return false;
        }

        return $vinculo->role->hasPermission($permissionKey);
    }

    /**
     * @param  array<int, string>  $permissionKeys
     */
    public function hasAnyPermission(array $permissionKeys): bool
    {
        $vinculo = $this->getActiveVinculo();

        if (! $vinculo?->role) {
            return false;
        }

        $role = $vinculo->role;

        return $role->permissions->contains(
            fn ($permission) => in_array($permission->key, $permissionKeys, true)
        );
    }

    public function hasPermissionTo(string $permissionKey): bool
    {
        return $this->hasPermission($permissionKey);
    }
}
