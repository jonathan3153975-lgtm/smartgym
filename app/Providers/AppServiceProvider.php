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
        Gate::define('admin.manage', function (User $user) {
            return $user->hasAnyPermission([
                'admin.access',
                'network.settings',
                'regions.manage',
                'units.manage',
                'people.view',
                'people.manage',
                'users.manage',
                'roles.manage',
                'plans.manage',
            ]);
        });
    }
}
