<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentAssessment;
use App\Support\UnitPanelContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function index(Request $request): View
    {
        $unit = UnitPanelContext::unit($request);
        $units = UnitPanelContext::units();

        $query = Student::query()
            ->whereHas('units', fn ($builder) => $builder->where('unit_id', $unit->id))
            ->with(['assessment.reviewedBy:id,name', 'assessment', 'account'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        $filter = $request->string('filter')->toString();

        if ($filter === 'pending') {
            $query->whereDoesntHave('assessment');
        } elseif ($filter === 'unreviewed') {
            $query->whereHas('assessment', fn ($builder) => $builder->whereNull('reviewed_at'));
        } elseif ($filter === 'reviewed') {
            $query->whereHas('assessment', fn ($builder) => $builder->whereNotNull('reviewed_at'));
        } elseif ($filter === 'alert') {
            $query->whereHas('assessment', fn ($builder) => $builder->where('parq_positive', true));
        } elseif ($filter === 'done') {
            $query->whereHas('assessment');
        }

        $students = $query->paginate(20)->withQueryString();

        return view('pages.unit.assessments.index', compact('unit', 'units', 'students', 'filter'));
    }

    public function review(Request $request, StudentAssessment $assessment): RedirectResponse
    {
        $unit = UnitPanelContext::unit($request);

        $belongsToUnit = $assessment->student()
            ->whereHas('units', fn ($builder) => $builder->where('unit_id', $unit->id))
            ->exists();

        abort_unless($belongsToUnit, 404);

        if ($assessment->reviewed_at) {
            $assessment->update([
                'reviewed_at' => null,
                'reviewed_by' => null,
            ]);

            $message = 'Verificação da ficha removida.';
        } else {
            $assessment->update([
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            $message = 'Ficha verificada com sucesso.';
        }

        return redirect()->back()->with('success', $message);
    }
}
