<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Network;
use App\Models\Region;
use App\Models\Role;
use App\Models\SaasPlan;
use App\Models\Unit;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the administrative overview for the current scope.
     */
    public function __invoke(): View
    {
        $tenant = TenantContext::current();

        return view('pages.admin.dashboard', [
            'networksCount' => $tenant->scopeNetworks(Network::query())->count(),
            'regionsCount' => $tenant->scopeRegions(Region::query())->count(),
            'unitsCount' => $tenant->scopeUnits(Unit::query())->count(),
            'usersCount' => $tenant->scopeUsers(User::query())->count(),
            'rolesCount' => $tenant->scopeRoles(Role::query())->count(),
            'plansCount' => $tenant->isPlatform() ? SaasPlan::count() : 0,
            'managementNetwork' => $tenant->network(),
        ]);
    }
}
