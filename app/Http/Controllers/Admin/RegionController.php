<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Network;
use App\Models\Region;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RegionController extends Controller
{
    /**
     * Display a listing of the regions.
     */
    public function index(Request $request): View|Response
    {
        $tenant = TenantContext::current();

        $query = $tenant->scopeRegions(Region::query())
            ->with('network')
            ->withCount('units')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('network', fn ($networkQuery) => $networkQuery->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('network_id')) {
            $query->where('network_id', $request->integer('network_id'));
        }

        $regions = $query->paginate(15)->withQueryString();
        $networks = $tenant->networksQuery()->get(['id', 'name']);

        if ($request->ajax()) {
            return response()->view('pages.admin.regions._table', compact('regions'));
        }

        return view('pages.admin.regions.index', compact('regions', 'networks'));
    }

    /**
     * Display the specified region and its units.
     */
    public function show(Region $region): View|JsonResponse
    {
        abort_unless(TenantContext::current()->canAccessRegion($region), 404);

        if (request()->wantsJson()) {
            return response()->json($region->load('network')->loadCount('units'));
        }

        $region->load(['network', 'units' => fn ($query) => $query->orderBy('name')]);

        return view('pages.admin.regions.show', compact('region'));
    }

    /**
     * Store a newly created region for a network.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::current();

        if (! $tenant->isPlatform()) {
            $request->merge(['network_id' => $tenant->networkId()]);
        }

        $validated = $request->validate([
            'network_id' => ['required', 'exists:networks,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('regions', 'name')->where('network_id', $request->input('network_id')),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['status'] = 'active';

        $region = Region::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Região cadastrada com sucesso.',
                'data' => $region,
            ]);
        }

        return redirect()->route('admin.regions.index')
            ->with('success', 'Região cadastrada com sucesso.');
    }

    /**
     * Update the specified region.
     */
    public function update(Request $request, Region $region): RedirectResponse|JsonResponse
    {
        abort_unless(TenantContext::current()->canAccessRegion($region), 404);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('regions', 'name')
                    ->where('network_id', $region->network_id)
                    ->ignore($region->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $region->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Região atualizada com sucesso.',
                'data' => $region->fresh(),
            ]);
        }

        return redirect()->route('admin.regions.index')
            ->with('success', 'Região atualizada com sucesso.');
    }

    /**
     * Toggle the region status between active and inactive.
     */
    public function toggleStatus(Region $region): JsonResponse|RedirectResponse
    {
        abort_unless(TenantContext::current()->canAccessRegion($region), 404);

        $region->update([
            'status' => $region->isActive() ? 'inactive' : 'active',
        ]);

        $message = $region->isActive()
            ? 'Região ativada com sucesso.'
            : 'Região inativada com sucesso.';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $region->status,
            ]);
        }

        return redirect()->route('admin.regions.index')->with('success', $message);
    }

    /**
     * Remove the specified region.
     */
    public function destroy(Region $region): RedirectResponse|JsonResponse
    {
        abort_unless(TenantContext::current()->canAccessRegion($region), 404);

        $region->units()->update(['region_id' => null]);
        $region->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Região removida com sucesso.',
            ]);
        }

        return redirect()->route('admin.regions.index')
            ->with('success', 'Região removida com sucesso.');
    }
}
