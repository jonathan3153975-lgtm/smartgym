<?php

namespace App\Http\Controllers;

use App\Models\MembershipPlan;
use App\Models\Network;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentCheckin;
use App\Models\StudentUnit;
use App\Models\Unit;
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
        $activeSubscription = $student?->activeSubscription();
        $assessment = $student?->assessment;
        $trialUsed = $student?->checkins()
            ->where('type', StudentCheckin::TYPE_TRIAL)
            ->exists() ?? false;

        $todayClasses = SchoolClass::query()
            ->with(['unit:id,name,city,state', 'modality:id,name', 'teacher:id,name'])
            ->where('network_id', $network->id)
            ->where('weekday', now()->dayOfWeek)
            ->where('status', 'active')
            ->whereHas('unit', fn ($query) => $query->where('status', 'active'))
            ->orderBy('start_time')
            ->get();

        return view('pages.student-portal.home', compact(
            'network',
            'account',
            'activeSubscription',
            'assessment',
            'trialUsed',
            'todayClasses'
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

        return view('pages.student-portal.dashboard', compact('network', 'account'));
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

        $classes = SchoolClass::query()
            ->with(['unit:id,name', 'teacher:id,name', 'modality:id,name'])
            ->where('network_id', $network->id)
            ->where('status', 'active')
            ->whereNotNull('modality_id')
            ->whereHas('unit', fn ($query) => $query->where('status', 'active'))
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get()
            ->groupBy('modality_id');

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
