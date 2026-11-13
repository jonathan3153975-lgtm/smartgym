<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Network;
use App\Models\StudentAccount;
use App\Models\WorkoutExercise;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentWorkoutPlanBuilderController extends Controller
{
    public function storeSession(Request $request, Network $network, WorkoutPlan $workoutPlan): RedirectResponse
    {
        $account = $this->account($network);
        $this->ensurePlanAccess($workoutPlan, $account);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['order'] = ($workoutPlan->sessions()->max('order') ?? 0) + 1;

        $workoutPlan->sessions()->create($validated);

        return redirect()
            ->route('student.portal.workouts.plans.builder', [$network, $workoutPlan])
            ->with('success', 'Sessão adicionada.');
    }

    public function updateSession(Request $request, Network $network, WorkoutSession $workoutSession): RedirectResponse
    {
        $account = $this->account($network);
        $this->ensureSessionAccess($workoutSession, $account);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $workoutSession->update($validated);

        return redirect()
            ->route('student.portal.workouts.plans.builder', [$network, $workoutSession->plan])
            ->with('success', 'Sessão atualizada.');
    }

    public function destroySession(Network $network, WorkoutSession $workoutSession): RedirectResponse
    {
        $account = $this->account($network);
        $this->ensureSessionAccess($workoutSession, $account);

        $plan = $workoutSession->plan;
        $workoutSession->delete();

        return redirect()
            ->route('student.portal.workouts.plans.builder', [$network, $plan])
            ->with('success', 'Sessão removida.');
    }

    public function storeExercise(Request $request, Network $network, WorkoutSession $workoutSession): RedirectResponse
    {
        $account = $this->account($network);
        $this->ensureSessionAccess($workoutSession, $account);
        $workoutSession->loadMissing('plan');

        $exercise = $this->validatedExercise($request, $workoutSession);
        $data = $exercise['data'];
        $data['order'] = ($workoutSession->exercises()->max('order') ?? 0) + 1;
        $data['exercise_name'] = $exercise['model']->name;
        $data['muscle_group'] = $exercise['model']->muscle_group;

        $workoutSession->exercises()->create($data);

        return redirect()
            ->route('student.portal.workouts.plans.builder', [$network, $workoutSession->plan])
            ->with('success', 'Exercício adicionado.');
    }

    public function updateExercise(Request $request, Network $network, WorkoutExercise $workoutExercise): RedirectResponse
    {
        $account = $this->account($network);
        $this->ensureExerciseAccess($workoutExercise, $account);
        $workoutExercise->loadMissing('session.plan');

        $exercise = $this->validatedExercise($request, $workoutExercise->session);
        $data = $exercise['data'];
        $data['exercise_name'] = $exercise['model']->name;
        $data['muscle_group'] = $exercise['model']->muscle_group;

        $workoutExercise->update($data);

        return redirect()
            ->route('student.portal.workouts.plans.builder', [$network, $workoutExercise->session->plan])
            ->with('success', 'Exercício atualizado.');
    }

    public function destroyExercise(Network $network, WorkoutExercise $workoutExercise): RedirectResponse
    {
        $account = $this->account($network);
        $this->ensureExerciseAccess($workoutExercise, $account);
        $workoutExercise->loadMissing('session.plan');

        $plan = $workoutExercise->session->plan;
        $workoutExercise->delete();

        return redirect()
            ->route('student.portal.workouts.plans.builder', [$network, $plan])
            ->with('success', 'Exercício removido.');
    }

    public function reorderExercises(Request $request, Network $network, WorkoutSession $workoutSession): JsonResponse
    {
        $account = $this->account($network);
        $this->ensureSessionAccess($workoutSession, $account);

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

    private function ensureSessionAccess(WorkoutSession $session, StudentAccount $account): void
    {
        $this->ensurePlanAccess($session->plan, $account);
    }

    private function ensureExerciseAccess(WorkoutExercise $exercise, StudentAccount $account): void
    {
        $this->ensureSessionAccess($exercise->session, $account);
    }

    private function ensurePlanAccess(?WorkoutPlan $plan, StudentAccount $account): void
    {
        abort_unless(
            $plan
            && (int) $plan->student_id === (int) $account->student_id
            && (int) $plan->network_id === (int) $account->network_id,
            404
        );
    }

    private function account(Network $network): StudentAccount
    {
        /** @var StudentAccount|null $account */
        $account = Auth::guard('student')->user();

        abort_unless(
            $account instanceof StudentAccount && (int) $account->network_id === (int) $network->id,
            403
        );

        $account->loadMissing('student');

        return $account;
    }
}
