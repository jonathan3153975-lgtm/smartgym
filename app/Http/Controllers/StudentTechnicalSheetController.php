<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\WorkoutPlan;
use App\Support\StudentBodySummary;
use App\Support\StudentWorkoutOverview;
use App\Support\TenantContext;
use App\Support\WorkoutPlanCopier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentTechnicalSheetController extends Controller
{
    public function __construct(private readonly WorkoutPlanCopier $copier) {}

    public function show(Student $student): View
    {
        $tenant = $this->tenant();

        abort_unless($tenant->canAccessStudent($student), 404);

        $student->load(['assessment', 'assessments', 'measurements']);

        $overview = StudentWorkoutOverview::for($student);
        $bodySummary = StudentBodySummary::for($student);

        return view('pages.students.technical-sheet', [
            'student' => $student,
            'summary' => $overview['summary'],
            'muscleGroups' => $overview['muscle_groups'],
            'muscleGroupExercises' => $overview['muscle_group_exercises'],
            'weeklyFrequency' => $overview['weekly_frequency'],
            'topExercises' => $overview['top_exercises'],
            'measurements' => $student->measurements->take(8),
            'assessmentMetrics' => $bodySummary['metrics'],
            'bodySummary' => $bodySummary,
        ]);
    }

    public function workouts(Student $student): View
    {
        $tenant = $this->tenant();

        abort_unless($tenant->canAccessStudent($student), 404);

        $overview = StudentWorkoutOverview::for($student);

        $appliedPlans = $student->workoutPlans()
            ->withCount('sessions')
            ->orderByDesc('created_at')
            ->get();

        $activePlan = $student->workoutPlans()
            ->where('status', WorkoutPlan::STATUS_ACTIVE)
            ->withCount('sessions')
            ->latest('created_at')
            ->first();

        $publicPlans = WorkoutPlan::query()
            ->where('network_id', $tenant->networkId())
            ->whereNull('student_id')
            ->where('status', WorkoutPlan::STATUS_ACTIVE)
            ->where('visibility', WorkoutPlan::VISIBILITY_PUBLIC)
            ->withCount('sessions')
            ->orderByDesc('copies_count')
            ->orderBy('name')
            ->get();

        return view('pages.students.workouts', [
            'student' => $student,
            'summary' => $overview['summary'],
            'recentLogs' => $overview['recent_logs'],
            'muscleGroups' => $overview['muscle_groups'],
            'muscleGroupExercises' => $overview['muscle_group_exercises'],
            'appliedPlans' => $appliedPlans,
            'activePlan' => $activePlan,
            'publicPlans' => $publicPlans,
        ]);
    }

    public function applyPlan(Request $request, Student $student): RedirectResponse
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessStudent($student), 404);

        $validated = $request->validate([
            'workout_plan_id' => [
                'required',
                'integer',
                Rule::exists('workout_plans', 'id')
                    ->where('network_id', $tenant->networkId())
                    ->whereNull('student_id')
                    ->where('visibility', WorkoutPlan::VISIBILITY_PUBLIC)
                    ->where('status', WorkoutPlan::STATUS_ACTIVE)
                    ->whereNull('deleted_at'),
            ],
        ]);

        $source = WorkoutPlan::findOrFail($validated['workout_plan_id']);

        $plan = $this->copier->copy($source, [
            'student_id' => $student->id,
            'unit_id' => $this->primaryUnitId($student),
            'visibility' => WorkoutPlan::VISIBILITY_PRIVATE,
            'status' => WorkoutPlan::STATUS_ACTIVE,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->copier->activateForStudent($plan);

        return redirect()
            ->route('admin.workout-plans.builder', $plan)
            ->with('success', 'Treino aplicado ao aluno. Ajuste o que precisar.');
    }

    public function storePlan(Request $request, Student $student): RedirectResponse
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessStudent($student), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $plan = WorkoutPlan::create([
            'network_id' => $tenant->networkId(),
            'unit_id' => $this->primaryUnitId($student),
            'student_id' => $student->id,
            'name' => $validated['name'],
            'objective' => $validated['objective'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'visibility' => WorkoutPlan::VISIBILITY_PRIVATE,
            'status' => WorkoutPlan::STATUS_ACTIVE,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $this->copier->activateForStudent($plan);

        return redirect()
            ->route('admin.workout-plans.builder', $plan)
            ->with('success', 'Treino personalizado criado. Adicione as sessões e exercícios.');
    }

    private function primaryUnitId(Student $student): ?int
    {
        $studentUnit = $student->units()->where('is_primary', true)->first()
            ?? $student->units()->first();

        return $studentUnit?->unit_id;
    }

    private function tenant(): TenantContext
    {
        $tenant = TenantContext::current();

        abort_if($tenant->isPlatform(), 403, 'Selecione uma rede para acessar a ficha técnica.');
        abort_unless($tenant->networkId(), 403, 'Selecione uma rede para acessar a ficha técnica.');

        return $tenant;
    }
}
