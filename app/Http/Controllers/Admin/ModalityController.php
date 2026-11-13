<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modality;
use App\Models\ModalityCategory;
use App\Support\RecordScope;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ModalityController extends Controller
{
    public function index(Request $request): View|Response
    {
        $tenant = $this->tenant();
        $query = RecordScope::query(Modality::query(), $tenant)
            ->with(['category', 'region', 'unit', 'network', 'createdBy'])
            ->withCount('units')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($category) => $category->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('category_id')) {
            $query->where('modality_category_id', $request->integer('category_id'));
        }

        $modalities = $query->paginate(15)->withQueryString();
        $categories = ModalityCategory::where('network_id', $tenant->networkId())
            ->orderBy('name')
            ->get();

        if ($request->ajax()) {
            return response()->view('pages.admin.modalities._table', compact('modalities'));
        }

        return view('pages.admin.modalities.index', compact('modalities', 'categories'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        $validated = $this->validateModality($request, $tenant);
        $attributes = RecordScope::creationAttributes($tenant);
        $attributes['created_by'] = auth()->id();
        $attributes['updated_by'] = auth()->id();

        $modality = DB::transaction(function () use ($validated, $attributes) {
            $data = array_merge($attributes, $this->payload($validated));

            if (isset($validated['image'])) {
                $data['image'] = $validated['image']->store('modalities/'.$attributes['network_id'], 'public');
            }

            return Modality::create($data);
        });

        if ($modality->created_level === 'unit' && $modality->unit_id) {
            $modality->units()->syncWithoutDetaching([$modality->unit_id]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Modalidade cadastrada com sucesso.',
                'data' => $modality,
            ]);
        }

        return redirect()->route('admin.modalities.index')->with('success', 'Modalidade cadastrada com sucesso.');
    }

    public function update(Request $request, Modality $modality): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        $this->ensureVisible($modality, $tenant);
        $this->ensureManageable($modality, $tenant);

        $validated = $this->validateModality($request, $tenant);
        $data = $this->payload($validated);

        $currentImage = $modality->image;

        if ($request->boolean('remove_image') && $currentImage) {
            Storage::disk('public')->delete($currentImage);
            $data['image'] = null;
            $currentImage = null;
        }

        if (isset($validated['image'])) {
            $data['image'] = $validated['image']->store('modalities/'.$modality->network_id, 'public');

            if ($currentImage) {
                Storage::disk('public')->delete($currentImage);
            }
        }

        $data['updated_by'] = auth()->id();
        $modality->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Modalidade atualizada com sucesso.',
                'data' => $modality->fresh(),
            ]);
        }

        return redirect()->route('admin.modalities.index')->with('success', 'Modalidade atualizada com sucesso.');
    }

    public function destroy(Modality $modality): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        $this->ensureVisible($modality, $tenant);
        $this->ensureManageable($modality, $tenant);

        if ($modality->image) {
            Storage::disk('public')->delete($modality->image);
        }

        $modality->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Modalidade removida com sucesso.',
            ]);
        }

        return redirect()->route('admin.modalities.index')->with('success', 'Modalidade removida com sucesso.');
    }

    public function editData(Modality $modality): JsonResponse
    {
        $tenant = $this->tenant();
        $this->ensureVisible($modality, $tenant);
        $this->ensureManageable($modality, $tenant);

        return response()->json([
            'id' => $modality->id,
            'name' => $modality->name,
            'modality_category_id' => $modality->modality_category_id,
            'image_url' => $modality->image_url,
            'created_level_label' => $modality->created_level_label,
        ]);
    }

    public function unitsData(Modality $modality): JsonResponse
    {
        $tenant = $this->tenant();
        $this->ensureVisible($modality, $tenant);
        $this->ensureManageable($modality, $tenant);

        $units = $modality->availableUnitsQuery()->orderBy('name')->get(['id', 'name', 'region_id']);

        return response()->json([
            'units' => $units,
            'selected' => $modality->units()->pluck('units.id'),
        ]);
    }

    public function syncUnits(Request $request, Modality $modality): JsonResponse
    {
        $tenant = $this->tenant();
        $this->ensureVisible($modality, $tenant);
        $this->ensureManageable($modality, $tenant);

        $validated = $request->validate([
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer', 'exists:units,id'],
        ]);

        $allowed = $modality->availableUnitsQuery()->pluck('id')->map(fn ($id) => (int) $id)->all();
        $selected = collect($validated['unit_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();

        if ($selected->diff($allowed)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'unit_ids' => 'Selecione apenas unidades disponíveis para o seu escopo.',
            ]);
        }

        $modality->units()->sync($selected->all());

        return response()->json([
            'success' => true,
            'message' => 'Unidades atualizadas com sucesso.',
            'count' => $selected->count(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateModality(Request $request, TenantContext $tenant): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'modality_category_id' => [
                'nullable',
                Rule::exists('modality_categories', 'id')->where('network_id', $tenant->networkId()),
            ],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'modality_category_id' => $validated['modality_category_id'] ?? null,
        ];
    }

    private function tenant(): TenantContext
    {
        $tenant = TenantContext::current();

        abort_if($tenant->isPlatform(), 403, 'Selecione uma rede para gerenciar modalidades.');
        abort_unless($tenant->networkId(), 403, 'Selecione uma rede para gerenciar modalidades.');

        return $tenant;
    }

    private function ensureVisible(Modality $modality, TenantContext $tenant): void
    {
        abort_unless(
            RecordScope::query(Modality::query()->whereKey($modality->id), $tenant)->exists(),
            404
        );
    }

    private function ensureManageable(Modality $modality, TenantContext $tenant): void
    {
        abort_unless(
            RecordScope::canManage($modality, $tenant),
            403,
            'Somente usuários do mesmo nível que criou a modalidade podem editá-la ou excluí-la.'
        );
    }
}
