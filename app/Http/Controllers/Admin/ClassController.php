<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Modality;
use App\Models\SchoolClass;
use App\Models\UserVinculo;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(Request $request): View|Response
    {
        $tenant = $this->tenant();

        $query = $tenant->scopeClasses(SchoolClass::query())
            ->with(['unit', 'teacher', 'modality'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('teacher', fn ($teacher) => $teacher->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('unit', fn ($unit) => $unit->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('modality', fn ($modality) => $modality->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('unit_id')) {
            $query->where('unit_id', $request->integer('unit_id'));
        }

        if ($request->filled('weekday')) {
            $query->where('weekday', $request->integer('weekday'));
        }

        $classes = $query->paginate(15)->withQueryString();
        $units = $tenant->unitsQuery()->orderBy('name')->get(['id', 'name']);
        $teachers = $this->teacherOptions($units->pluck('id')->all());
        $modalities = $this->modalityOptions($units->pluck('id')->all());

        if ($request->ajax()) {
            return response()->view('pages.admin.classes._table', compact('classes'));
        }

        return view('pages.admin.classes.index', compact('classes', 'units', 'teachers', 'modalities'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        $validated = $this->validateClass($request, $tenant);
        $validated['network_id'] = $tenant->networkId();
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $class = SchoolClass::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Turma cadastrada com sucesso.',
                'data' => $class,
            ]);
        }

        return redirect()->route('admin.classes.index')->with('success', 'Turma cadastrada com sucesso.');
    }

    public function update(Request $request, SchoolClass $class): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessClass($class), 404);

        $validated = $this->validateClass($request, $tenant);
        $validated['updated_by'] = auth()->id();
        unset($validated['network_id']);

        $class->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Turma atualizada com sucesso.',
                'data' => $class->fresh(),
            ]);
        }

        return redirect()->route('admin.classes.index')->with('success', 'Turma atualizada com sucesso.');
    }

    public function destroy(SchoolClass $class): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessClass($class), 404);

        $class->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Turma removida com sucesso.',
            ]);
        }

        return redirect()->route('admin.classes.index')->with('success', 'Turma removida com sucesso.');
    }

    public function editData(SchoolClass $class): JsonResponse
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessClass($class), 404);

        return response()->json([
            'id' => $class->id,
            'unit_id' => $class->unit_id,
            'modality_id' => $class->modality_id,
            'teacher_id' => $class->teacher_id,
            'name' => $class->name,
            'weekday' => $class->weekday,
            'start_time' => $class->start_time ? substr($class->start_time, 0, 5) : null,
            'end_time' => $class->end_time ? substr($class->end_time, 0, 5) : null,
            'vacancies' => $class->vacancies,
            'drop_in_price' => $class->drop_in_price,
            'status' => $class->status,
        ]);
    }

    /**
     * Modalities linked to the accessible units, used to populate the modality select.
     *
     * @param  array<int, int>  $unitIds
     * @return Collection<int, array{id: int, name: string, unit_ids: array<int, int>}>
     */
    private function modalityOptions(array $unitIds)
    {
        return Modality::query()
            ->where('network_id', TenantContext::current()->networkId())
            ->whereHas('units', fn ($query) => $query->whereIn('units.id', $unitIds))
            ->with(['units' => fn ($query) => $query->whereIn('units.id', $unitIds)])
            ->orderBy('name')
            ->get()
            ->map(fn (Modality $modality) => [
                'id' => (int) $modality->id,
                'name' => $modality->name,
                'unit_ids' => $modality->units->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ]);
    }

    /**
     * Users linked to the accessible units, used to populate the teacher select.
     *
     * @param  array<int, int>  $unitIds
     * @return Collection<int, array{id: int, name: string, unit_id: int}>
     */
    private function teacherOptions(array $unitIds)
    {
        return UserVinculo::query()
            ->whereIn('unit_id', $unitIds)
            ->where('status', 'active')
            ->whereNotNull('unit_id')
            ->with(['user', 'role'])
            ->get()
            ->filter(fn (UserVinculo $vinculo) => $vinculo->user && $vinculo->role && $vinculo->role->level !== 'platform')
            ->map(fn (UserVinculo $vinculo) => [
                'id' => (int) $vinculo->user_id,
                'name' => $vinculo->user->name,
                'unit_id' => (int) $vinculo->unit_id,
            ])
            ->unique(fn (array $teacher) => $teacher['id'].'-'.$teacher['unit_id'])
            ->sortBy('name')
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateClass(Request $request, TenantContext $tenant): array
    {
        $validated = $request->validate([
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'modality_id' => ['nullable', 'integer', 'exists:modalities,id'],
            'teacher_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'weekday' => ['required', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'vacancies' => ['required', 'integer', 'min:1', 'max:9999'],
            'drop_in_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $accessibleUnits = $tenant->unitsQuery()->pluck('id')->map(fn ($id) => (int) $id);

        if (! $accessibleUnits->contains((int) $validated['unit_id'])) {
            throw ValidationException::withMessages([
                'unit_id' => 'Selecione uma unidade que você pode acessar.',
            ]);
        }

        if (! empty($validated['modality_id'])) {
            $modalityLinked = Modality::query()
                ->whereKey($validated['modality_id'])
                ->where('network_id', $tenant->networkId())
                ->whereHas('units', fn ($query) => $query->whereKey($validated['unit_id']))
                ->exists();

            if (! $modalityLinked) {
                throw ValidationException::withMessages([
                    'modality_id' => 'A modalidade selecionada não está disponível na unidade da turma.',
                ]);
            }
        }

        if (! empty($validated['teacher_id'])) {
            $teacherLinked = UserVinculo::query()
                ->where('user_id', $validated['teacher_id'])
                ->where('unit_id', $validated['unit_id'])
                ->where('status', 'active')
                ->exists();

            if (! $teacherLinked) {
                throw ValidationException::withMessages([
                    'teacher_id' => 'O professor selecionado não está vinculado à unidade da turma.',
                ]);
            }
        }

        return $validated;
    }

    private function tenant(): TenantContext
    {
        $tenant = TenantContext::current();

        abort_if($tenant->isPlatform(), 403, 'Selecione uma rede para gerenciar turmas.');
        abort_unless($tenant->networkId(), 403, 'Selecione uma rede para gerenciar turmas.');

        return $tenant;
    }
}
