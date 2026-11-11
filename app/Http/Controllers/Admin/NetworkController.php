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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NetworkController extends Controller
{
    /**
     * Display a listing of the networks.
     */
    public function index(Request $request): View|Response
    {
        $query = TenantContext::current()->scopeNetworks(Network::query())
            ->withCount(['regions', 'units'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('document', 'like', "%{$search}%")
                    ->orWhere('responsible_name', 'like', "%{$search}%")
                    ->orWhere('responsible_cpf', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $networks = $query->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return response()->view('pages.admin.networks._table', compact('networks'));
        }

        return view('pages.admin.networks.index', compact('networks'));
    }

    /**
     * Store a newly created network in storage.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $this->validateNetwork($request);

        $validated['slug'] = $this->resolveUniqueSlug($validated);

        if (! empty($validated['state'])) {
            $validated['state'] = Str::upper($validated['state']);
        }

        $validated['status'] = 'active';

        $network = Network::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Rede cadastrada com sucesso.',
                'data' => $network,
            ]);
        }

        return redirect()->route('admin.networks.index')
            ->with('success', 'Rede cadastrada com sucesso.');
    }

    /**
     * Display the specified network.
     */
    public function show(Network $network): View|JsonResponse
    {
        abort_unless(TenantContext::current()->canAccessNetwork($network), 404);

        if (request()->wantsJson()) {
            $data = $network->loadCount(['regions', 'units'])->toArray();
            $data['plan_name'] = $network->plan?->name;

            return response()->json($data);
        }

        $regions = $network->regions()
            ->withCount('units')
            ->with(['units' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();

        $unlinkedUnits = $network->units()
            ->whereNull('region_id')
            ->orderBy('name')
            ->get();

        $unitsCount = $network->units()->count();

        $structure = [
            'name' => $network->name,
            'regions' => $regions->map(fn ($region) => [
                'id' => $region->id,
                'name' => $region->name,
                'units' => $region->units
                    ->map(fn (Unit $unit) => $this->unitStructureData($unit, $region->name))
                    ->values()
                    ->all(),
            ])->values()->all(),
            'unlinkedUnits' => $unlinkedUnits
                ->map(fn (Unit $unit) => $this->unitStructureData($unit))
                ->values()
                ->all(),
        ];

        return view('pages.admin.networks.show', compact('network', 'regions', 'unlinkedUnits', 'unitsCount', 'structure'));
    }

    /**
     * Update the specified network in storage.
     */
    public function update(Request $request, Network $network): RedirectResponse|JsonResponse
    {
        $validated = $this->validateNetwork($request, $network);

        $validated['slug'] = $this->resolveUniqueSlug($validated, $network);

        if (! empty($validated['state'])) {
            $validated['state'] = Str::upper($validated['state']);
        }

        $network->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Rede atualizada com sucesso.',
                'data' => $network->fresh(),
            ]);
        }

        return redirect()->route('admin.networks.index')
            ->with('success', 'Rede atualizada com sucesso.');
    }

    /**
     * Toggle the network status between active and inactive.
     */
    public function toggleStatus(Network $network): JsonResponse|RedirectResponse
    {
        $network->update([
            'status' => $network->isActive() ? 'inactive' : 'active',
        ]);

        $message = $network->isActive()
            ? 'Rede ativada com sucesso.'
            : 'Rede inativada com sucesso.';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $network->status,
            ]);
        }

        return redirect()->route('admin.networks.index')->with('success', $message);
    }

    /**
     * Build the unit payload used by the org chart and the map.
     *
     * @return array<string, mixed>
     */
    private function unitStructureData(Unit $unit, ?string $regionName = null): array
    {
        $address = collect([
            trim(collect([$unit->street, $unit->number])->filter()->implode(', ')),
            $unit->complement,
            $unit->neighborhood,
            trim(collect([$unit->city, $unit->state])->filter()->implode(' - ')),
            $unit->zip_code ? 'CEP '.$unit->zip_code : null,
        ])->filter()->implode(', ');

        return [
            'id' => $unit->id,
            'name' => $unit->name,
            'identifier' => $unit->identifier,
            'status' => $unit->status,
            'city' => $unit->city,
            'state' => $unit->state,
            'address' => $address,
            'phone' => $unit->phone,
            'region' => $regionName,
        ];
    }

    /**
     * Validate the network request data.
     *
     * @return array<string, mixed>
     */
    private function validateNetwork(Request $request, ?Network $network = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('networks', 'slug')->ignore($network?->id),
            ],
            'document' => ['nullable', 'string', 'max:18'],
            'zip_code' => ['nullable', 'string', 'max:9'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'responsible_cpf' => ['nullable', 'string', 'max:14'],
            'responsible_email' => ['nullable', 'email', 'max:255'],
            'responsible_phone' => ['nullable', 'string', 'max:20'],
            'plan_id' => ['nullable', 'exists:saas_plans,id'],
        ]);
    }

    /**
     * Resolve a unique slug from the given data.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveUniqueSlug(array $data, ?Network $network = null): string
    {
        $baseSlug = Str::slug($data['slug'] ?? '') ?: Str::slug($data['name']);
        $baseSlug = $baseSlug ?: 'rede';
        $slug = $baseSlug;
        $suffix = 2;

        while (
            Network::withTrashed()
                ->where('slug', $slug)
                ->when($network, fn ($query) => $query->whereKeyNot($network->getKey()))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
