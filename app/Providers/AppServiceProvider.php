<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register a gate that uses the user's hasPermission method
        Gate::before(function ($user, $ability) {
            if ($user instanceof User && method_exists($user, 'hasPermission')) {
                return $user->hasPermission($ability) ? true : null;
            }

            return null;
        });

        // Access to the management area: platform operators and network/region/unit managers.
        // Operational panel for unit-level staff (network/region managers may select a unit).
        Gate::define('unit.panel', function (User $user) {
            $vinculo = $user->getActiveVinculo();

            return $vinculo
                && $vinculo->network_id
                && $vinculo->role?->level !== 'platform'
                && $user->hasPermission('students.view');
        });

        Gate::define('admin.manage', function (User $user) {
            return $user->hasAnyPermission([
                'admin.access',
                'network.settings',
                'regions.manage',
                'units.manage',
                'people.view',
                'people.manage',
                'modalities.view',
                'modalities.manage',
                'membership_plans.view',
                'classes.manage',
                'exercises.manage',
                'workouts.manage',
                'users.manage',
                'roles.manage',
                'plans.manage',
            ]);
        });
    }
}
