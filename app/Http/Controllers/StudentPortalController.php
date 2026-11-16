<?php

namespace App\Http\Controllers;

use App\Models\ClassBooking;
use App\Models\ClassSchedule;
use App\Models\Exercise;
use App\Models\MembershipPlan;
use App\Models\Network;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentCheckin;
use App\Models\StudentUnit;
use App\Models\Unit;
use App\Models\WorkoutLog;
use App\Support\AssessmentMetrics;
use App\Support\BrazilianDocument;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentPortalController extends Controller
{
    public function index(Network $network): RedirectResponse
    {
        $account = Auth::guard('student')->user();

        if ($account && (int) $account->network_id === (int) $network->id) {
            return redirect($this->landingRoute($account, $network));
        }

        return redirect()->route('student.portal.login', $network);
    }

    public function showLogin(Network $network): View|RedirectResponse
    {
        $account = Auth::guard('student')->user();

        if ($account && (int) $account->network_id === (int) $network->id) {
            return redirect($this->landingRoute($account, $network));
        }

        return view('pages.student-portal.auth.login', compact('network'));
    }

    public function showRegister(Network $network): View|RedirectResponse
    {
        $account = Auth::guard('student')->user();

        if ($account && (int) $account->network_id === (int) $network->id) {
            return redirect($this->landingRoute($account, $network));
        }

        $units = $network->units()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'state']);

        return view('pages.student-portal.auth.register', compact('network', 'units'));
    }

    public function register(Request $request, Network $network): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'cpf' => ['nullable', 'string', 'max:14'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $unit = $network->units()
            ->where('status', 'active')
            ->whereKey($validated['unit_id'])
            ->first();

        if (! $unit) {
            throw ValidationException::withMessages([
                'unit_id' => 'Selecione uma unidade válida desta rede.',
            ]);
        }

        $email = mb_strtolower(trim($validated['email']));

        if (StudentAccount::query()->where('network_id', $network->id)->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Já existe um acesso com este e-mail nesta rede.',
            ]);
        }

        $cpf = $validated['cpf'] ?? null;
        $cpfRaw = null;

        if (! empty($cpf)) {
            if (! BrazilianDocument::isValidCpf($cpf)) {
                throw ValidationException::withMessages([
                    'cpf' => 'Informe um CPF válido.',
                ]);
            }

            $cpfRaw = BrazilianDocument::onlyDigits($cpf);

            $cpfInUse = Student::query()
                ->where('network_id', $network->id)
                ->where('cpf_raw', $cpfRaw)
                ->exists();

            if ($cpfInUse) {
                throw ValidationException::withMessages([
                    'cpf' => 'Já existe um aluno cadastrado com este CPF nesta rede.',
                ]);
            }

            $cpf = BrazilianDocument::formatCpf($cpfRaw);
        }

        $account = DB::transaction(function () use ($validated, $network, $unit, $email, $cpf, $cpfRaw) {
            $student = Student::create([
                'network_id' => $network->id,
                'name' => $validated['name'],
                'email' => $email,
                'phone' => $validated['phone'] ?? null,
                'whatsapp' => $validated['phone'] ?? null,
                'cpf' => $cpf,
                'cpf_raw' => $cpfRaw,
                'birth_date' => $validated['birth_date'] ?? null,
                'source' => 'portal',
                'status' => 'active',
            ]);

            StudentUnit::create([
                'student_id' => $student->id,
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'status' => 'active',
                'is_primary' => true,
                'starts_at' => now()->toDateString(),
            ]);

            return StudentAccount::create([
                'network_id' => $network->id,
                'student_id' => $student->id,
                'email' => $email,
                'password' => $validated['password'],
                'email_verified_at' => now(),
                'must_change_password' => false,
                'status' => 'active',
            ]);
        });

        Auth::guard('student')->login($account);
        $request->session()->regenerate();

        return redirect()
            ->route('student.portal.plans', $network)
            ->with('success', 'Cadastro concluído! Escolha um plano para começar.');
    }

    public function login(Request $request, Network $network): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('student')->check()) {
            Auth::guard('student')->logout();
        }

        $authenticated = Auth::guard('student')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'network_id' => $network->id,
            'status' => 'active',
        ], $request->boolean('remember'));

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'email' => 'E-mail ou senha inválidos para esta rede.',
            ]);
        }

        $request->session()->regenerate();

        /** @var StudentAccount $account */
        $account = Auth::guard('student')->user();
        $account->forceFill(['last_login_at' => now()])->save();

        if ($account->must_change_password) {
            return redirect()->route('student.portal.password.edit', $network);
        }

        return redirect()->intended($this->landingRoute($account, $network));
    }

    public function logout(Request $request, Network $network): RedirectResponse
    {
        Auth::guard('student')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('student.portal.login', $network);
    }

    public function home(Network $network): View
    {
        /** @var StudentAccount $account */
        $account = Auth::guard('student')->user();
        $account->load(['student.units.unit']);

        $student = $account->student;

        $activeSubscriptions = $student
            ? $student->activeSubscriptions()->with(['membershipPlan.units', 'unit'])->get()
            : collect();

        $activeSubscription = $activeSubscriptions->first();
        $hasTrainingAccess = $activeSubscriptions->contains(
            fn ($subscription) => $subscription->plan_type !== MembershipPlan::TYPE_CLASSES
        );
        $hasClassAccess = $activeSubscriptions->contains(
            fn ($subscription) => $subscription->includesClasses()
        );

        $assessment = $student?->assessment;
        $previousAssessment = null;

        if ($assessment) {
            $previousAssessment = $student->assessments()
                ->whereKeyNot($assessment->id)
                ->where(function ($query) use ($assessment) {
                    $query->where('submitted_at', '<', $assessment->submitted_at)
                        ->orWhere(fn ($sameTime) => $sameTime
                            ->where('submitted_at', $assessment->submitted_at)
                            ->where('id', '<', $assessment->id));
                })
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->first();
        }

        $assessmentMetrics = AssessmentMetrics::for($assessment, $previousAssessment);

        $trialUsed = $student?->checkins()
            ->where('type', StudentCheckin::TYPE_TRIAL)
            ->exists() ?? false;

        $pendingCharges = $student
            ? $student->charges()->where('status', 'pending')->orderBy('due_date')->get()
            : collect();

        $notifications = $student
            ? $student->notifications()->whereNull('read_at')->take(5)->get()
            : collect();

        $accessibleUnits = collect();

        foreach ($activeSubscriptions as $subscription) {
            if ($subscription->membershipPlan?->all_units) {
                $accessibleUnits = $accessibleUnits->merge(
                    $network->units()
                        ->where('status', 'active')
                        ->get(['id', 'name', 'street', 'number', 'neighborhood', 'city', 'state', 'latitude', 'longitude'])
                );

                continue;
            }

            if ($subscription->unit) {
                $accessibleUnits->push($subscription->unit);
            }

            if ($subscription->membershipPlan) {
                $accessibleUnits = $accessibleUnits->merge($subscription->membershipPlan->units);
            }
        }

        $accessibleUnits = $accessibleUnits->filter()->unique('id')->values();

        if ($accessibleUnits->isEmpty()) {
            $accessibleUnits = $student?->units
                ?->map(fn (StudentUnit $studentUnit) => $studentUnit->unit)
                ->filter()
                ->unique('id')
                ->values() ?? collect();
        }

        $primaryUnit = $student?->units?->firstWhere('is_primary')?->unit
            ?? $accessibleUnits->first();

        $todaySchedules = ClassSchedule::query()
            ->with([
                'schoolClass.unit:id,name,city,state',
                'schoolClass.modality:id,name',
                'person:id,name',
                'teacher:id,name',
            ])
            ->where('weekday', now()->dayOfWeek)
            ->whereHas('schoolClass', fn ($query) => $query
                ->where('network_id', $network->id)
                ->where('status', 'active')
                ->whereHas('unit', fn ($unitQuery) => $unitQuery->where('status', 'active')))
            ->orderBy('start_time')
            ->get();

        $todayClasses = $hasClassAccess
            ? $todaySchedules->filter(function (ClassSchedule $schedule) use ($activeSubscriptions, $accessibleUnits) {
                $class = $schedule->schoolClass;

                if (! $class || ! $accessibleUnits->contains('id', $class->unit_id)) {
                    return false;
                }

                return $activeSubscriptions->contains(
                    fn ($subscription) => $subscription->includesModality($class->modality_id)
                );
            })->values()
            : collect();

        $lastClassCheckin = $student
            ? $student->classBookings()
                ->whereNotNull('checked_in_at')
                ->with(['schoolClass.unit:id,name', 'schoolClass.modality:id,name', 'schedule:id,start_time'])
                ->latest('checked_in_at')
                ->first()
            : null;

        $openClassBookings = $student
            ? $student->classBookings()
                ->whereIn('status', [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED])
                ->whereDate('booked_for', '>=', now()->toDateString())
                ->with(['schoolClass.unit:id,name', 'schoolClass.modality:id,name', 'schedule:id,start_time'])
                ->orderBy('booked_for')
                ->get()
            : collect();

        $latestWorkout = $student
            ? $student->workoutLogs()
                ->where('status', WorkoutLog::STATUS_COMPLETED)
                ->orderByDesc('started_at')
                ->first()
            : null;

        return view('pages.student-portal.home', compact(
            'network',
            'account',
            'student',
            'activeSubscription',
            'activeSubscriptions',
            'hasTrainingAccess',
            'hasClassAccess',
            'assessment',
            'assessmentMetrics',
            'trialUsed',
            'todayClasses',
            'pendingCharges',
            'notifications',
            'accessibleUnits',
            'primaryUnit',
            'lastClassCheckin',
            'openClassBookings',
            'latestWorkout'
        ));
    }

    public function showPasswordForm(Network $network): View
    {
        /** @var StudentAccount $account */
        $account = Auth::guard('student')->user();

        return view('pages.student-portal.auth.password', compact('network', 'account'));
    }

    public function updatePassword(Request $request, Network $network): RedirectResponse
    {
        /** @var StudentAccount $account */
        $account = Auth::guard('student')->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $account->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'A senha atual informada está incorreta.',
            ]);
        }

        $account->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
        ])->save();

        return redirect()
            ->to($this->landingRoute($account, $network))
            ->with('success', 'Senha alterada com sucesso. Bem-vindo(a) ao portal!');
    }

    public function dashboard(Network $network): View
    {
        /** @var StudentAccount $account */
        $account = Auth::guard('student')->user();
        $account->load(['student.units.unit']);

        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $activeSubscriptions = $student->activeSubscriptions()
            ->with('membershipPlan')
            ->get();

        $activeSubscription = $activeSubscriptions->first();
        $referenceSubscription = $activeSubscriptions->sortByDesc('weekly_accesses')->first();
        $weeklyAccess = max(1, (int) ($referenceSubscription?->weekly_accesses ?? 3));

        $windowStart = now()->subDays(29)->startOfDay();
        $planStart = $referenceSubscription?->starts_at?->copy()->startOfDay();
        $since = $planStart && $planStart->gt($windowStart) ? $planStart : $windowStart;

        $daysInWindow = max(1, (int) $since->diffInDays(now()->startOfDay()) + 1);
        $expectedDays = min($daysInWindow, max(1, (int) round($weeklyAccess * 4 * ($daysInWindow / 30))));

        $workoutLogs = $student->workoutLogs()
            ->where('status', WorkoutLog::STATUS_COMPLETED)
            ->where('started_at', '>=', $since)
            ->where('started_at', '<=', now())
            ->with('sets')
            ->get();

        $checkins = $student->checkins()
            ->where('checked_in_at', '>=', $since)
            ->where('checked_in_at', '<=', now())
            ->get();

        $classCheckins = $student->classBookings()
            ->whereNotNull('checked_in_at')
            ->where('checked_in_at', '>=', $since)
            ->where('checked_in_at', '<=', now())
            ->with(['schoolClass.modality:id,name'])
            ->get();

        $activityDates = collect()
            ->merge($workoutLogs->pluck('started_at'))
            ->merge($checkins->pluck('checked_in_at'))
            ->merge($classCheckins->pluck('checked_in_at'))
            ->filter()
            ->map(fn ($date) => $date->toDateString())
            ->unique();

        $actualDays = $activityDates->count();
        $frequencyPercent = (int) min(100, round(($actualDays / $expectedDays) * 100));

        $allSets = $workoutLogs->flatMap->sets;
        $totalReps = max(1, (int) $allSets->sum(fn ($set) => (int) $set->reps));

        $muscleGroups = $allSets
            ->groupBy('muscle_group')
            ->map(function ($sets, $group) use ($totalReps) {
                $reps = (int) $sets->sum(fn ($set) => (int) $set->reps);

                return [
                    'group' => $group,
                    'label' => Exercise::MUSCLE_GROUPS[$group] ?? (string) $group,
                    'reps' => $reps,
                    'percent' => (int) round(($reps / $totalReps) * 100),
                ];
            })
            ->sortByDesc('reps')
            ->values();

        $muscleGroupExercises = $allSets
            ->groupBy('muscle_group')
            ->map(function ($sets) {
                $groupReps = max(1, (int) $sets->sum(fn ($set) => (int) $set->reps));

                return $sets
                    ->groupBy('exercise_name')
                    ->map(function ($exerciseSets, $name) use ($groupReps) {
                        $reps = (int) $exerciseSets->sum(fn ($set) => (int) $set->reps);
                        $maxWeight = (float) $exerciseSets->max(fn ($set) => (float) $set->weight_kg);

                        return [
                            'name' => $name,
                            'reps' => $reps,
                            'max_weight' => $maxWeight,
                            'max_weight_label' => $maxWeight > 0
                                ? rtrim(rtrim(number_format($maxWeight, 2, ',', '.'), '0'), ',').' kg'
                                : '—',
                            'percent' => (int) round(($reps / $groupReps) * 100),
                        ];
                    })
                    ->sortByDesc('reps')
                    ->values()
                    ->all();
            })
            ->all();

        $hasClassAccess = $activeSubscriptions->contains(
            fn ($subscription) => $subscription->includesClasses()
        );

        $classFrequency = null;
        $classModalities = collect();

        if ($hasClassAccess) {
            $classDates = $classCheckins
                ->map(fn (ClassBooking $booking) => $booking->checked_in_at?->toDateString())
                ->filter()
                ->unique();

            $totalClassCheckins = max(1, $classCheckins->count());

            $classModalities = $classCheckins
                ->groupBy(fn (ClassBooking $booking) => $booking->schoolClass?->modality?->name ?? 'Sem modalidade')
                ->map(fn ($bookings, $name) => [
                    'name' => $name,
                    'count' => $bookings->count(),
                    'percent' => (int) round(($bookings->count() / $totalClassCheckins) * 100),
                ])
                ->sortByDesc('count')
                ->values();

            $classFrequency = [
                'percent' => (int) min(100, round(($classDates->count() / $expectedDays) * 100)),
                'attended' => $classDates->count(),
                'expected' => $expectedDays,
            ];
        }

        $currentAssessment = $student->assessment;
        $previousAssessment = null;

        if ($currentAssessment) {
            $previousAssessment = $student->assessments()
                ->whereKeyNot($currentAssessment->id)
                ->where(function ($query) use ($currentAssessment) {
                    $query->where('submitted_at', '<', $currentAssessment->submitted_at)
                        ->orWhere(fn ($sameTime) => $sameTime
                            ->where('submitted_at', $currentAssessment->submitted_at)
                            ->where('id', '<', $currentAssessment->id));
                })
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->first();
        }

        $assessmentDeltas = AssessmentMetrics::for($currentAssessment, $previousAssessment);

        return view('pages.student-portal.dashboard', compact(
            'network',
            'account',
            'student',
            'activeSubscription',
            'frequencyPercent',
            'actualDays',
            'expectedDays',
            'muscleGroups',
            'muscleGroupExercises',
            'classFrequency',
            'classModalities',
            'currentAssessment',
            'previousAssessment',
            'assessmentDeltas'
        ));
    }

    public function profile(Network $network): View
    {
        /** @var StudentAccount $account */
        $account = Auth::guard('student')->user();
        $account->load(['student.units.unit']);

        return view('pages.student-portal.profile', compact('network', 'account'));
    }

    public function updateProfile(Request $request, Network $network): RedirectResponse
    {
        /** @var StudentAccount $account */
        $account = Auth::guard('student')->user();
        $student = $account->student;

        abort_unless($student instanceof Student, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ]);

        $email = mb_strtolower(trim($validated['email']));

        $emailInUse = StudentAccount::query()
            ->where('network_id', $network->id)
            ->where('email', $email)
            ->whereKeyNot($account->id)
            ->exists();

        if ($emailInUse) {
            throw ValidationException::withMessages([
                'email' => 'Já existe um acesso com este e-mail nesta rede.',
            ]);
        }

        DB::transaction(function () use ($student, $account, $validated, $email) {
            $student->update([
                'name' => $validated['name'],
                'email' => $email,
                'phone' => $validated['phone'] ?? null,
                'whatsapp' => $validated['whatsapp'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
            ]);

            $account->forceFill(['email' => $email])->save();
        });

        return redirect()
            ->route('student.portal.profile', $network)
            ->with('success', 'Dados pessoais atualizados com sucesso.');
    }

    public function plans(Network $network): View
    {
        /** @var StudentAccount $account */
        $account = Auth::guard('student')->user();
        $account->load(['student.units.unit']);

        $student = $account->student;
        $primaryUnit = $student?->units?->firstWhere('is_primary') ?? $student?->units?->first();
        $studentUnit = $primaryUnit?->unit;

        $activeSubscription = $student?->activeSubscription();
        $plans = $studentUnit ? $this->eligiblePlans($studentUnit) : collect();
        $units = $network->units()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $classes = ClassSchedule::query()
            ->with(['schoolClass.unit:id,name', 'schoolClass.modality:id,name', 'person:id,name', 'teacher:id,name'])
            ->whereHas('schoolClass', fn ($query) => $query
                ->where('network_id', $network->id)
                ->where('status', 'active')
                ->whereNotNull('modality_id')
                ->whereHas('unit', fn ($unitQuery) => $unitQuery->where('status', 'active')))
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($schedule) => $schedule->schoolClass?->modality_id);

        return view('pages.student-portal.plans', compact(
            'network',
            'account',
            'student',
            'studentUnit',
            'activeSubscription',
            'plans',
            'units',
            'classes'
        ));
    }

    /**
     * Plans available for the student's primary unit, respecting the level the plan was created at.
     *
     * @return Collection<int, MembershipPlan>
     */
    private function eligiblePlans(Unit $unit)
    {
        return MembershipPlan::query()
            ->with(['units:id,name', 'modalities:id,name'])
            ->eligibleForUnit($unit)
            ->orderBy('price')
            ->get();
    }

    /**
     * Route the student should land on after authentication. Students without an active
     * subscription are sent to the plan catalog.
     */
    private function landingRoute(StudentAccount $account, Network $network): string
    {
        $account->loadMissing('student');

        if ($account->student?->activeSubscription()) {
            return route('student.portal.home', $network);
        }

        return route('student.portal.plans', $network);
    }
}
