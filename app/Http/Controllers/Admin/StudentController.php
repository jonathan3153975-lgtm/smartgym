<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modality;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentAssessment;
use App\Models\StudentSubscription;
use App\Models\StudentUnit;
use App\Support\AssessmentMetrics;
use App\Support\BrazilianDocument;
use App\Support\NetworkPolicy;
use App\Support\StudentAccessSummary;
use App\Support\StudentAssessmentForm;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View|Response
    {
        $tenant = $this->tenant();

        $query = $tenant->scopeStudents(Student::query())
            ->with(['units.unit'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('social_name', 'like', "%{$search}%")
                    ->orWhere('cpf', 'like', "%{$search}%")
                    ->orWhere('cpf_raw', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('whatsapp', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('unit_id')) {
            $query->whereHas('units', fn ($unitQuery) => $unitQuery->where('unit_id', $request->integer('unit_id')));
        }

        $students = $query->paginate(15)->withQueryString();
        $units = $tenant->unitsQuery()->get(['id', 'name']);
        $accessSummaries = StudentAccessSummary::forMany($students->getCollection());

        if ($request->ajax()) {
            return response()->view('pages.students._table', compact('students', 'accessSummaries'));
        }

        return view('pages.students.index', compact('students', 'units', 'accessSummaries'));
    }

    public function show(Student $student): View
    {
        abort_unless($this->tenant()->canAccessStudent($student), 404);

        $student->load([
            'units.unit.region',
            'account',
            'assessment',
            'assessments',
            'createdBy',
            'updatedBy',
        ]);
        $student->loadCount('assessments');

        $latestMeasurement = $student->measurements()->first();
        $access = StudentAccessSummary::for($student);

        $activeSubscriptions = $student->activeSubscriptions()
            ->with(['membershipPlan.units', 'membershipPlan.modalities', 'unit:id,name'])
            ->orderByDesc('starts_at')
            ->get();

        $plans = $activeSubscriptions
            ->map(fn (StudentSubscription $subscription) => $this->planDetails($subscription))
            ->values()
            ->all();

        $previousAssessment = $student->assessments->skip(1)->first();
        $assessmentMetrics = AssessmentMetrics::for($student->assessment, $previousAssessment);

        return view('pages.students.show', compact(
            'student',
            'latestMeasurement',
            'access',
            'plans',
            'previousAssessment',
            'assessmentMetrics'
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function planDetails(StudentSubscription $subscription): array
    {
        $plan = $subscription->membershipPlan;
        $includesClasses = $plan?->includesClasses() ?? $subscription->includesClasses();
        $allUnits = (bool) ($plan?->all_units);

        $unitIds = collect();
        $unitNames = [];

        if ($allUnits) {
            $unitNames = ['Todas'];
        } else {
            $units = $plan?->units ?? collect();
            $unitIds = $units->pluck('id');
            $unitNames = $units->pluck('name')->filter()->values()->all();

            if ($unitIds->isEmpty() && $subscription->unit) {
                $unitIds = collect([$subscription->unit->id]);
                $unitNames = [$subscription->unit->name];
            }
        }

        $allModalities = (bool) ($plan?->all_modalities);
        $isBasic = $plan?->isBasic() ?? $subscription->plan_type === 'basic';
        $modalityIds = collect();
        $modalityNames = [];

        if ($isBasic) {
            $modalityNames = ['Acesso à academia'];
        } elseif ($allModalities) {
            $modalityNames = ['Todas'];
        } else {
            $modalities = $plan?->modalities ?? collect();
            $modalityIds = $modalities->pluck('id');
            $modalityNames = $modalities->pluck('name')->filter()->values()->all();

            if ($modalityIds->isEmpty()) {
                $modalityIds = collect($subscription->modality_ids ?? [])->map(fn ($id) => (int) $id);

                if ($modalityIds->isNotEmpty()) {
                    $modalityNames = Modality::query()
                        ->whereIn('id', $modalityIds)
                        ->orderBy('name')
                        ->pluck('name')
                        ->all();
                }
            }
        }

        $classes = collect();

        if ($includesClasses) {
            $classesQuery = SchoolClass::query()
                ->where('network_id', $subscription->network_id)
                ->where('status', 'active')
                ->with(['schedules', 'unit:id,name', 'modality:id,name'])
                ->orderBy('name');

            if (! $allUnits && $unitIds->isNotEmpty()) {
                $classesQuery->whereIn('unit_id', $unitIds);
            }

            if (! $allModalities && ! $isBasic && $modalityIds->isNotEmpty()) {
                $classesQuery->whereIn('modality_id', $modalityIds);
            }

            $classes = $classesQuery
                ->limit(60)
                ->get()
                ->map(fn (SchoolClass $class) => [
                    'name' => $class->name,
                    'unit' => $class->unit?->name,
                    'modality' => $class->modality?->name,
                    'schedules' => $class->schedules
                        ->map(fn ($schedule) => $schedule->label)
                        ->filter()
                        ->values()
                        ->all(),
                ]);
        }

        return [
            'id' => $subscription->id,
            'name' => $subscription->plan_name ?: ($plan?->name ?? 'Plano'),
            'status' => $subscription->status_label,
            'period' => $plan?->period_label ?? $subscription->period_label,
            'type' => $plan?->type_label ?? $subscription->plan_type_label,
            'price' => $subscription->price_label,
            'starts_at' => $subscription->starts_at?->format('d/m/Y'),
            'ends_at' => $subscription->ends_at?->format('d/m/Y'),
            'weekly_accesses' => $subscription->weekly_accesses,
            'daily_checkins' => $subscription->daily_checkins,
            'checkin_advance_minutes' => $subscription->checkin_advance_minutes,
            'units' => $unitNames,
            'modalities' => $modalityNames,
            'includes_classes' => $includesClasses,
            'classes' => $classes->all(),
        ];
    }

    public function createAssessment(Request $request, Student $student): View
    {
        abort_unless($this->tenant()->canAccessStudent($student), 404);
        abort_unless($request->user()?->canCreateAssessments(), 403);

        $student->load(['assessment', 'units.unit']);

        return view('pages.students.assessment-form', [
            'student' => $student,
            'assessment' => $student->assessment,
        ]);
    }

    public function storeAssessment(Request $request, Student $student): RedirectResponse
    {
        $tenant = $this->tenant();

        abort_unless($tenant->canAccessStudent($student), 404);
        abort_unless($request->user()?->canCreateAssessments(), 403);

        StudentAssessmentForm::normalizeDecimals($request);

        $validated = $request->validate(StudentAssessmentForm::rules());

        $parqAnswers = StudentAssessmentForm::parqAnswers($validated['parq_answers']);

        $payload = Arr::except($validated, ['parq_answers', 'smoker', 'consent']);

        StudentAssessment::create(array_merge($payload, [
            'network_id' => $tenant->networkId(),
            'student_id' => $student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'smoker' => filter_var($validated['smoker'] ?? false, FILTER_VALIDATE_BOOL),
            'parq_answers' => $parqAnswers,
            'parq_positive' => in_array(true, $parqAnswers, true),
            'consent' => true,
            'submitted_at' => now(),
        ]));

        return redirect()
            ->route('students.technical-sheet', $student)
            ->with('success', 'Nova avaliação registrada para o aluno.');
    }

    public function editData(Student $student): JsonResponse
    {
        abort_unless($this->tenant()->canAccessStudent($student), 404);

        $primary = $student->units->firstWhere('is_primary');
        $additional = $student->units->where('is_primary', false)->pluck('unit_id')->values();

        return response()->json([
            'id' => $student->id,
            'name' => $student->name,
            'social_name' => $student->social_name,
            'cpf' => $student->cpf,
            'birth_date' => $student->birth_date?->format('Y-m-d'),
            'gender' => $student->gender,
            'email' => $student->email,
            'phone' => $student->phone,
            'whatsapp' => $student->whatsapp,
            'zip_code' => $student->zip_code,
            'street' => $student->street,
            'number' => $student->number,
            'complement' => $student->complement,
            'neighborhood' => $student->neighborhood,
            'city' => $student->city,
            'state' => $student->state,
            'emergency_contact_name' => $student->emergency_contact_name,
            'emergency_contact_phone' => $student->emergency_contact_phone,
            'emergency_contact_relationship' => $student->emergency_contact_relationship,
            'avatar_url' => $student->avatar_url,
            'admin_notes' => $student->admin_notes,
            'source' => $student->source,
            'status' => $student->status,
            'primary_unit_id' => $primary?->unit_id,
            'additional_unit_ids' => $additional,
            'has_access' => $student->account !== null,
            'access_email' => $student->account?->email,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        $request->merge(['network_id' => $tenant->networkId()]);

        $validated = $this->validateStudent($request, $tenant);
        $validated['network_id'] = $tenant->networkId();

        $student = DB::transaction(function () use ($validated, $request, $tenant) {
            $data = $this->payload($validated);
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            if ($request->hasFile('avatar')) {
                $data['avatar'] = $request->file('avatar')->store('students/'.$tenant->networkId(), 'public');
            }

            $student = Student::create($data);
            $this->syncUnits($student, $validated);
            $this->syncAccount($student, $validated);

            return $student;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aluno cadastrado com sucesso.',
                'data' => $student,
            ]);
        }

        return redirect()->route('students.index')->with('success', 'Aluno cadastrado com sucesso.');
    }

    public function update(Request $request, Student $student): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessStudent($student), 404);
        $request->merge(['network_id' => $tenant->networkId()]);

        $validated = $this->validateStudent($request, $tenant, $student);
        $validated['network_id'] = $tenant->networkId();

        DB::transaction(function () use ($student, $validated, $request) {
            $data = $this->payload($validated);
            $data['updated_by'] = auth()->id();
            $currentAvatar = $student->avatar;

            if ($request->boolean('remove_avatar') && $currentAvatar) {
                Storage::disk('public')->delete($currentAvatar);
                $data['avatar'] = null;
                $currentAvatar = null;
            }

            if ($request->hasFile('avatar')) {
                $data['avatar'] = $request->file('avatar')->store('students/'.$student->network_id, 'public');

                if ($currentAvatar) {
                    Storage::disk('public')->delete($currentAvatar);
                }
            }

            $student->update($data);
            $this->syncUnits($student, $validated);
            $this->syncAccount($student, $validated);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aluno atualizado com sucesso.',
                'data' => $student->fresh(),
            ]);
        }

        return redirect()->route('students.index')->with('success', 'Aluno atualizado com sucesso.');
    }

    public function toggleStatus(Student $student): JsonResponse|RedirectResponse
    {
        abort_unless($this->tenant()->canAccessStudent($student), 404);

        $student->update([
            'status' => $student->status === 'active' ? 'inactive' : 'active',
            'updated_by' => auth()->id(),
        ]);

        $message = $student->status === 'active'
            ? 'Aluno ativado com sucesso.'
            : 'Aluno inativado com sucesso.';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $student->status,
            ]);
        }

        return redirect()->route('students.index')->with('success', $message);
    }

    public function destroy(Student $student): RedirectResponse|JsonResponse
    {
        abort_unless($this->tenant()->canAccessStudent($student), 404);

        if ($student->avatar) {
            Storage::disk('public')->delete($student->avatar);
        }

        $student->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Aluno removido com sucesso.',
            ]);
        }

        return redirect()->route('students.index')->with('success', 'Aluno removido com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStudent(Request $request, TenantContext $tenant, ?Student $student = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'social_name' => ['nullable', 'string', 'max:255'],
            'cpf' => ['nullable', 'string', 'max:14'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'prefer_not_to_say'])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'zip_code' => ['nullable', 'string', 'max:9'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'size:2'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            'avatar' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:50'],
            'status' => ['required', Rule::in(['active', 'inactive', 'pending'])],
            'primary_unit_id' => ['required', 'integer', 'exists:units,id'],
            'additional_unit_ids' => ['nullable', 'array'],
            'additional_unit_ids.*' => ['integer', 'exists:units,id'],
            'access_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $accessibleUnits = $tenant->unitsQuery()->pluck('id')->map(fn ($id) => (int) $id);
        $primaryUnitId = (int) $validated['primary_unit_id'];

        if (! $accessibleUnits->contains($primaryUnitId)) {
            throw ValidationException::withMessages([
                'primary_unit_id' => 'Selecione uma unidade que você pode acessar.',
            ]);
        }

        $additionalUnits = collect($validated['additional_unit_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->reject(fn ($id) => $id === $primaryUnitId)
            ->values();

        if ($additionalUnits->diff($accessibleUnits)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'additional_unit_ids' => 'Selecione apenas unidades que você pode acessar.',
            ]);
        }

        $network = $tenant->network();
        $shareAcrossUnits = NetworkPolicy::get($network, 'pessoas', 'share_across_units', true);

        if (! $shareAcrossUnits && $additionalUnits->isNotEmpty()) {
            throw ValidationException::withMessages([
                'additional_unit_ids' => 'As configurações da rede não permitem vincular o aluno a mais de uma unidade.',
            ]);
        }

        $validated['additional_unit_ids'] = $additionalUnits->all();

        if (! empty($validated['cpf'])) {
            if (! BrazilianDocument::isValidCpf($validated['cpf'])) {
                throw ValidationException::withMessages([
                    'cpf' => 'Informe um CPF válido.',
                ]);
            }

            $raw = BrazilianDocument::onlyDigits($validated['cpf']);

            $this->validateCpfUniqueness($raw, $primaryUnitId, $tenant, $student);

            $validated['cpf'] = BrazilianDocument::formatCpf($raw);
            $validated['cpf_raw'] = $raw;
        } else {
            $validated['cpf'] = null;
            $validated['cpf_raw'] = null;
        }

        if (! empty($validated['state'])) {
            $validated['state'] = strtoupper($validated['state']);
        }

        $this->validateAccessPassword($validated, $tenant, $student);

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function validateAccessPassword(array $validated, TenantContext $tenant, ?Student $student): void
    {
        if (empty($validated['access_password'])) {
            return;
        }

        if (empty($validated['email'])) {
            throw ValidationException::withMessages([
                'access_password' => 'Informe o e-mail do aluno para criar o acesso.',
            ]);
        }

        $existing = StudentAccount::query()
            ->where('network_id', $tenant->networkId())
            ->where('email', $validated['email'])
            ->when($student, fn ($query) => $query->where('student_id', '!=', $student->id))
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'email' => 'Este e-mail já está sendo usado por outro acesso de aluno.',
            ]);
        }
    }

    private function validateCpfUniqueness(string $raw, int $primaryUnitId, TenantContext $tenant, ?Student $student): void
    {
        $network = $tenant->network();
        $scope = NetworkPolicy::get($network, 'pessoas', 'cpf_unique_scope', 'network');

        $query = Student::query()
            ->where('network_id', $tenant->networkId())
            ->where('cpf_raw', $raw);

        if ($student) {
            $query->whereKeyNot($student->id);
        }

        if ($scope === 'unit') {
            $query->whereHas('units', fn ($unitQuery) => $unitQuery->where('unit_id', $primaryUnitId));
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'cpf' => 'Já existe um aluno cadastrado com este CPF.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated): array
    {
        return [
            'network_id' => $validated['network_id'],
            'name' => $validated['name'],
            'social_name' => $validated['social_name'] ?? null,
            'cpf' => $validated['cpf'] ?? null,
            'cpf_raw' => $validated['cpf_raw'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'whatsapp' => $validated['whatsapp'] ?? null,
            'zip_code' => $validated['zip_code'] ?? null,
            'street' => $validated['street'] ?? null,
            'number' => $validated['number'] ?? null,
            'complement' => $validated['complement'] ?? null,
            'neighborhood' => $validated['neighborhood'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
            'emergency_contact_relationship' => $validated['emergency_contact_relationship'] ?? null,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'source' => $validated['source'] ?? null,
            'status' => $validated['status'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncAccount(Student $student, array $validated): void
    {
        if (empty($validated['access_password'])) {
            return;
        }

        $account = $student->account;
        $data = [
            'network_id' => $student->network_id,
            'student_id' => $student->id,
            'email' => $student->email,
            'password' => $validated['access_password'],
            'must_change_password' => true,
            'status' => 'active',
        ];

        if (! $account) {
            $data['email_verified_at'] = now();
        }

        if ($account) {
            $account->update($data);

            return;
        }

        StudentAccount::create($data);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncUnits(Student $student, array $validated): void
    {
        $student->units()->delete();

        $primaryUnitId = (int) $validated['primary_unit_id'];
        $unitIds = collect([$primaryUnitId])
            ->merge($validated['additional_unit_ids'] ?? [])
            ->unique()
            ->values();

        foreach ($unitIds as $unitId) {
            StudentUnit::create([
                'student_id' => $student->id,
                'network_id' => $student->network_id,
                'unit_id' => $unitId,
                'status' => 'active',
                'is_primary' => (int) $unitId === $primaryUnitId,
                'starts_at' => now()->toDateString(),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        }
    }

    private function tenant(): TenantContext
    {
        $tenant = TenantContext::current();

        abort_if($tenant->isPlatform(), 403, 'Selecione uma rede para gerenciar alunos.');
        abort_unless($tenant->networkId(), 403, 'Selecione uma rede para gerenciar alunos.');

        return $tenant;
    }
}
