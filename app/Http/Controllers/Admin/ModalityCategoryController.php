<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ModalityCategory;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ModalityCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $tenant = $this->tenant();

        $categories = ModalityCategory::where('network_id', $tenant->networkId())
            ->withCount('modalities')
            ->orderBy('name')
            ->get();

        return response()->json(['data' => $categories]);
    }

    public function store(Request $request): JsonResponse
    {
        $tenant = $this->tenant();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('modality_categories', 'name')->where('network_id', $tenant->networkId()),
            ],
        ]);

        $category = ModalityCategory::create([
            'network_id' => $tenant->networkId(),
            'name' => $validated['name'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Categoria cadastrada com sucesso.',
            'data' => $category,
        ]);
    }

    public function update(Request $request, ModalityCategory $modalityCategory): JsonResponse
    {
        $tenant = $this->tenant();

        abort_unless($modalityCategory->network_id === $tenant->networkId(), 404);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('modality_categories', 'name')
                    ->where('network_id', $tenant->networkId())
                    ->ignore($modalityCategory->id),
            ],
        ]);

        $modalityCategory->update(['name' => $validated['name']]);

        return response()->json([
            'success' => true,
            'message' => 'Categoria atualizada com sucesso.',
            'data' => $modalityCategory->fresh(),
        ]);
    }

    public function destroy(ModalityCategory $modalityCategory): JsonResponse
    {
        $tenant = $this->tenant();

        abort_unless($modalityCategory->network_id === $tenant->networkId(), 404);

        if ($modalityCategory->modalities()->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Esta categoria possui modalidades vinculadas e não pode ser excluída.',
            ]);
        }

        $modalityCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoria removida com sucesso.',
        ]);
    }

    private function tenant(): TenantContext
    {
        $tenant = TenantContext::current();

        abort_unless($tenant->networkId(), 403, 'Selecione uma rede para gerenciar categorias.');
        abort_if($tenant->isPlatform(), 403, 'Selecione uma rede para gerenciar categorias.');

        return $tenant;
    }
}
