<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\Modality;
use App\Support\RecordScope;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MembershipPlanController extends Controller
{
    public function index(Request $request): View|Response
    {
        $tenant = $this->tenant();
        $query = RecordScope::query(MembershipPlan::query(), $tenant)
            ->with(['region', 'unit'])
            ->withCount(['units', 'modalities'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('period')) {
            $query->where('period', $request->string('period')->toString());
        }

        $plans = $query->paginate(15)->withQueryString();
        $units = $tenant->unitsQuery()->orderBy('name')->get(['id', 'name', 'region_id']);
        $modalities = RecordScope::query(Modality::query(), $tenant)
            ->with('category')
            ->orderBy('name')
            ->get(['id', 'name', 'modality_category_id', 'created_level']);

        if ($request->ajax()) {
            return response()->view('pages.admin.membership-plans._table', compact('plans'));
        }

        return view('pages.admin.membership-plans.index', compact('plans', 'units', 'modalities'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        $validated = $this->validatePlan($request, $tenant);
        $attributes = RecordScope::creationAttributes($tenant);
        $attributes['created_by'] = auth()->id();
        $attributes['updated_by'] = auth()->id();
        $attributes['duration_days'] = MembershipPlan::durationForPeriod($validated['period']);

        $plan = DB::transaction(function () use ($validated, $attributes) {
            $plan = MembershipPlan::create(array_merge($attributes, $this->payload($validated)));
            $this->syncRelations($plan, $validated);

            return $plan;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Plano cadastrado com sucesso.',
                'data' => $plan,
            ]);
        }

        return redirect()->route('admin.membership-plans.index')->with('success', 'Plano cadastrado com sucesso.');
    }

    public function update(Request $request, MembershipPlan $membershipPlan): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        $this->ensureVisible($membershipPlan, $tenant);
        $this->ensureManageable($membershipPlan, $tenant);

        $validated = $this->validatePlan($request, $tenant);
        $data = $this->payload($validated);
        $data['duration_days'] = MembershipPlan::durationForPeriod($validated['period']);
        $data['updated_by'] = auth()->id();

        DB::transaction(function () use ($membershipPlan, $data, $validated) {
            $membershipPlan->update($data);
            $this->syncRelations($membershipPlan, $validated);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Plano atualizado com sucesso.',
                'data' => $membershipPlan->fresh(),
            ]);
        }

        return redirect()->route('admin.membership-plans.index')->with('success', 'Plano atualizado com sucesso.');
    }

    public function destroy(MembershipPlan $membershipPlan): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        $this->ensureVisible($membershipPlan, $tenant);
        $this->ensureManageable($membershipPlan, $tenant);

        $membershipPlan->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Plano removido com sucesso.',
            ]);
        }

        return redirect()->route('admin.membership-plans.index')->with('success', 'Plano removido com sucesso.');
    }

    public function editData(MembershipPlan $membershipPlan): JsonResponse
    {
        $tenant = $this->tenant();
        $this->ensureVisible($membershipPlan, $tenant);
        $this->ensureManageable($membershipPlan, $tenant);

        return response()->json([
            'id' => $membershipPlan->id,
            'name' => $membershipPlan->name,
            'description' => $membershipPlan->description,
            'price' => $membershipPlan->price,
            'period' => $membershipPlan->period,
            'weekly_accesses' => $membershipPlan->weekly_accesses,
            'daily_checkins' => $membershipPlan->daily_checkins,
            'all_units' => $membershipPlan->all_units,
            'all_modalities' => $membershipPlan->all_modalities,
            'unit_ids' => $membershipPlan->units()->pluck('units.id'),
            'modality_ids' => $membershipPlan->modalities()->pluck('modalities.id'),
            'created_level_label' => $membershipPlan->created_level_label,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePlan(Request $request, TenantContext $tenant): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'period' => ['required', Rule::in(array_keys(MembershipPlan::PERIODS))],
            'weekly_accesses' => ['required', 'integer', 'min:1', 'max:7'],
            'daily_checkins' => ['required', 'integer', 'min:1', 'max:10'],
            'all_units' => ['nullable', 'boolean'],
            'all_modalities' => ['nullable', 'boolean'],
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer', 'exists:units,id'],
            'modality_ids' => ['nullable', 'array'],
            'modality_ids.*' => ['integer', 'exists:modalities,id'],
        ]);

        $allUnits = filter_var($validated['all_units'] ?? true, FILTER_VALIDATE_BOOL);
        $allModalities = filter_var($validated['all_modalities'] ?? true, FILTER_VALIDATE_BOOL);
        $validated['all_units'] = $allUnits;
        $validated['all_modalities'] = $allModalities;

        $accessibleUnits = $tenant->unitsQuery()->pluck('id')->map(fn ($id) => (int) $id);
        $selectedUnits = collect($validated['unit_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        if ($selectedUnits->diff($accessibleUnits)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'unit_ids' => 'Selecione apenas unidades que você pode acessar.',
            ]);
        }

        if (! $allUnits && $selectedUnits->isEmpty()) {
            throw ValidationException::withMessages([
                'unit_ids' => 'Selecione ao menos uma unidade para o plano.',
            ]);
        }

        $visibleModalities = RecordScope::query(Modality::query(), $tenant)->pluck('id')->map(fn ($id) => (int) $id);
        $selectedModalities = collect($validated['modality_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        if ($selectedModalities->diff($visibleModalities)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'modality_ids' => 'Selecione apenas modalidades disponíveis para o seu escopo.',
            ]);
        }

        if (! $allModalities && $selectedModalities->isEmpty()) {
            throw ValidationException::withMessages([
                'modality_ids' => 'Selecione ao menos uma modalidade para o plano.',
            ]);
        }

        $validated['unit_ids'] = $selectedUnits->all();
        $validated['modality_ids'] = $selectedModalities->all();

        return $validated;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'] ?? null,
            'period' => $validated['period'],
            'duration_days' => MembershipPlan::durationForPeriod($validated['period']),
            'weekly_accesses' => $validated['weekly_accesses'],
            'daily_checkins' => $validated['daily_checkins'],
            'all_units' => $validated['all_units'],
            'all_modalities' => $validated['all_modalities'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncRelations(MembershipPlan $plan, array $validated): void
    {
        $plan->units()->sync($validated['all_units'] ? [] : $validated['unit_ids']);
        $plan->modalities()->sync($validated['all_modalities'] ? [] : $validated['modality_ids']);
    }

    private function tenant(): TenantContext
    {
        $tenant = TenantContext::current();

        abort_unless($tenant->networkId(), 403, 'Selecione uma rede para gerenciar planos.');
        abort_if($tenant->isPlatform(), 403, 'Selecione uma rede para gerenciar planos.');

        return $tenant;
    }

    private function ensureVisible(MembershipPlan $plan, TenantContext $tenant): void
    {
        abort_unless(
            RecordScope::query(MembershipPlan::query()->whereKey($plan->id), $tenant)->exists(),
            404
        );
    }

    private function ensureManageable(MembershipPlan $plan, TenantContext $tenant): void
    {
        abort_unless(
            RecordScope::canManage($plan, $tenant),
            403,
            'Somente usuários do mesmo nível que criou o plano podem editá-lo ou excluí-lo.'
        );
    }
}
