<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Student;
use App\Models\WorkoutLog;
use App\Models\WorkoutPlan;
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

        $student->load(['assessment', 'measurements']);

        $logs = $student->workoutLogs()
            ->where('status', WorkoutLog::STATUS_COMPLETED)
            ->with('sets')
            ->orderByDesc('started_at')
            ->get();

        $allSets = $logs->flatMap->sets;

        $summary = [
            'workouts' => $logs->count(),
            'sets' => $allSets->count(),
            'reps' => (int) $allSets->sum(fn ($set) => (int) $set->reps),
            'volume' => round($allSets->sum(fn ($set) => ((int) $set->reps) * ((float) $set->weight_kg)), 2),
            'last_workout' => $logs->first()?->started_at,
            'last_30' => $logs->filter(fn (WorkoutLog $log) => $log->started_at->gte(now()->subDays(30)))->count(),
        ];

        $volumeByMuscle = $allSets
            ->groupBy('muscle_group')
            ->map(fn ($sets, $group) => [
                'label' => Exercise::MUSCLE_GROUPS[$group] ?? $group,
                'volume' => round($sets->sum(fn ($set) => ((int) $set->reps) * ((float) $set->weight_kg)), 2),
                'sets' => $sets->count(),
            ])
            ->sortByDesc('volume')
            ->take(8)
            ->values();

        $maxMuscleVolume = max(1, (float) ($volumeByMuscle->max('volume') ?? 1));
        $volumeByMuscle = $volumeByMuscle->map(function (array $item) use ($maxMuscleVolume) {
            $item['percent'] = (int) round(($item['volume'] / $maxMuscleVolume) * 100);

            return $item;
        });

        $weeklyFrequency = collect(range(7, 0))->map(function (int $weeksAgo) use ($logs) {
            $start = now()->startOfWeek()->subWeeks($weeksAgo);
            $end = $start->copy()->endOfWeek();

            return [
                'label' => $start->format('d/m'),
                'count' => $logs->filter(fn (WorkoutLog $log) => $log->started_at->between($start, $end))->count(),
            ];
        });

        $maxWeekly = max(1, (int) ($weeklyFrequency->max('count') ?? 1));
        $weeklyFrequency = $weeklyFrequency->map(function (array $item) use ($maxWeekly) {
            $item['percent'] = (int) round(($item['count'] / $maxWeekly) * 100);

            return $item;
        });

        $topExercises = $allSets
            ->groupBy('exercise_name')
            ->map(fn ($sets, $name) => [
                'name' => $name,
                'sets' => $sets->count(),
                'max_weight' => (float) $sets->max(fn ($set) => (float) $set->weight_kg),
                'volume' => round($sets->sum(fn ($set) => ((int) $set->reps) * ((float) $set->weight_kg)), 2),
            ])
            ->sortByDesc('volume')
            ->take(10)
            ->values();

        $recentLogs = $logs->take(8);
        $measurements = $student->measurements->take(8);

        $publicPlans = WorkoutPlan::query()
            ->where('network_id', $tenant->networkId())
            ->whereNull('student_id')
            ->where('status', WorkoutPlan::STATUS_ACTIVE)
            ->where('visibility', WorkoutPlan::VISIBILITY_PUBLIC)
            ->withCount('sessions')
            ->orderBy('name')
            ->get();

        $activePlan = $student->workoutPlans()
            ->where('status', WorkoutPlan::STATUS_ACTIVE)
            ->withCount('sessions')
            ->latest('created_at')
            ->first();

        return view('pages.students.technical-sheet', compact(
            'student',
            'summary',
            'volumeByMuscle',
            'weeklyFrequency',
            'topExercises',
            'recentLogs',
            'measurements',
            'publicPlans',
            'activePlan'
        ));
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
