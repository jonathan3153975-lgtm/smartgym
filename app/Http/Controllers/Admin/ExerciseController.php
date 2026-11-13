<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Exercise;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExerciseController extends Controller
{
    public function index(Request $request): View|Response
    {
        $tenant = $this->tenant();

        $query = Exercise::query()
            ->where('network_id', $tenant->networkId())
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('equipment', 'like', "%{$search}%");
            });
        }

        if ($request->filled('muscle_group')) {
            $query->where('muscle_group', $request->string('muscle_group')->toString());
        }

        if ($request->filled('equipment')) {
            $query->where('equipment', $request->string('equipment')->toString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $exercises = $query->paginate(20)->withQueryString();

        if ($request->ajax()) {
            return response()->view('pages.admin.exercises._table', compact('exercises'));
        }

        $filterPairs = Exercise::query()
            ->where('network_id', $tenant->networkId())
            ->get(['muscle_group', 'equipment'])
            ->groupBy('muscle_group')
            ->map(fn ($items) => $items->pluck('equipment')->filter()->unique()->values()->all())
            ->toArray();

        return view('pages.admin.exercises.index', compact('exercises', 'filterPairs'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        $validated = $this->validateExercise($request);

        $data = Arr::except($validated, ['image', 'remove_image']);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('exercises/'.$tenant->networkId(), 'public');
        }

        $exercise = Exercise::create(array_merge($data, [
            'network_id' => $tenant->networkId(),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Exercício cadastrado com sucesso.',
                'data' => $exercise,
            ]);
        }

        return redirect()->route('admin.exercises.index')->with('success', 'Exercício cadastrado com sucesso.');
    }

    public function editData(Exercise $exercise): JsonResponse
    {
        abort_unless($this->canAccess($exercise), 404);

        return response()->json([
            'id' => $exercise->id,
            'name' => $exercise->name,
            'muscle_group' => $exercise->muscle_group,
            'equipment' => $exercise->equipment,
            'instructions' => $exercise->instructions,
            'image_url' => $exercise->image_url,
            'status' => $exercise->status,
        ]);
    }

    public function update(Request $request, Exercise $exercise): RedirectResponse|JsonResponse
    {
        abort_unless($this->canAccess($exercise), 404);

        $validated = $this->validateExercise($request);
        $data = Arr::except($validated, ['image', 'remove_image']);
        $data['updated_by'] = auth()->id();

        $currentImage = $exercise->image;

        if ($request->boolean('remove_image') && $currentImage) {
            Storage::disk('public')->delete($currentImage);
            $data['image'] = null;
            $currentImage = null;
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('exercises/'.$exercise->network_id, 'public');

            if ($currentImage) {
                Storage::disk('public')->delete($currentImage);
            }
        }

        $exercise->update($data);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Exercício atualizado com sucesso.',
                'data' => $exercise->fresh(),
            ]);
        }

        return redirect()->route('admin.exercises.index')->with('success', 'Exercício atualizado com sucesso.');
    }

    public function toggleStatus(Exercise $exercise): JsonResponse|RedirectResponse
    {
        abort_unless($this->canAccess($exercise), 404);

        $exercise->update([
            'status' => $exercise->isActive() ? 'inactive' : 'active',
            'updated_by' => auth()->id(),
        ]);

        $message = $exercise->isActive()
            ? 'Exercício ativado com sucesso.'
            : 'Exercício inativado com sucesso.';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $exercise->status,
            ]);
        }

        return redirect()->route('admin.exercises.index')->with('success', $message);
    }

    public function destroy(Exercise $exercise): JsonResponse|RedirectResponse
    {
        abort_unless($this->canAccess($exercise), 404);

        $exercise->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Exercício removido com sucesso.',
            ]);
        }

        return redirect()->route('admin.exercises.index')->with('success', 'Exercício removido com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateExercise(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'muscle_group' => ['required', Rule::in(array_keys(Exercise::MUSCLE_GROUPS))],
            'equipment' => ['nullable', Rule::in(array_keys(Exercise::EQUIPMENT_OPTIONS))],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp,gif', 'max:4096', 'dimensions:ratio=1/1'],
            'remove_image' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'image.dimensions' => 'A imagem de referência deve ser quadrada (1:1).',
        ]);
    }

    private function canAccess(Exercise $exercise): bool
    {
        $tenant = $this->tenant();

        return (int) $exercise->network_id === (int) $tenant->networkId();
    }

    private function tenant(): TenantContext
    {
        $tenant = TenantContext::current();

        abort_if($tenant->isPlatform(), 403, 'Selecione uma rede para gerenciar exercícios.');
        abort_unless($tenant->networkId(), 403, 'Selecione uma rede para gerenciar exercícios.');

        return $tenant;
    }
}
