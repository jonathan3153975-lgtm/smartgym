<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Student;
use App\Models\WorkoutLog;
use App\Models\WorkoutPlan;
use App\Support\StudentActivitySummary;
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
            'frequencySummary' => $overview['frequency_summary'],
            'lastFourWeeksLogs' => $overview['last_four_weeks_logs'],
            'workoutExtracts' => $overview['workout_extracts'],
            'assessmentMetrics' => $bodySummary['metrics'],
            'bodySummary' => $bodySummary,
            'assessment' => $bodySummary['current'],
            'previousAssessment' => $bodySummary['previous'],
            'assessmentHistory' => $student->assessments,
        ]);
    }

    public function printReport(Student $student): View
    {
        $tenant = $this->tenant();

        abort_unless($tenant->canAccessStudent($student), 404);

        $student->load(['assessment', 'assessments', 'measurements', 'network']);

        $bodySummary = StudentBodySummary::for($student);
        $current = $bodySummary['current'];
        $previous = $bodySummary['previous'];
        $periodEnd = $current?->submitted_at ?? now();
        $periodStart = $previous?->submitted_at ?? $periodEnd->copy()->subDays(30);

        return view('pages.students.technical-sheet-print', [
            'student' => $student,
            'bodySummary' => $bodySummary,
            'activity' => StudentActivitySummary::for($student, $periodStart, $periodEnd),
            'assessmentHistory' => $student->assessments,
        ]);
    }

    public function workouts(Student $student): View
    {
        $tenant = $this->tenant();

        abort_unless($tenant->canAccessStudent($student), 404);

        $overview = StudentWorkoutOverview::for($student);

        $appliedPlans = $student->workoutPlans()
            ->with(['sessions.exercises.exercise'])
            ->withCount('sessions')
            ->orderByDesc('created_at')
            ->get();

        $activePlan = $student->workoutPlans()
            ->where('status', WorkoutPlan::STATUS_ACTIVE)
            ->with(['sessions.exercises.exercise'])
            ->withCount('sessions')
            ->latest('created_at')
            ->first();

        $completedBySession = $student->workoutLogs()
            ->where('status', WorkoutLog::STATUS_COMPLETED)
            ->whereNotNull('workout_session_id')
            ->selectRaw('workout_session_id, count(*) as total')
            ->groupBy('workout_session_id')
            ->pluck('total', 'workout_session_id');

        $planSessions = $activePlan
            ? $activePlan->sessions->map(function ($session) use ($completedBySession) {
                $exercises = $session->exercises
                    ->map(fn ($exercise) => [
                        'name' => $exercise->exercise_name,
                        'muscle_group' => $exercise->muscle_group,
                        'muscle_group_label' => Exercise::MUSCLE_GROUPS[$exercise->muscle_group] ?? $exercise->muscle_group,
                        'sets' => $exercise->sets,
                        'reps' => $exercise->reps,
                        'rest_seconds' => $exercise->rest_seconds,
                        'suggested_load' => $exercise->suggested_load,
                        'notes' => $exercise->notes,
                        'image_url' => $exercise->exercise?->image_url,
                    ])
                    ->values()
                    ->all();

                $totalExercises = max(1, count($exercises));

                $muscleGroups = collect($exercises)
                    ->groupBy('muscle_group')
                    ->map(fn ($items, $group) => [
                        'group' => $group,
                        'label' => Exercise::MUSCLE_GROUPS[$group] ?? $group,
                        'exercises' => $items->count(),
                        'percent' => (int) round(($items->count() / $totalExercises) * 100),
                    ])
                    ->sortByDesc('exercises')
                    ->values()
                    ->all();

                return [
                    'id' => $session->id,
                    'name' => $session->name,
                    'day_label' => $session->day_label,
                    'exercise_count' => count($exercises),
                    'completed_count' => (int) ($completedBySession[$session->id] ?? 0),
                    'exercises' => $exercises,
                    'muscle_groups' => $muscleGroups,
                ];
            })->sortByDesc('completed_count')->values()->all()
            : [];

        $allPlanExercises = collect($planSessions)
            ->flatMap(fn (array $session) => collect($session['exercises'])
                ->map(fn (array $exercise) => array_merge($exercise, ['session' => $session['name']])))
            ->values();

        $totalPlanExercises = max(1, $allPlanExercises->count());

        $planMuscleGroups = $allPlanExercises
            ->groupBy('muscle_group')
            ->map(fn ($items, $group) => [
                'group' => $group,
                'label' => Exercise::MUSCLE_GROUPS[$group] ?? $group,
                'exercises' => $items->count(),
                'percent' => (int) round(($items->count() / $totalPlanExercises) * 100),
            ])
            ->sortByDesc('exercises')
            ->values()
            ->all();

        $planMuscleGroupExercises = $allPlanExercises
            ->groupBy('muscle_group')
            ->map(fn ($items) => $items->map(fn (array $exercise) => [
                'name' => $exercise['name'],
                'session' => $exercise['session'],
                'sets' => $exercise['sets'],
                'reps' => $exercise['reps'],
                'suggested_load' => $exercise['suggested_load'],
            ])->values()->all())
            ->all();

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
            'exerciseProgress' => $overview['exercise_progress'],
            'overallEvolution' => $overview['overall_evolution'],
            'workoutExtracts' => $overview['workout_extracts'],
            'appliedPlans' => $appliedPlans,
            'activePlan' => $activePlan,
            'planSessions' => $planSessions,
            'planMuscleGroups' => $planMuscleGroups,
            'planMuscleGroupExercises' => $planMuscleGroupExercises,
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
            ->route('admin.workout-plans.builder', ['workoutPlan' => $plan, 'from' => 'student'])
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
            ->route('admin.workout-plans.builder', ['workoutPlan' => $plan, 'from' => 'student'])
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
