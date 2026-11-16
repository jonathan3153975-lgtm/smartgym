<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\Modality;
use App\Support\RecordScope;
use App\Support\Settings\DayPassPrice;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MembershipPlanController extends Controller
{
    public function __construct(private readonly DayPassPrice $dayPassPrice) {}

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
            ->with(['category', 'units:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'network_id', 'region_id', 'unit_id', 'modality_category_id', 'created_level']);

        $modalityUnits = $modalities->mapWithKeys(function (Modality $modality) use ($units): array {
            $available = $modality->units->isNotEmpty()
                ? $modality->units
                : $modality->availableUnitsQuery()->orderBy('name')->get(['id', 'name']);

            $availableIds = $available->pluck('id')->map(fn ($id) => (int) $id);

            return [
                (int) $modality->id => $units
                    ->filter(fn ($unit) => $availableIds->contains((int) $unit->id))
                    ->values()
                    ->map(fn ($unit) => ['id' => $unit->id, 'name' => $unit->name])
                    ->all(),
            ];
        });

        if ($request->ajax()) {
            return response()->view('pages.admin.membership-plans._table', compact('plans'));
        }

        $dayPassResolved = $this->dayPassPrice->resolveForTenant($tenant);

        return view('pages.admin.membership-plans.index', compact('plans', 'units', 'modalities', 'modalityUnits', 'dayPassResolved'));
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
            'type' => $membershipPlan->type,
            'weekly_accesses' => $membershipPlan->weekly_accesses,
            'daily_checkins' => $membershipPlan->daily_checkins,
            'checkin_advance_minutes' => $membershipPlan->checkin_advance_minutes,
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
            'type' => ['required', Rule::in(array_keys(MembershipPlan::TYPE_LABELS))],
            'weekly_accesses' => ['required', 'integer', 'min:1', 'max:7'],
            'daily_checkins' => ['required', 'integer', 'min:1', 'max:10'],
            'checkin_advance_minutes' => ['nullable', 'integer', 'between:0,120'],
            'all_units' => ['nullable', 'boolean'],
            'all_modalities' => ['nullable', 'boolean'],
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer', 'exists:units,id'],
            'modality_ids' => ['nullable', 'array'],
            'modality_ids.*' => ['integer', 'exists:modalities,id'],
        ]);

        $type = $validated['type'];
        $allUnits = filter_var($validated['all_units'] ?? true, FILTER_VALIDATE_BOOL);

        $accessibleUnits = $tenant->unitsQuery()->pluck('id')->map(fn ($id) => (int) $id);
        $selectedUnits = collect($validated['unit_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        if ($selectedUnits->diff($accessibleUnits)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'unit_ids' => 'Selecione apenas unidades que você pode acessar.',
            ]);
        }

        $visibleModalities = RecordScope::query(Modality::query(), $tenant)->pluck('id')->map(fn ($id) => (int) $id);
        $selectedModalities = collect($validated['modality_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        if ($selectedModalities->diff($visibleModalities)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'modality_ids' => 'Selecione apenas modalidades disponíveis para o seu escopo.',
            ]);
        }

        if ($type === MembershipPlan::TYPE_BASIC) {
            if (! $allUnits && $selectedUnits->isEmpty()) {
                throw ValidationException::withMessages([
                    'unit_ids' => 'Selecione ao menos uma unidade ou marque “Todas”.',
                ]);
            }

            if ($validated['weekly_accesses'] > 7) {
                throw ValidationException::withMessages([
                    'weekly_accesses' => 'Planos de academia permitem no máximo 7 acessos por semana.',
                ]);
            }

            $selectedModalities = collect();
        } else {
            if ($selectedModalities->isEmpty()) {
                throw ValidationException::withMessages([
                    'modality_ids' => 'Selecione ao menos uma modalidade para este tipo de plano.',
                ]);
            }

            $allUnits = false;

            $availableUnits = $this->unitsAvailableForModalities($selectedModalities, $tenant);

            if ($selectedUnits->isEmpty()) {
                throw ValidationException::withMessages([
                    'unit_ids' => 'Selecione ao menos uma unidade disponível para as modalidades escolhidas.',
                ]);
            }

            if ($selectedUnits->diff($availableUnits)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'unit_ids' => 'As unidades selecionadas não oferecem todas as modalidades escolhidas.',
                ]);
            }

            if ($type === MembershipPlan::TYPE_CLASSES) {
                $validated['daily_checkins'] = $selectedModalities->count();
            }
        }

        $validated['all_units'] = $allUnits;
        $validated['all_modalities'] = false;
        $validated['unit_ids'] = $selectedUnits->all();
        $validated['modality_ids'] = $selectedModalities->all();
        $validated['checkin_advance_minutes'] = (int) ($validated['checkin_advance_minutes']
            ?? MembershipPlan::DEFAULT_CHECKIN_ADVANCE);

        if ($validated['period'] === 'daily') {
            $dayPass = $this->dayPassPrice->resolveForTenant($tenant);

            if ($dayPass['price'] !== null) {
                $validated['price'] = $dayPass['price'];
            }
        }

        return $validated;
    }

    /**
     * Units that offer every selected modality, limited to the tenant scope.
     *
     * @param  Collection<int, int>  $modalityIds
     * @return Collection<int, int>
     */
    private function unitsAvailableForModalities(Collection $modalityIds, TenantContext $tenant): Collection
    {
        $accessibleUnits = $tenant->unitsQuery()->pluck('id')->map(fn ($id) => (int) $id);
        $availableUnits = null;

        foreach ($modalityIds as $modalityId) {
            $modality = Modality::query()->with('units:id')->find($modalityId);

            if (! $modality) {
                continue;
            }

            $units = $modality->units->isNotEmpty()
                ? $modality->units->pluck('id')
                : $modality->availableUnitsQuery()->pluck('id');

            $units = $units->map(fn ($id) => (int) $id)->intersect($accessibleUnits);

            $availableUnits = $availableUnits === null
                ? $units
                : $availableUnits->intersect($units);
        }

        return $availableUnits?->values() ?? collect();
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
            'type' => $validated['type'],
            'price' => $validated['price'] ?? null,
            'period' => $validated['period'],
            'duration_days' => MembershipPlan::durationForPeriod($validated['period']),
            'weekly_accesses' => $validated['weekly_accesses'],
            'daily_checkins' => $validated['daily_checkins'],
            'checkin_advance_minutes' => $validated['checkin_advance_minutes'],
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
