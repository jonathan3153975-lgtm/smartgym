<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\WorkoutPlan;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WorkoutPlanController extends Controller
{
    public function index(Request $request): View|Response
    {
        $tenant = $this->tenant();

        $query = WorkoutPlan::query()
            ->where('network_id', $tenant->networkId())
            ->where(fn ($builder) => $builder
                ->whereNull('student_id')
                ->orWhereHas('student', fn ($student) => $tenant->scopeStudents($student)))
            ->with(['student:id,name', 'createdBy:id,name'])
            ->withCount('sessions')
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('objective', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($student) => $student->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $plans = $query->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return response()->view('pages.admin.workout-plans._table', compact('plans'));
        }

        return view('pages.admin.workout-plans.index', compact('plans'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        $validated = $this->validatePlan($request);

        $plan = WorkoutPlan::create(array_merge($validated, [
            'network_id' => $tenant->networkId(),
            'student_id' => null,
            'unit_id' => null,
            'visibility' => WorkoutPlan::VISIBILITY_PUBLIC,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Treino cadastrado com sucesso.',
                'data' => $plan,
            ]);
        }

        return redirect()->route('admin.workout-plans.builder', $plan)
            ->with('success', 'Treino cadastrado com sucesso.');
    }

    public function editData(WorkoutPlan $workoutPlan): JsonResponse
    {
        $this->ensureAccess($workoutPlan);

        return response()->json([
            'id' => $workoutPlan->id,
            'name' => $workoutPlan->name,
            'objective' => $workoutPlan->objective,
            'notes' => $workoutPlan->notes,
            'status' => $workoutPlan->status,
            'visibility' => $workoutPlan->visibility,
        ]);
    }

    public function update(Request $request, WorkoutPlan $workoutPlan): RedirectResponse|JsonResponse
    {
        $this->ensureAccess($workoutPlan);

        $validated = $this->validatePlan($request);
        $validated['updated_by'] = auth()->id();

        $workoutPlan->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Treino atualizado com sucesso.',
                'data' => $workoutPlan->fresh(),
            ]);
        }

        return redirect()->route('admin.workout-plans.index')->with('success', 'Treino atualizado com sucesso.');
    }

    public function toggleStatus(WorkoutPlan $workoutPlan): JsonResponse|RedirectResponse
    {
        $this->ensureAccess($workoutPlan);

        $workoutPlan->update([
            'status' => $workoutPlan->isActive() ? WorkoutPlan::STATUS_INACTIVE : WorkoutPlan::STATUS_ACTIVE,
            'updated_by' => auth()->id(),
        ]);

        $message = $workoutPlan->isActive() ? 'Treino ativado com sucesso.' : 'Treino inativado com sucesso.';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $workoutPlan->status,
            ]);
        }

        return redirect()->route('admin.workout-plans.index')->with('success', $message);
    }

    public function destroy(WorkoutPlan $workoutPlan): JsonResponse|RedirectResponse
    {
        $this->ensureAccess($workoutPlan);

        $workoutPlan->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Treino removido com sucesso.',
            ]);
        }

        return redirect()->route('admin.workout-plans.index')->with('success', 'Treino removido com sucesso.');
    }

    public function builder(WorkoutPlan $workoutPlan): View
    {
        $this->ensureAccess($workoutPlan);

        $workoutPlan->load([
            'student:id,name',
            'createdBy:id,name',
            'sessions.exercises',
        ]);

        $exercises = Exercise::query()
            ->where('network_id', $workoutPlan->network_id)
            ->orderBy('name')
            ->get(['id', 'name', 'muscle_group', 'equipment', 'image', 'status']);

        $builderRoutes = [
            'back' => route('admin.workout-plans.index'),
            'sessionStore' => route('admin.workout-plans.sessions.store', $workoutPlan),
            'sessionUpdate' => route('admin.workout-sessions.update', '__ID__'),
            'sessionDestroy' => route('admin.workout-sessions.destroy', '__ID__'),
            'exerciseStore' => route('admin.workout-sessions.exercises.store', '__ID__'),
            'exerciseUpdate' => route('admin.workout-exercises.update', '__ID__'),
            'exerciseDestroy' => route('admin.workout-exercises.destroy', '__ID__'),
            'exerciseReorder' => route('admin.workout-sessions.exercises.reorder', '__ID__'),
        ];

        return view('pages.workout-plans.builder', [
            'layout' => 'layouts.app',
            'workoutPlan' => $workoutPlan,
            'exercises' => $exercises,
            'builderRoutes' => $builderRoutes,
            'canManageVisibility' => false,
            'visibilityUpdate' => null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePlan(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in([WorkoutPlan::STATUS_ACTIVE, WorkoutPlan::STATUS_INACTIVE])],
        ]);
    }

    private function ensureAccess(WorkoutPlan $plan): void
    {
        $tenant = $this->tenant();

        abort_unless((int) $plan->network_id === (int) $tenant->networkId(), 404);

        if ($plan->student_id) {
            abort_unless($plan->student && $tenant->canAccessStudent($plan->student), 404);
        }
    }

    private function tenant(): TenantContext
    {
        $tenant = TenantContext::current();

        abort_if($tenant->isPlatform(), 403, 'Selecione uma rede para gerenciar treinos.');
        abort_unless($tenant->networkId(), 403, 'Selecione uma rede para gerenciar treinos.');

        return $tenant;
    }
}
