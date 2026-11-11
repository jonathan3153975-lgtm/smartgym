<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Network;
use App\Models\Unit;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    /**
     * Display a listing of the units.
     */
    public function index(Request $request): View|Response
    {
        $tenant = TenantContext::current();

        $query = $tenant->scopeUnits(Unit::query())
            ->with(['network', 'region'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('identifier', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('network', fn ($networkQuery) => $networkQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('region', fn ($regionQuery) => $regionQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('network_id')) {
            $query->where('network_id', $request->integer('network_id'));
        }

        $units = $query->paginate(15)->withQueryString();
        $networks = $tenant->networksQuery()->get(['id', 'name']);

        if ($request->ajax()) {
            return response()->view('pages.admin.units._table', compact('units'));
        }

        return view('pages.admin.units.index', compact('units', 'networks'));
    }

    /**
     * Display the specified unit.
     */
    public function show(Unit $unit): View|JsonResponse
    {
        abort_unless(TenantContext::current()->canAccessUnit($unit), 404);

        if (request()->wantsJson()) {
            return response()->json($unit->load(['network', 'region']));
        }

        $unit->load(['network', 'region']);

        $regions = $unit->network
            ? $unit->network->regions()->orderBy('name')->get()
            : collect();

        return view('pages.admin.units.show', compact('unit', 'regions'));
    }

    /**
     * Store a newly created unit for a network.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::current();

        if (! $tenant->isPlatform()) {
            $request->merge(['network_id' => $tenant->networkId()]);
        }

        $validated = $this->validateUnit($request);
        $validated['status'] = 'active';

        $unit = Unit::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Unidade cadastrada com sucesso.',
                'data' => $unit,
            ]);
        }

        return redirect()->route('admin.units.index')
            ->with('success', 'Unidade cadastrada com sucesso.');
    }

    /**
     * Update the specified unit.
     */
    public function update(Request $request, Unit $unit): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::current();

        abort_unless($tenant->canAccessUnit($unit), 404);

        if (! $tenant->isPlatform()) {
            $request->merge(['network_id' => $tenant->networkId()]);
        }

        $validated = $this->validateUnit($request, $unit);

        $unit->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Unidade atualizada com sucesso.',
                'data' => $unit->fresh(),
            ]);
        }

        return redirect()->route('admin.units.index')
            ->with('success', 'Unidade atualizada com sucesso.');
    }

    /**
     * Toggle the unit status between active and inactive.
     */
    public function toggleStatus(Unit $unit): JsonResponse|RedirectResponse
    {
        abort_unless(TenantContext::current()->canAccessUnit($unit), 404);

        $unit->update([
            'status' => $unit->isActive() ? 'inactive' : 'active',
        ]);

        $message = $unit->isActive()
            ? 'Unidade ativada com sucesso.'
            : 'Unidade inativada com sucesso.';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $unit->status,
            ]);
        }

        return redirect()->route('admin.units.index')->with('success', $message);
    }

    /**
     * Remove the specified unit.
     */
    public function destroy(Unit $unit): RedirectResponse|JsonResponse
    {
        abort_unless(TenantContext::current()->canAccessUnit($unit), 404);

        $unit->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Unidade removida com sucesso.',
            ]);
        }

        return redirect()->route('admin.units.index')
            ->with('success', 'Unidade removida com sucesso.');
    }

    /**
     * Validate the unit request data.
     *
     * @return array<string, mixed>
     */
    private function validateUnit(Request $request, ?Unit $unit = null): array
    {
        $validated = $request->validate([
            'network_id' => ['required', 'exists:networks,id'],
            'region_id' => [
                'nullable',
                Rule::exists('regions', 'id')->where('network_id', $request->input('network_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'identifier' => [
                'required',
                'string',
                'max:50',
                Rule::unique('units', 'identifier')->ignore($unit?->id),
            ],
            'zip_code' => ['nullable', 'string', 'max:9'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'maintenance'])],
        ]);

        if (! empty($validated['state'])) {
            $validated['state'] = strtoupper($validated['state']);
        }

        if ($unit && empty($validated['status'])) {
            unset($validated['status']);
        }

        return $validated;
    }
}
