<?php

namespace App\Http\Controllers;

use App\Support\Dashboard\DashboardMetrics;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard for the current access level.
     */
    public function index(Request $request): View
    {
        return view('pages.dashboard', (new DashboardMetrics($request, TenantContext::current()))->build());
    }
}
