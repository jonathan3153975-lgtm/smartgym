<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Network;
use App\Models\Person;
use App\Models\Unit;
use App\Support\BrazilianDocument;
use App\Support\NetworkPolicy;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PersonController extends Controller
{
    /**
     * Display a listing of the people.
     */
    public function index(Request $request): View|Response
    {
        $tenant = TenantContext::current();

        $query = $tenant->scopePeople(Person::query())
            ->with('unit')
            ->withCount('users')
            ->orderBy('name');

        if ($request->filled('search')) {
            $query->search($request->string('search')->toString());
        }

        if ($request->filled('person_type')) {
            $query->where('person_type', $request->string('person_type')->toString());
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category')->toString());
        }

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->integer('unit_id'));
        }

        $people = $query->paginate(15)->withQueryString();
        $units = $tenant->unitsQuery()->get(['id', 'name']);

        if ($request->ajax()) {
            return response()->view('pages.admin.people._table', compact('people'));
        }

        return view('pages.admin.people.index', compact('people', 'units'));
    }

    /**
     * Display the specified person.
     */
    public function show(Person $person): View
    {
        abort_unless(TenantContext::current()->canAccessPerson($person), 404);

        $person->load([
            'network',
            'unit',
            'users' => fn ($query) => $query->with('vinculos.role')->orderBy('name'),
            'createdBy',
            'updatedBy',
        ]);

        return view('pages.admin.people.show', compact('person'));
    }

    /**
     * Return the person data as JSON (used by the edit modal).
     */
    public function editData(Person $person): JsonResponse
    {
        abort_unless(TenantContext::current()->canAccessPerson($person), 404);

        $data = $person->only([
            'id',
            'network_id',
            'unit_id',
            'person_type',
            'category',
            'name',
            'trade_name',
            'document',
            'state_registration',
            'legal_representative_name',
            'legal_representative_document',
            'email',
            'phone',
            'whatsapp',
            'zip_code',
            'street',
            'number',
            'complement',
            'neighborhood',
            'city',
            'state',
            'notes',
            'status',
        ]);

        $data['birth_date'] = $person->birth_date?->format('Y-m-d');

        return response()->json($data);
    }

    /**
     * Store a newly created person.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::current();

        abort_unless($tenant->isPlatform() || $tenant->networkId(), 403);

        if (! $tenant->isPlatform()) {
            $request->merge(['network_id' => $tenant->networkId()]);
        }

        $validated = $this->validatePerson($request);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $person = DB::transaction(fn (): Person => Person::create($validated));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pessoa cadastrada com sucesso.',
                'data' => $person,
            ]);
        }

        return redirect()->route('admin.people.index')
            ->with('success', 'Pessoa cadastrada com sucesso.');
    }

    /**
     * Update the specified person.
     */
    public function update(Request $request, Person $person): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::current();

        abort_unless($tenant->canAccessPerson($person), 404);

        if (! $tenant->isPlatform()) {
            $request->merge(['network_id' => $tenant->networkId()]);
        }

        $validated = $this->validatePerson($request, $person);
        $validated['updated_by'] = auth()->id();

        $person->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pessoa atualizada com sucesso.',
                'data' => $person->fresh(),
            ]);
        }

        return redirect()->route('admin.people.index')
            ->with('success', 'Pessoa atualizada com sucesso.');
    }

    /**
     * Toggle the status of the person.
     */
    public function toggleStatus(Person $person): JsonResponse|RedirectResponse
    {
        abort_unless(TenantContext::current()->canAccessPerson($person), 404);

        $person->update([
            'status' => $person->status === 'active' ? 'inactive' : 'active',
            'updated_by' => auth()->id(),
        ]);

        $message = $person->status === 'active'
            ? 'Pessoa ativada com sucesso.'
            : 'Pessoa inativada com sucesso.';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $person->status,
            ]);
        }

        return redirect()->route('admin.people.index')->with('success', $message);
    }

    /**
     * Remove the specified person.
     */
    public function destroy(Person $person): RedirectResponse|JsonResponse
    {
        abort_unless(TenantContext::current()->canAccessPerson($person), 404);

        if ($person->users()->exists()) {
            $message = 'Esta pessoa possui usuários vinculados e não pode ser excluída.';

            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('admin.people.index')->with('error', $message);
        }

        $person->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pessoa removida com sucesso.',
            ]);
        }

        return redirect()->route('admin.people.index')
            ->with('success', 'Pessoa removida com sucesso.');
    }

    /**
     * Validate the person payload according to the network policies.
     *
     * @return array<string, mixed>
     */
    private function validatePerson(Request $request, ?Person $person = null): array
    {
        $validated = $request->validate([
            'network_id' => ['nullable', 'exists:networks,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'person_type' => ['required', Rule::in(['individual', 'company'])],
            'category' => ['required', Rule::in(['staff', 'client'])],
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'document' => ['required', 'string', 'max:18'],
            'state_registration' => ['nullable', 'string', 'max:30'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'legal_representative_name' => ['nullable', 'string', 'max:255'],
            'legal_representative_document' => ['nullable', 'string', 'max:14'],
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
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $personType = $validated['person_type'];

        if (empty($validated['network_id']) && $person?->network_id) {
            $validated['network_id'] = $person->network_id;
        }

        if (empty($validated['network_id']) && ! empty($validated['unit_id'])) {
            $validated['network_id'] = Unit::find($validated['unit_id'])?->network_id;
        }

        if (! BrazilianDocument::isValid($validated['document'], $personType)) {
            throw ValidationException::withMessages([
                'document' => $personType === 'company'
                    ? 'Informe um CNPJ válido.'
                    : 'Informe um CPF válido.',
            ]);
        }

        $this->validateUnitScope($validated);

        $tenant = TenantContext::current();

        if (! $tenant->isPlatform() && empty($validated['unit_id']) && ($tenantUnitId = $tenant->unitId())) {
            $validated['unit_id'] = $tenantUnitId;
        }

        $this->validateDocumentScope($validated, $person);

        $validated['document_raw'] = BrazilianDocument::onlyDigits($validated['document']);
        $validated['document'] = BrazilianDocument::format($validated['document'], $personType);

        if (! empty($validated['legal_representative_document'])) {
            if (! BrazilianDocument::isValidCpf($validated['legal_representative_document'])) {
                throw ValidationException::withMessages([
                    'legal_representative_document' => 'Informe um CPF válido para o responsável legal.',
                ]);
            }

            $validated['legal_representative_document'] = BrazilianDocument::formatCpf(
                $validated['legal_representative_document']
            );
        }

        if (! empty($validated['state'])) {
            $validated['state'] = strtoupper($validated['state']);
        }

        if ($personType === 'individual') {
            $validated['trade_name'] = null;
            $validated['state_registration'] = null;
            $validated['legal_representative_name'] = null;
            $validated['legal_representative_document'] = null;
        } else {
            $validated['birth_date'] = null;
        }

        return $validated;
    }

    /**
     * Ensure the selected unit respects the network, region and unit scopes.
     *
     * @param  array<string, mixed>  $validated
     */
    private function validateUnitScope(array $validated): void
    {
        $tenant = TenantContext::current();
        $networkId = $validated['network_id'] ?? null;
        $unitId = $validated['unit_id'] ?? null;
        $unit = $unitId ? Unit::find($unitId) : null;

        if ($unitId && ! $unit) {
            throw ValidationException::withMessages([
                'unit_id' => 'A unidade selecionada não foi encontrada.',
            ]);
        }

        if ($unit && (int) $unit->network_id !== (int) $networkId) {
            throw ValidationException::withMessages([
                'unit_id' => 'A unidade selecionada não pertence à rede informada.',
            ]);
        }

        if (! $tenant->isPlatform()) {
            if ($tenantUnitId = $tenant->unitId()) {
                if ($unitId && (int) $unitId !== $tenantUnitId) {
                    throw ValidationException::withMessages([
                        'unit_id' => 'Você só pode cadastrar pessoas na sua unidade.',
                    ]);
                }
            } elseif ($tenantRegionId = $tenant->regionId()) {
                if (! $unitId) {
                    throw ValidationException::withMessages([
                        'unit_id' => 'Selecione uma unidade da sua região.',
                    ]);
                }

                if ($unit && (int) $unit->region_id !== $tenantRegionId) {
                    throw ValidationException::withMessages([
                        'unit_id' => 'A unidade selecionada não pertence à sua região.',
                    ]);
                }
            }
        }

        $network = $networkId ? Network::find($networkId) : null;

        if (! $network) {
            return;
        }

        $policyScope = NetworkPolicy::get($network, 'pessoas', 'scope', 'network');
        $primaryUnitRequired = NetworkPolicy::get($network, 'pessoas', 'primary_unit_required', true);

        if ($policyScope === 'unit' && ! $unitId) {
            throw ValidationException::withMessages([
                'unit_id' => 'As configurações da rede exigem que a pessoa seja vinculada a uma unidade.',
            ]);
        }

        if ($validated['category'] === 'client' && $primaryUnitRequired && ! $unitId) {
            throw ValidationException::withMessages([
                'unit_id' => 'Selecione a unidade de referência do cliente.',
            ]);
        }
    }

    /**
     * Ensure the document is unique inside the scope defined by the network.
     *
     * @param  array<string, mixed>  $validated
     */
    private function validateDocumentScope(array $validated, ?Person $person): void
    {
        $networkId = $validated['network_id'] ?? null;
        $network = $networkId ? Network::find($networkId) : null;
        $raw = BrazilianDocument::onlyDigits($validated['document']);

        $uniqueScope = $network
            ? NetworkPolicy::get($network, 'pessoas', 'cpf_unique_scope', 'network')
            : 'network';

        $query = Person::query()->where('document_raw', $raw);

        if ($person) {
            $query->whereKeyNot($person->id);
        }

        if ($networkId) {
            $query->where('network_id', $networkId);
        } else {
            $query->whereNull('network_id');
        }

        if ($uniqueScope === 'unit') {
            $unitId = $validated['unit_id'] ?? null;

            if ($unitId) {
                $query->where('unit_id', $unitId);
            } else {
                $query->whereNull('unit_id');
            }
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'document' => 'Já existe uma pessoa cadastrada com este documento.',
            ]);
        }
    }
}
