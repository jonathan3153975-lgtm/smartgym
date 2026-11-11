<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index(Request $request): View|Response
    {
        $tenant = TenantContext::current();

        $query = $tenant->scopeRoles(Role::query())
            ->withCount(['permissions', 'userVinculos'])
            ->orderBy('level')
            ->orderBy('display_name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('level')) {
            $query->where('level', $request->string('level')->toString());
        }

        $roles = $query->paginate(15)->withQueryString();

        if ($request->ajax()) {
            return response()->view('pages.admin.roles._table', compact('roles'));
        }

        $permissions = $tenant->permissionOptionsQuery()
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');

        return view('pages.admin.roles.index', compact('roles', 'permissions'));
    }

    /**
     * Return the role and its permission ids as JSON (used by the edit modal).
     */
    public function show(Role $role): JsonResponse
    {
        abort_unless(TenantContext::current()->canViewRole($role), 404);

        return response()->json([
            'id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name,
            'level' => $role->level,
            'description' => $role->description,
            'is_system' => $role->is_system,
            'permissions' => $role->permissions()->pluck('permissions.id'),
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::current();

        abort_unless($tenant->isPlatform() || $tenant->networkId(), 403);

        $validated = $this->validateRole($request);
        $validated['name'] = $this->resolveUniqueName($validated);
        $validated['is_system'] = false;
        $validated['network_id'] = $tenant->isPlatform() ? null : $tenant->networkId();

        $permissions = $validated['permissions'] ?? [];
        unset($validated['permissions']);

        $this->ensurePermissionsAllowed($permissions, $tenant);

        $role = Role::create($validated);
        $role->permissions()->sync($permissions);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Perfil cadastrado com sucesso.',
                'data' => $role,
            ]);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Perfil cadastrado com sucesso.');
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::current();

        if ($role->is_system) {
            return $this->errorResponse('Perfis do sistema não podem ser alterados.');
        }

        abort_unless($tenant->canManageRole($role), 403);

        $validated = $this->validateRole($request, $role);
        $validated['name'] = $this->resolveUniqueName($validated, $role);

        $permissions = $validated['permissions'] ?? [];
        unset($validated['permissions']);

        $this->ensurePermissionsAllowed($permissions, $tenant);

        $role->update($validated);
        $role->permissions()->sync($permissions);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso.',
                'data' => $role->fresh(),
            ]);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Perfil atualizado com sucesso.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::current();

        if ($role->is_system) {
            return $this->errorResponse('Perfis do sistema não podem ser excluídos.');
        }

        abort_unless($tenant->canManageRole($role), 403);

        if ($role->userVinculos()->exists()) {
            return $this->errorResponse('Este perfil possui usuários vinculados e não pode ser excluído.');
        }

        $role->permissions()->detach();
        $role->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Perfil removido com sucesso.',
            ]);
        }

        return redirect()->route('admin.roles.index')
            ->with('success', 'Perfil removido com sucesso.');
    }

    /**
     * Validate the role request data.
     *
     * @return array<string, mixed>
     */
    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'name' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles', 'name')
                    ->where('level', $request->input('level'))
                    ->ignore($role?->id),
            ],
            'level' => ['required', Rule::in(['platform', 'network', 'region', 'unit'])],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);
    }

    /**
     * Resolve a unique snake_case role name.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveUniqueName(array $data, ?Role $role = null): string
    {
        $baseName = Str::slug($data['name'] ?? '', '_') ?: Str::slug($data['display_name'], '_');
        $baseName = $baseName ?: 'perfil';
        $name = $baseName;
        $suffix = 2;

        while (
            Role::query()
                ->where('name', $name)
                ->where('level', $data['level'])
                ->when($role, fn ($query) => $query->whereKeyNot($role->getKey()))
                ->exists()
        ) {
            $name = "{$baseName}_{$suffix}";
            $suffix++;
        }

        return $name;
    }

    /**
     * Ensure the current user can delegate the selected permissions.
     *
     * @param  array<int, int|string>  $permissionIds
     */
    private function ensurePermissionsAllowed(array $permissionIds, TenantContext $tenant): void
    {
        if ($tenant->isPlatform()) {
            return;
        }

        $allowedIds = $tenant->permissionOptionsQuery()->pluck('id')->all();

        if (array_diff($permissionIds, $allowedIds)) {
            throw ValidationException::withMessages([
                'permissions' => 'Um ou mais perfis de permissão não podem ser delegados pela sua rede.',
            ]);
        }
    }

    private function errorResponse(string $message): JsonResponse
    {
        if (request()->wantsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return response()->json(['success' => false, 'message' => $message], 422);
    }
}
