<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index(Request $request): View
    {
        // Mock stats for now - will be replaced with real data in Phase 2
        $stats = [
            'total_students' => 0,
            'active_students' => 0,
            'inactive_students' => 0,
            'new_this_month' => 0,
        ];

        // Mock recent students - will be replaced with real data in Phase 2
        $recentStudents = collect();

        return view('pages.dashboard', compact('stats', 'recentStudents'));
    }
}
