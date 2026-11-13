<?php

namespace App\Http\Middleware;

use App\Models\Network;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentPortalNetwork
{
    /**
     * Resolve and validate the network from the portal URL.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $network = $request->route('network');

        abort_unless($network instanceof Network && $network->isActive(), 404);

        view()->share('portalNetwork', $network);

        return $next($request);
    }
}
