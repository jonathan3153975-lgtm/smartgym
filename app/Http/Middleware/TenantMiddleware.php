<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Get active vinculo from session or resolve from user's active vinculos
        $activeVinculoId = Session::get('active_vinculo_id');
        $vinculo = null;

        if ($activeVinculoId) {
            $vinculo = $user->vinculos()
                ->with(['network', 'region', 'unit', 'role'])
                ->where('id', $activeVinculoId)
                ->where('status', 'active')
                ->first();
        }

        // If no active vinculo in session, get the first active one
        if (! $vinculo) {
            $vinculo = $user->vinculos()
                ->with(['network', 'region', 'unit', 'role'])
                ->where('status', 'active')
                ->first();

            if ($vinculo) {
                Session::put('active_vinculo_id', $vinculo->id);
            }
        }

        if ($vinculo) {
            // Set session data for the active context
            Session::put('active_network', $vinculo->network);
            Session::put('active_region', $vinculo->region);
            Session::put('active_unit', $vinculo->unit);
            Session::put('active_role', $vinculo->role);
            Session::put('active_vinculo', $vinculo);

            // Share with views
            view()->share([
                'activeNetwork' => $vinculo->network,
                'activeRegion' => $vinculo->region,
                'activeUnit' => $vinculo->unit,
                'activeRole' => $vinculo->role,
            ]);
        }

        return $next($request);
    }
}
