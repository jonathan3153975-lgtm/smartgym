<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Support\UnitPanelContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $unit = UnitPanelContext::unit($request);
        $units = UnitPanelContext::units();

        $query = Student::query()
            ->whereHas('units', fn ($builder) => $builder->where('unit_id', $unit->id))
            ->with([
                'account',
                'assessment',
                'units.unit:id,name',
                'subscriptions' => fn ($builder) => $builder->active(),
            ])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->input('access') === 'with') {
            $query->whereHas('account');
        } elseif ($request->input('access') === 'without') {
            $query->whereDoesntHave('account');
        } elseif ($request->input('access') === 'temporary') {
            $query->whereHas('account', fn ($builder) => $builder->where('must_change_password', true));
        }

        if ($request->input('assessment') === 'pending') {
            $query->whereDoesntHave('assessment');
        } elseif ($request->input('assessment') === 'alert') {
            $query->whereHas('assessment', fn ($builder) => $builder->where('parq_positive', true));
        } elseif ($request->input('assessment') === 'unreviewed') {
            $query->whereHas('assessment', fn ($builder) => $builder->whereNull('reviewed_at'));
        }

        if ($request->input('plan') === 'active') {
            $query->whereHas('subscriptions', fn ($builder) => $builder->active());
        } elseif ($request->input('plan') === 'none') {
            $query->whereDoesntHave('subscriptions', fn ($builder) => $builder->active());
        }

        $students = $query->paginate(20)->withQueryString();

        return view('pages.unit.students.index', compact('unit', 'units', 'students'));
    }
}
