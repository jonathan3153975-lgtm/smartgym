<?php

namespace App\Http\Middleware;

use App\Models\Network;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class StudentPortalAccess
{
    /**
     * Ensure the authenticated student account belongs to the portal network.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Network|null $network */
        $network = $request->route('network');

        if (! Auth::guard('student')->check()) {
            return redirect()->route('student.portal.login', $network);
        }

        $account = Auth::guard('student')->user();

        if (! $network || (int) $account->network_id !== (int) $network->id) {
            Auth::guard('student')->logout();

            return redirect()
                ->route('student.portal.login', $network)
                ->withErrors(['email' => 'Faça login novamente para acessar esta rede.']);
        }

        if ($account->must_change_password
            && ! $request->routeIs('student.portal.password.*')
            && ! $request->routeIs('student.portal.logout')) {
            return redirect()->route('student.portal.password.edit', $network);
        }

        view()->share('studentAccount', $account);

        return $next($request);
    }
}
