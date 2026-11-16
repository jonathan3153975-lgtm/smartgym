<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Network;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentAssessment;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use App\Support\WorkoutPlanCopier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentWorkoutPlanController extends Controller
{
    public function __construct(private readonly WorkoutPlanCopier $copier) {}

    public function store(Request $request, Network $network): RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'objective' => ['nullable', 'string', 'max:255'],
            'goals' => ['nullable', 'array'],
            'goals.*' => ['string', Rule::in(array_keys(StudentAssessment::GOALS))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['nullable', Rule::in([WorkoutPlan::VISIBILITY_PRIVATE, WorkoutPlan::VISIBILITY_PUBLIC])],
        ]);

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $plan = WorkoutPlan::create([
            'network_id' => $network->id,
            'unit_id' => $this->primaryUnitId($student),
            'student_id' => $student->id,
            'name' => $validated['name'],
            'objective' => $validated['objective'] ?? null,
            'goals' => $validated['goals'] ?? [],
            'notes' => $validated['notes'] ?? null,
            'visibility' => $validated['visibility'] ?? WorkoutPlan::VISIBILITY_PRIVATE,
            'status' => WorkoutPlan::STATUS_ACTIVE,
        ]);

        $this->copier->activateForStudent($plan);

        return redirect()
            ->route('student.portal.workouts.plans.builder', [$network, $plan])
            ->with('success', 'Treino criado. Monte suas sessões e exercícios.');
    }

    public function search(Request $request, Network $network): JsonResponse
    {
        $account = $this->account($network);

        $goal = $request->string('goal')->toString();
        $muscleGroup = $request->string('muscle_group')->toString();
        $studentId = (int) $account->student_id;

        $plans = WorkoutPlan::query()
            ->where('network_id', $network->id)
            ->where('status', WorkoutPlan::STATUS_ACTIVE)
            ->where('visibility', WorkoutPlan::VISIBILITY_PUBLIC)
            ->where(fn ($query) => $query
                ->whereNull('student_id')
                ->orWhere('student_id', '!=', $studentId))
            ->with([
                'student:id,name',
                'sessions' => fn ($query) => $query->orderBy('order')->with('exercises'),
            ])
            ->withCount('sessions')
            ->orderByDesc('copies_count')
            ->orderByDesc('created_at')
            ->get()
            ->filter(function (WorkoutPlan $plan) use ($goal, $muscleGroup) {
                if ($goal && ! in_array($goal, $plan->goals ?? [], true)) {
                    return false;
                }

                if ($muscleGroup) {
                    return $plan->sessions
                        ->flatMap->exercises
                        ->contains('muscle_group', $muscleGroup);
                }

                return true;
            })
            ->take(30)
            ->values();

        return response()->json([
            'data' => $plans->map(fn (WorkoutPlan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'objective' => $plan->objective,
                'author' => $plan->student?->name ? 'Por '.$plan->student->name : 'Treino da equipe',
                'copies_label' => $plan->copies_label,
                'sessions_count' => $plan->sessions_count,
                'goals' => $plan->goal_labels,
                'sessions' => $plan->sessions->map(fn (WorkoutSession $session) => [
                    'id' => $session->id,
                    'name' => $session->name,
                    'exercises' => $session->exercises->map(fn ($exercise) => [
                        'id' => $exercise->id,
                        'name' => $exercise->exercise_name,
                        'sets' => $exercise->sets,
                        'reps' => $exercise->reps,
                    ])->values(),
                ])->values(),
            ])->values(),
        ]);
    }

    public function destroy(Network $network, WorkoutPlan $workoutPlan): RedirectResponse
    {
        $account = $this->account($network);
        $student = $account->student;

        abort_unless(
            $student instanceof Student
            && (int) $workoutPlan->student_id === (int) $student->id
            && (int) $workoutPlan->network_id === (int) $network->id,
            404
        );

        abort_if(
            $workoutPlan->created_by !== null,
            403,
            'Treinos aplicados pela equipe não podem ser excluídos pelo aluno.'
        );

        $workoutPlan->delete();

        return redirect()
            ->route('student.portal.workouts.index', $network)
            ->with('success', 'Treino excluído.');
    }

    public function copy(Network $network, WorkoutPlan $workoutPlan): RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        abort_unless(
            (int) $workoutPlan->network_id === (int) $network->id
            && $workoutPlan->isPublic()
            && $workoutPlan->isActive(),
            404
        );

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $plan = $this->copier->copy($workoutPlan, [
            'student_id' => $student->id,
            'unit_id' => $this->primaryUnitId($student),
            'visibility' => WorkoutPlan::VISIBILITY_PRIVATE,
            'created_by' => null,
        ]);

        $this->copier->activateForStudent($plan);

        return redirect()
            ->route('student.portal.workouts.plans.builder', [$network, $plan])
            ->with('success', 'Treino copiado para a sua conta.');
    }

    public function activate(Network $network, WorkoutPlan $workoutPlan): RedirectResponse
    {
        $account = $this->account($network);

        abort_unless(
            (int) $workoutPlan->student_id === (int) $account->student_id
            && (int) $workoutPlan->network_id === (int) $network->id,
            404
        );

        $workoutPlan->update(['status' => WorkoutPlan::STATUS_ACTIVE]);
        $this->copier->activateForStudent($workoutPlan);

        return redirect()
            ->route('student.portal.workouts.index', $network)
            ->with('success', 'Treino ativado.');
    }

    public function visibility(Network $network, WorkoutPlan $workoutPlan): JsonResponse|RedirectResponse
    {
        $account = $this->account($network);

        abort_unless(
            (int) $workoutPlan->student_id === (int) $account->student_id
            && (int) $workoutPlan->network_id === (int) $network->id,
            404
        );

        $workoutPlan->update([
            'visibility' => $workoutPlan->isPublic()
                ? WorkoutPlan::VISIBILITY_PRIVATE
                : WorkoutPlan::VISIBILITY_PUBLIC,
        ]);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'visibility' => $workoutPlan->visibility,
            ]);
        }

        return redirect()
            ->route('student.portal.workouts.plans.builder', [$network, $workoutPlan])
            ->with('success', 'Visibilidade atualizada.');
    }

    public function builder(Network $network, WorkoutPlan $workoutPlan): View|RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        abort_unless(
            (int) $workoutPlan->student_id === (int) $account->student_id
            && (int) $workoutPlan->network_id === (int) $network->id,
            404
        );

        $workoutPlan->load(['sessions.exercises']);

        $exercises = Exercise::query()
            ->where('network_id', $network->id)
            ->orderBy('name')
            ->get(['id', 'name', 'muscle_group', 'equipment', 'image', 'status']);

        $builderRoutes = [
            'back' => route('student.portal.workouts.index', $network),
            'sessionStore' => route('student.portal.workouts.plans.sessions.store', [$network, $workoutPlan]),
            'sessionUpdate' => route('student.portal.workouts.plans.sessions.update', [$network, '__ID__']),
            'sessionDestroy' => route('student.portal.workouts.plans.sessions.destroy', [$network, '__ID__']),
            'exerciseStore' => route('student.portal.workouts.plans.exercises.store', [$network, '__ID__']),
            'exerciseUpdate' => route('student.portal.workouts.plans.exercises.update', [$network, '__ID__']),
            'exerciseDestroy' => route('student.portal.workouts.plans.exercises.destroy', [$network, '__ID__']),
            'exerciseReorder' => route('student.portal.workouts.plans.exercises.reorder', [$network, '__ID__']),
        ];

        return view('pages.workout-plans.builder', [
            'layout' => 'layouts.student',
            'workoutPlan' => $workoutPlan,
            'exercises' => $exercises,
            'builderRoutes' => $builderRoutes,
            'canManageVisibility' => true,
            'visibilityUpdate' => route('student.portal.workouts.plans.visibility', [$network, $workoutPlan]),
        ]);
    }

    private function primaryUnitId(Student $student): ?int
    {
        $studentUnit = $student->units()->where('is_primary', true)->first()
            ?? $student->units()->first();

        return $studentUnit?->unit_id;
    }

    private function ensureActivePlan(StudentAccount $account, Network $network): ?RedirectResponse
    {
        if ($account->student?->activeSubscription()) {
            return null;
        }

        return redirect()
            ->route('student.portal.plans', $network)
            ->withErrors(['plan' => 'Assine um plano para montar seus treinos.']);
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
