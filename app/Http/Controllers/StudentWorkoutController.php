<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Network;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\WorkoutLog;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentWorkoutController extends Controller
{
    public function index(Network $network): View|RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $plan = $this->activePlan($student);
        $inProgress = $this->inProgressLog($student);

        $myPlans = $student->workoutPlans()
            ->withCount('sessions')
            ->orderByDesc('created_at')
            ->get();

        $publicPlans = WorkoutPlan::query()
            ->where('network_id', $network->id)
            ->where('status', WorkoutPlan::STATUS_ACTIVE)
            ->where('visibility', WorkoutPlan::VISIBILITY_PUBLIC)
            ->where(fn ($query) => $query
                ->whereNull('student_id')
                ->orWhere('student_id', '!=', $student->id))
            ->with('student:id,name')
            ->withCount('sessions')
            ->orderByDesc('created_at')
            ->take(12)
            ->get();

        return view('pages.student-portal.workouts.index', compact(
            'network',
            'account',
            'student',
            'plan',
            'inProgress',
            'myPlans',
            'publicPlans'
        ));
    }

    public function showSession(Network $network, WorkoutSession $workoutSession): View|RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $this->ensureSessionAccess($workoutSession, $account);

        $workoutSession->load(['plan.student', 'exercises.exercise']);

        $inProgress = $account->student
            ?->workoutLogs()
            ->where('status', WorkoutLog::STATUS_IN_PROGRESS)
            ->where('workout_session_id', $workoutSession->id)
            ->latest('started_at')
            ->first();

        return view('pages.student-portal.workouts.session', compact(
            'network',
            'account',
            'workoutSession',
            'inProgress'
        ));
    }

    public function start(Network $network, WorkoutSession $workoutSession): RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $this->ensureSessionAccess($workoutSession, $account);

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $log = DB::transaction(function () use ($student, $workoutSession) {
            $student->workoutLogs()
                ->where('status', WorkoutLog::STATUS_IN_PROGRESS)
                ->update(['status' => WorkoutLog::STATUS_CANCELED]);

            return WorkoutLog::create([
                'network_id' => $workoutSession->plan->network_id,
                'student_id' => $student->id,
                'workout_plan_id' => $workoutSession->workout_plan_id,
                'workout_session_id' => $workoutSession->id,
                'session_name' => $workoutSession->name,
                'status' => WorkoutLog::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);
        });

        return redirect()->route('student.portal.workouts.log', [$network, $log]);
    }

    public function log(Network $network, WorkoutLog $workoutLog): View|RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $this->ensureLogAccess($workoutLog, $account);

        if ($workoutLog->isCompleted()) {
            return redirect()->route('student.portal.workouts.history.show', [$network, $workoutLog]);
        }

        $workoutLog->load(['session.exercises.exercise', 'sets']);

        abort_unless($workoutLog->session, 404);

        return view('pages.student-portal.workouts.log', [
            'network' => $network,
            'account' => $account,
            'workoutLog' => $workoutLog,
            'exercises' => $workoutLog->session->exercises,
        ]);
    }

    public function complete(Request $request, Network $network, WorkoutLog $workoutLog): RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $this->ensureLogAccess($workoutLog, $account);

        if ($workoutLog->isCompleted()) {
            return redirect()->route('student.portal.workouts.history.show', [$network, $workoutLog]);
        }

        $validated = $request->validate([
            'sets' => ['nullable', 'array'],
            'sets.*.workout_exercise_id' => ['nullable', 'integer'],
            'sets.*.exercise_id' => ['nullable', 'integer'],
            'sets.*.exercise_name' => ['required_with:sets', 'string', 'max:255'],
            'sets.*.muscle_group' => ['required_with:sets', 'string', 'max:40'],
            'sets.*.set_number' => ['required_with:sets', 'integer', 'between:1,50'],
            'sets.*.reps' => ['nullable', 'integer', 'between:0,1000'],
            'sets.*.weight_kg' => ['nullable', 'numeric', 'between:0,999.99'],
            'sets.*.rest_seconds' => ['nullable', 'integer', 'between:0,600'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $workoutLog->loadMissing('session.exercises');
        $plannedExercises = $workoutLog->session?->exercises->keyBy('id') ?? collect();

        $rows = collect($validated['sets'] ?? [])
            ->filter(fn (array $row) => ($row['reps'] ?? null) !== null || ($row['weight_kg'] ?? null) !== null)
            ->map(function (array $row) use ($plannedExercises) {
                $planned = $plannedExercises->get((int) ($row['workout_exercise_id'] ?? 0));

                if (! $planned) {
                    throw ValidationException::withMessages([
                        'sets' => 'Um dos exercícios registrados não pertence mais a este treino.',
                    ]);
                }

                $row['exercise_id'] = $planned->exercise_id;
                $row['exercise_name'] = $planned->exercise_name;
                $row['muscle_group'] = $planned->muscle_group;
                $row['rest_seconds'] = $planned->rest_seconds;

                return $row;
            })
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'sets' => 'Registre ao menos uma série para concluir o treino.',
            ]);
        }

        DB::transaction(function () use ($workoutLog, $rows, $validated) {
            $workoutLog->sets()->delete();

            foreach ($rows as $row) {
                $workoutLog->sets()->create([
                    'workout_exercise_id' => $row['workout_exercise_id'] ?? null,
                    'exercise_id' => $row['exercise_id'] ?? null,
                    'exercise_name' => $row['exercise_name'],
                    'muscle_group' => $row['muscle_group'],
                    'set_number' => $row['set_number'],
                    'reps' => $row['reps'],
                    'weight_kg' => $row['weight_kg'],
                    'rest_seconds' => $row['rest_seconds'] ?? null,
                ]);
            }

            $completedAt = now();

            $workoutLog->update([
                'status' => WorkoutLog::STATUS_COMPLETED,
                'completed_at' => $completedAt,
                'duration_seconds' => max(0, (int) $completedAt->diffInSeconds($workoutLog->started_at)),
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()
            ->route('student.portal.workouts.history.show', [$network, $workoutLog])
            ->with('success', 'Treino concluído! Bons treinos.');
    }

    public function history(Network $network): View|RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $logs = $student->workoutLogs()
            ->where('status', WorkoutLog::STATUS_COMPLETED)
            ->with('sets')
            ->orderByDesc('started_at')
            ->paginate(10)
            ->withQueryString();

        return view('pages.student-portal.workouts.history', compact('network', 'account', 'logs'));
    }

    public function historyShow(Network $network, WorkoutLog $workoutLog): View|RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $this->ensureLogAccess($workoutLog, $account);

        $workoutLog->load(['sets', 'plan']);

        return view('pages.student-portal.workouts.history-show', compact('network', 'account', 'workoutLog'));
    }

    public function evolution(Network $network): View|RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $logs = $student->workoutLogs()
            ->where('status', WorkoutLog::STATUS_COMPLETED)
            ->with('sets')
            ->orderBy('started_at')
            ->get();

        $allSets = $logs->flatMap->sets;

        $totalVolume = round($allSets->sum(
            fn ($set) => ((int) $set->reps) * ((float) $set->weight_kg)
        ), 2);

        $volumeByMuscle = $allSets
            ->groupBy('muscle_group')
            ->map(fn ($sets, $group) => [
                'group' => $group,
                'label' => Exercise::MUSCLE_GROUPS[$group] ?? $group,
                'volume' => round($sets->sum(fn ($set) => ((int) $set->reps) * ((float) $set->weight_kg)), 2),
                'sets' => $sets->count(),
            ])
            ->sortByDesc('volume')
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
                'max_weight' => (float) $sets->max(fn ($set) => (float) $set->weight_kg),
                'volume' => round($sets->sum(fn ($set) => ((int) $set->reps) * ((float) $set->weight_kg)), 2),
                'sets' => $sets->count(),
            ])
            ->sortByDesc('volume')
            ->take(10)
            ->values();

        $lastThirtyDays = $logs->filter(fn (WorkoutLog $log) => $log->started_at->gte(now()->subDays(30)));

        $summary = [
            'workouts' => $logs->count(),
            'workouts_last_30' => $lastThirtyDays->count(),
            'sets' => $allSets->count(),
            'reps' => (int) $allSets->sum(fn ($set) => (int) $set->reps),
            'volume' => $totalVolume,
            'volume_last_30' => round($lastThirtyDays->flatMap->sets->sum(
                fn ($set) => ((int) $set->reps) * ((float) $set->weight_kg)
            ), 2),
        ];

        return view('pages.student-portal.workouts.evolution', compact(
            'network',
            'account',
            'summary',
            'volumeByMuscle',
            'weeklyFrequency',
            'topExercises'
        ));
    }

    private function activePlan(Student $student): ?WorkoutPlan
    {
        return $student->workoutPlans()
            ->active()
            ->where(function ($query) {
                $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', now()->toDateString());
            })
            ->with(['sessions' => fn ($query) => $query->withCount('exercises')])
            ->latest('created_at')
            ->first();
    }

    private function inProgressLog(Student $student): ?WorkoutLog
    {
        return $student->workoutLogs()
            ->where('status', WorkoutLog::STATUS_IN_PROGRESS)
            ->latest('started_at')
            ->first();
    }

    private function ensureSessionAccess(WorkoutSession $session, StudentAccount $account): void
    {
        $session->loadMissing('plan');

        $plan = $session->plan;

        abort_unless(
            $plan
            && (int) $plan->student_id === (int) $account->student_id
            && (int) $plan->network_id === (int) $account->network_id
            && $plan->isActive()
            && (! $plan->starts_at || $plan->starts_at->isToday() || $plan->starts_at->isPast())
            && (! $plan->ends_at || $plan->ends_at->isToday() || $plan->ends_at->isFuture()),
            404
        );
    }

    private function ensureLogAccess(WorkoutLog $log, StudentAccount $account): void
    {
        abort_unless(
            (int) $log->student_id === (int) $account->student_id
            && (int) $log->network_id === (int) $account->network_id,
            404
        );
    }

    private function ensureActivePlan(StudentAccount $account, Network $network): ?RedirectResponse
    {
        if ($account->student?->activeSubscription()) {
            return null;
        }

        return redirect()
            ->route('student.portal.plans', $network)
            ->withErrors(['plan' => 'Assine um plano para acessar seus treinos.']);
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
