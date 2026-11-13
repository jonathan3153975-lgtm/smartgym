<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Models\WorkoutExercise;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkoutBuilderController extends Controller
{
    public function storeSession(Request $request, WorkoutPlan $workoutPlan): RedirectResponse
    {
        $this->ensurePlanAccess($workoutPlan);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['order'] = ($workoutPlan->sessions()->max('order') ?? 0) + 1;

        $workoutPlan->sessions()->create($validated);

        return redirect()
            ->route('admin.workout-plans.builder', $workoutPlan)
            ->with('success', 'Sessão de treino adicionada.');
    }

    public function updateSession(Request $request, WorkoutSession $workoutSession): RedirectResponse
    {
        $this->ensureSessionAccess($workoutSession);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $workoutSession->update($validated);

        return redirect()
            ->route('admin.workout-plans.builder', $workoutSession->plan)
            ->with('success', 'Sessão de treino atualizada.');
    }

    public function destroySession(WorkoutSession $workoutSession): RedirectResponse
    {
        $this->ensureSessionAccess($workoutSession);

        $plan = $workoutSession->plan;
        $workoutSession->delete();

        return redirect()
            ->route('admin.workout-plans.builder', $plan)
            ->with('success', 'Sessão de treino removida.');
    }

    public function storeExercise(Request $request, WorkoutSession $workoutSession): RedirectResponse
    {
        $this->ensureSessionAccess($workoutSession);
        $workoutSession->loadMissing('plan');

        $exercise = $this->validatedExercise($request, $workoutSession);
        $validated = $exercise['data'];
        $validated['order'] = ($workoutSession->exercises()->max('order') ?? 0) + 1;
        $validated['exercise_name'] = $exercise['model']->name;
        $validated['muscle_group'] = $exercise['model']->muscle_group;

        $workoutSession->exercises()->create($validated);

        return redirect()
            ->route('admin.workout-plans.builder', $workoutSession->plan)
            ->with('success', 'Exercício adicionado à sessão.');
    }

    public function updateExercise(Request $request, WorkoutExercise $workoutExercise): RedirectResponse
    {
        $this->ensureExerciseAccess($workoutExercise);
        $workoutExercise->loadMissing('session.plan');

        $exercise = $this->validatedExercise($request, $workoutExercise->session);
        $validated = $exercise['data'];
        $validated['exercise_name'] = $exercise['model']->name;
        $validated['muscle_group'] = $exercise['model']->muscle_group;

        $workoutExercise->update($validated);

        return redirect()
            ->route('admin.workout-plans.builder', $workoutExercise->session->plan)
            ->with('success', 'Exercício atualizado.');
    }

    public function destroyExercise(WorkoutExercise $workoutExercise): RedirectResponse
    {
        $this->ensureExerciseAccess($workoutExercise);
        $workoutExercise->loadMissing('session.plan');

        $plan = $workoutExercise->session->plan;
        $workoutExercise->delete();

        return redirect()
            ->route('admin.workout-plans.builder', $plan)
            ->with('success', 'Exercício removido da sessão.');
    }

    public function reorderExercises(Request $request, WorkoutSession $workoutSession): JsonResponse
    {
        $this->ensureSessionAccess($workoutSession);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $allowed = $workoutSession->exercises()->pluck('id')->map(fn ($id) => (int) $id);

        foreach (array_values($validated['ids']) as $position => $id) {
            if ($allowed->contains((int) $id)) {
                $workoutSession->exercises()
                    ->whereKey((int) $id)
                    ->update(['order' => $position + 1]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * @return array{model: Exercise, data: array<string, mixed>}
     */
    private function validatedExercise(Request $request, WorkoutSession $session): array
    {
        $session->loadMissing('plan');

        $validated = $request->validate([
            'exercise_id' => [
                'required',
                'integer',
                Rule::exists('exercises', 'id')
                    ->where('network_id', $session->plan->network_id)
                    ->whereNull('deleted_at'),
            ],
            'sets' => ['required', 'integer', 'between:1,20'],
            'reps' => ['required', 'integer', 'between:1,1000'],
            'rest_seconds' => ['required', 'integer', 'between:0,600'],
            'suggested_load' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $exercise = Exercise::query()
            ->where('network_id', $session->plan->network_id)
            ->whereKey($validated['exercise_id'])
            ->first();

        if (! $exercise) {
            throw ValidationException::withMessages([
                'exercise_id' => 'Selecione um exercício disponível na rede.',
            ]);
        }

        $validated['exercise_id'] = $exercise->id;

        return ['model' => $exercise, 'data' => $validated];
    }

    private function ensureSessionAccess(WorkoutSession $session): void
    {
        $this->ensurePlanAccess($session->plan);
    }

    private function ensureExerciseAccess(WorkoutExercise $exercise): void
    {
        $this->ensureSessionAccess($exercise->session);
    }

    private function ensurePlanAccess(?WorkoutPlan $plan): void
    {
        $tenant = $this->tenant();

        abort_unless($plan && (int) $plan->network_id === (int) $tenant->networkId(), 404);

        if ($plan->student_id) {
            abort_unless($plan->student && $tenant->canAccessStudent($plan->student), 404);
        }
    }

    private function tenant(): TenantContext
    {
        $tenant = TenantContext::current();

        abort_if($tenant->isPlatform(), 403, 'Selecione uma rede para montar treinos.');
        abort_unless($tenant->networkId(), 403, 'Selecione uma rede para montar treinos.');

        return $tenant;
    }
}
