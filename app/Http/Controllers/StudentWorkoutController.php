<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\Network;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\WorkoutExercise;
use App\Models\WorkoutLog;
use App\Models\WorkoutLogSet;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use App\Support\StudentExerciseStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
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

        return view('pages.student-portal.workouts.index', compact(
            'network',
            'account',
            'student',
            'plan',
            'inProgress',
            'myPlans'
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

        $exerciseStats = $account->student
            ? StudentExerciseStats::for($account->student, $workoutSession->exercises->pluck('exercise_id'))
            : [];

        return view('pages.student-portal.workouts.session', compact(
            'network',
            'account',
            'workoutSession',
            'inProgress',
            'exerciseStats'
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

        $exerciseStats = $account->student
            ? StudentExerciseStats::for($account->student, $workoutLog->session->exercises->pluck('exercise_id'))
            : [];

        $exerciseCatalog = Exercise::query()
            ->where('network_id', $network->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $startedExerciseIds = $workoutLog->sets
            ->pluck('workout_exercise_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return view('pages.student-portal.workouts.log', [
            'network' => $network,
            'account' => $account,
            'workoutLog' => $workoutLog,
            'exercises' => $workoutLog->session->exercises,
            'exerciseStats' => $exerciseStats,
            'exerciseCatalog' => $exerciseCatalog,
            'startedExerciseIds' => $startedExerciseIds,
        ]);
    }

    public function saveProgress(Request $request, Network $network, WorkoutLog $workoutLog): JsonResponse
    {
        $account = $this->account($network);
        $this->ensureLogAccess($workoutLog, $account);

        if ($workoutLog->isCompleted()) {
            return response()->json(['success' => false, 'message' => 'Treino já concluído.'], 409);
        }

        $request->merge([
            'sets' => $this->normalizeSetInputs($request->input('sets', [])),
        ]);

        $validated = $request->validate([
            'sets' => ['nullable', 'array'],
            'sets.*.workout_exercise_id' => ['nullable', 'integer'],
            'sets.*.exercise_id' => ['nullable', 'integer'],
            'sets.*.exercise_name' => ['required_with:sets', 'string', 'max:255'],
            'sets.*.muscle_group' => ['required_with:sets', 'string', 'max:40'],
            'sets.*.set_number' => ['required_with:sets', 'integer', 'between:1,50'],
            'sets.*.reps' => ['nullable', 'integer', 'between:0,1000'],
            'sets.*.weight_kg' => ['nullable', 'numeric', 'between:0,99999.999'],
            'sets.*.distance_km' => ['nullable', 'numeric', 'between:0,99999.999'],
            'sets.*.cardio_seconds' => ['nullable', 'integer', 'between:0,86400'],
            'sets.*.rest_seconds' => ['nullable', 'integer', 'between:0,600'],
            'sets.*.touched' => ['nullable', 'boolean'],
            'sets.*.completed' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $workoutLog->loadMissing('session.exercises.exercise');
        $plannedExercises = $workoutLog->session?->exercises->keyBy('id') ?? collect();

        $rows = collect($validated['sets'] ?? [])
            ->filter(fn (array $row) => $this->hasSetData($row) && (int) ($row['touched'] ?? 0) === 1)
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

                if ($planned->isCardio()) {
                    $row['weight_kg'] = null;
                    $row['reps'] = null;
                    $row['cardio_seconds'] = $this->resolveCardioSeconds($row, $planned->suggested_load);
                } else {
                    $row['distance_km'] = null;
                    $row['cardio_seconds'] = null;
                }

                return $row;
            })
            ->values();

        DB::transaction(function () use ($workoutLog, $rows, $validated) {
            $workoutLog->sets()->delete();

            foreach ($rows as $row) {
                $workoutLog->sets()->create([
                    'workout_exercise_id' => $row['workout_exercise_id'] ?? null,
                    'exercise_id' => $row['exercise_id'] ?? null,
                    'exercise_name' => $row['exercise_name'],
                    'muscle_group' => $row['muscle_group'],
                    'set_number' => $row['set_number'],
                    'reps' => $row['reps'] ?? null,
                    'weight_kg' => $row['weight_kg'] ?? null,
                    'distance_km' => $row['distance_km'] ?? null,
                    'cardio_seconds' => $row['cardio_seconds'] ?? null,
                    'completed' => (bool) ($row['completed'] ?? false),
                    'rest_seconds' => $row['rest_seconds'] ?? null,
                ]);
            }

            $workoutLog->update([
                'notes' => $validated['notes'] ?? $workoutLog->notes,
            ]);
        });

        return response()->json([
            'success' => true,
            'count' => $rows->count(),
            'saved_at' => now()->toIso8601String(),
        ]);
    }

    public function replaceExercise(
        Request $request,
        Network $network,
        WorkoutLog $workoutLog,
        WorkoutExercise $workoutExercise
    ): RedirectResponse {
        $account = $this->account($network);
        $this->ensureLogAccess($workoutLog, $account);

        abort_if($workoutLog->isCompleted(), 403);
        abort_unless(
            (int) $workoutExercise->workout_session_id === (int) $workoutLog->workout_session_id,
            404
        );

        if ($workoutLog->sets()->where('workout_exercise_id', $workoutExercise->id)->exists()) {
            return back()->withErrors([
                'exercise' => 'Conclua ou edite as séries antes de substituir este exercício.',
            ]);
        }

        $validated = $request->validate([
            'exercise_id' => [
                'required',
                'integer',
                Rule::exists('exercises', 'id')
                    ->where('network_id', $network->id)
                    ->whereNull('deleted_at'),
            ],
        ]);

        $exercise = Exercise::query()
            ->where('network_id', $network->id)
            ->whereKey($validated['exercise_id'])
            ->firstOrFail();

        $workoutExercise->update([
            'exercise_id' => $exercise->id,
            'exercise_name' => $exercise->name,
            'muscle_group' => $exercise->muscle_group,
            'suggested_load' => null,
        ]);

        return redirect()
            ->route('student.portal.workouts.log', [$network, $workoutLog])
            ->with('success', 'Exercício substituído neste treino.');
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

        $request->merge([
            'sets' => $this->normalizeSetInputs($request->input('sets', [])),
        ]);

        $validated = $request->validate([
            'sets' => ['nullable', 'array'],
            'sets.*.workout_exercise_id' => ['nullable', 'integer'],
            'sets.*.exercise_id' => ['nullable', 'integer'],
            'sets.*.exercise_name' => ['required_with:sets', 'string', 'max:255'],
            'sets.*.muscle_group' => ['required_with:sets', 'string', 'max:40'],
            'sets.*.set_number' => ['required_with:sets', 'integer', 'between:1,50'],
            'sets.*.reps' => ['nullable', 'integer', 'between:0,1000'],
            'sets.*.weight_kg' => ['nullable', 'numeric', 'between:0,99999.999'],
            'sets.*.distance_km' => ['nullable', 'numeric', 'between:0,99999.999'],
            'sets.*.cardio_seconds' => ['nullable', 'integer', 'between:0,86400'],
            'sets.*.rest_seconds' => ['nullable', 'integer', 'between:0,600'],
            'sets.*.completed' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'update_plan' => ['nullable', 'boolean'],
        ]);

        $workoutLog->loadMissing('session.exercises.exercise');
        $plannedExercises = $workoutLog->session?->exercises->keyBy('id') ?? collect();

        $rows = collect($validated['sets'] ?? [])
            ->filter(fn (array $row) => $this->hasSetData($row)
                && (! array_key_exists('completed', $row) || (int) $row['completed'] === 1))
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

                if ($planned->isCardio()) {
                    $row['weight_kg'] = null;
                    $row['reps'] = null;
                    $row['cardio_seconds'] = $this->resolveCardioSeconds($row, $planned->suggested_load);
                } else {
                    $row['distance_km'] = null;
                    $row['cardio_seconds'] = null;
                }

                return $row;
            })
            ->values();

        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'sets' => 'Marque ao menos uma série como concluída para finalizar o treino.',
            ]);
        }

        DB::transaction(function () use ($workoutLog, $rows, $validated, $plannedExercises) {
            $workoutLog->sets()->delete();

            foreach ($rows as $row) {
                $workoutLog->sets()->create([
                    'workout_exercise_id' => $row['workout_exercise_id'] ?? null,
                    'exercise_id' => $row['exercise_id'] ?? null,
                    'exercise_name' => $row['exercise_name'],
                    'muscle_group' => $row['muscle_group'],
                    'set_number' => $row['set_number'],
                    'reps' => $row['reps'] ?? null,
                    'weight_kg' => $row['weight_kg'] ?? null,
                    'distance_km' => $row['distance_km'] ?? null,
                    'cardio_seconds' => $row['cardio_seconds'] ?? null,
                    'completed' => true,
                    'rest_seconds' => $row['rest_seconds'] ?? null,
                ]);
            }

            if (! empty($validated['update_plan'])) {
                $this->updatePlanDefinitions($plannedExercises, $rows);
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

    public function history(Request $request, Network $network): View|RedirectResponse
    {
        $account = $this->account($network);

        if ($redirect = $this->ensureActivePlan($account, $network)) {
            return $redirect;
        }

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $years = $student->workoutLogs()
            ->where('status', WorkoutLog::STATUS_COMPLETED)
            ->get(['id', 'started_at'])
            ->map(fn (WorkoutLog $log) => $log->started_at?->year)
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        $query = $student->workoutLogs()
            ->where('status', WorkoutLog::STATUS_COMPLETED)
            ->with('sets');

        if ($request->filled('year')) {
            $query->whereYear('started_at', (int) $request->input('year'));
        }

        if ($request->filled('month')) {
            $query->whereMonth('started_at', (int) $request->input('month'));
        }

        $logs = $query
            ->orderByDesc('started_at')
            ->paginate(10)
            ->withQueryString();

        return view('pages.student-portal.workouts.history', compact('network', 'account', 'logs', 'years'));
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

        $exercisesByMuscle = $allSets
            ->groupBy('muscle_group')
            ->map(function ($sets, $group) use ($allSets) {
                $totalReps = max(1, (int) $allSets->sum(fn ($set) => (int) $set->reps));
                $reps = (int) $sets->sum(fn ($set) => (int) $set->reps);

                return [
                    'group' => $group,
                    'label' => Exercise::MUSCLE_GROUPS[$group] ?? $group,
                    'reps' => $reps,
                    'sets' => $sets->count(),
                    'exercises' => $sets->pluck('exercise_name')->unique()->count(),
                    'percent' => (int) round(($reps / $totalReps) * 100),
                ];
            })
            ->sortByDesc('reps')
            ->values();

        $setRows = $logs->flatMap(function (WorkoutLog $log) {
            return $log->sets->map(fn (WorkoutLogSet $set) => [
                'set' => $set,
                'date_key' => $log->started_at->toDateString(),
                'date' => $log->started_at->format('d/m/Y'),
            ]);
        });

        $muscleDetails = $setRows
            ->groupBy(fn ($row) => $row['set']->muscle_group)
            ->map(function ($rows, $group) {
                $exercises = $rows
                    ->groupBy(fn ($row) => $row['set']->exercise_name)
                    ->map(function ($exerciseRows, $name) {
                        $evolution = $exerciseRows
                            ->groupBy('date_key')
                            ->sortKeys()
                            ->map(function ($dayRows) {
                                $maxWeight = (float) $dayRows->max(fn ($row) => (float) $row['set']->weight_kg);
                                $maxSeconds = (int) $dayRows->max(fn ($row) => (int) $row['set']->cardio_seconds);
                                $maxDistance = (float) $dayRows->max(fn ($row) => (float) $row['set']->distance_km);
                                $maxReps = (int) $dayRows->max(fn ($row) => (int) $row['set']->reps);

                                return [
                                    'date' => $dayRows->first()['date'],
                                    'weight_label' => $maxWeight > 0 ? number_format($maxWeight, 2, ',', '.').' kg' : null,
                                    'time_label' => $maxSeconds > 0
                                        ? sprintf('%d min %02d s', intdiv($maxSeconds, 60), $maxSeconds % 60)
                                        : null,
                                    'distance_label' => $maxDistance > 0 ? number_format($maxDistance, 3, ',', '.').' km' : null,
                                    'reps_label' => $maxReps > 0 ? $maxReps.' reps' : null,
                                ];
                            })
                            ->values();

                        return [
                            'name' => $name,
                            'sets' => $exerciseRows->count(),
                            'evolution' => $evolution,
                        ];
                    })
                    ->sortByDesc('sets')
                    ->values();

                return [
                    'group' => $group,
                    'label' => Exercise::MUSCLE_GROUPS[$group] ?? $group,
                    'exercises' => $exercises,
                ];
            })
            ->all();

        $weeklyFrequency = collect(range(7, 0))->map(function (int $weeksAgo) use ($logs) {
            $start = now()->startOfWeek()->subWeeks($weeksAgo);
            $end = $start->copy()->endOfWeek();
            $weekLogs = $logs->filter(fn (WorkoutLog $log) => $log->started_at->between($start, $end));
            $seconds = (int) $weekLogs->sum('duration_seconds');

            return [
                'label' => $start->format('d/m'),
                'count' => $weekLogs->count(),
                'seconds' => $seconds,
                'hours_label' => $seconds >= 3600
                    ? sprintf('%dh %02dmin', intdiv($seconds, 3600), intdiv($seconds % 3600, 60))
                    : sprintf('%dmin', intdiv($seconds, 60)),
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
            'exercisesByMuscle',
            'muscleDetails',
            'weeklyFrequency',
            'topExercises'
        ));
    }

    /**
     * Normalize masked inputs (comma decimals) before validation.
     *
     * @param  array<int|string, array<string, mixed>>  $sets
     * @return array<int|string, array<string, mixed>>
     */
    private function normalizeSetInputs(array $sets): array
    {
        return collect($sets)
            ->map(function ($row) {
                if (! is_array($row)) {
                    return $row;
                }

                foreach (['weight_kg', 'distance_km'] as $field) {
                    $value = $row[$field] ?? null;

                    if (is_string($value) && str_contains($value, ',')) {
                        $row[$field] = str_replace(['.', ','], ['', '.'], trim($value));
                    }
                }

                return $row;
            })
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function hasSetData(array $row): bool
    {
        foreach (['reps', 'weight_kg', 'distance_km', 'cardio_seconds'] as $field) {
            $value = $row[$field] ?? null;

            if ($value !== null && $value !== '' && (float) $value > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveCardioSeconds(array $row, ?string $plannedLoad): int
    {
        $seconds = (int) ($row['cardio_seconds'] ?? 0);

        if ($seconds > 0) {
            return $seconds;
        }

        if (is_string($plannedLoad) && preg_match('/^(\d{1,3}):([0-5]\d)$/', trim($plannedLoad), $matches) === 1) {
            return ((int) $matches[1] * 60) + (int) $matches[2];
        }

        return 0;
    }

    /**
     * Apply the values recorded in the workout log back to the plan exercises.
     *
     * @param  Collection<int, WorkoutExercise>  $plannedExercises
     * @param  Collection<int, array<string, mixed>>  $rows
     */
    private function updatePlanDefinitions($plannedExercises, $rows): void
    {
        $latestRows = $rows
            ->filter(fn (array $row) => ! empty($row['workout_exercise_id']))
            ->groupBy('workout_exercise_id')
            ->map(fn ($exerciseRows) => $exerciseRows->sortByDesc('set_number')->first());

        foreach ($latestRows as $workoutExerciseId => $row) {
            $planned = $plannedExercises->get((int) $workoutExerciseId);

            if (! $planned) {
                continue;
            }

            $attributes = [];

            if ($planned->isCardio()) {
                $seconds = (int) ($row['cardio_seconds'] ?? 0);

                if ($seconds > 0) {
                    $attributes['suggested_load'] = sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
                }
            } else {
                if (($row['reps'] ?? null) !== null) {
                    $attributes['reps'] = (int) $row['reps'];
                }

                if (! $planned->isLoadDisabled() && ($row['weight_kg'] ?? null) !== null) {
                    $attributes['suggested_load'] = str_replace('.', ',', (string) $row['weight_kg']);
                }
            }

            if ($attributes !== []) {
                $planned->update($attributes);
            }
        }
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
