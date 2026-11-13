<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentCheckin;
use App\Support\UnitPanelContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessController extends Controller
{
    public function index(Request $request): View
    {
        $unit = UnitPanelContext::unit($request);
        $units = UnitPanelContext::units();

        $studentsQuery = Student::query()
            ->whereHas('units', fn ($builder) => $builder->where('unit_id', $unit->id));

        $query = (clone $studentsQuery)
            ->with(['account', 'assessment'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $access = $request->string('access')->toString();

        if ($access === 'without') {
            $query->whereDoesntHave('account');
        } elseif ($access === 'with') {
            $query->whereHas('account', fn ($builder) => $builder->where('status', 'active'));
        } elseif ($access === 'temporary') {
            $query->whereHas('account', fn ($builder) => $builder->where('must_change_password', true));
        } elseif ($access === 'inactive') {
            $query->whereHas('account', fn ($builder) => $builder->where('status', 'inactive'));
        }

        $students = $query->paginate(20)->withQueryString();

        $metrics = [
            'total' => (clone $studentsQuery)->count(),
            'with_access' => (clone $studentsQuery)->whereHas('account', fn ($builder) => $builder->where('status', 'active'))->count(),
            'without_access' => (clone $studentsQuery)->whereDoesntHave('account')->count(),
            'temporary' => (clone $studentsQuery)->whereHas('account', fn ($builder) => $builder->where('must_change_password', true))->count(),
            'inactive' => (clone $studentsQuery)->whereHas('account', fn ($builder) => $builder->where('status', 'inactive'))->count(),
            'trials' => StudentCheckin::query()
                ->where('unit_id', $unit->id)
                ->where('type', StudentCheckin::TYPE_TRIAL)
                ->where('checked_in_at', '>=', now()->startOfMonth())
                ->count(),
        ];

        $recentCheckins = StudentCheckin::query()
            ->where('unit_id', $unit->id)
            ->with('student:id,name,email')
            ->orderByDesc('checked_in_at')
            ->take(15)
            ->get();

        return view('pages.unit.access.index', compact(
            'unit',
            'units',
            'students',
            'metrics',
            'recentCheckins',
            'access'
        ));
    }
}
