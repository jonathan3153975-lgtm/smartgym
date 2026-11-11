<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Network;
use App\Models\Person;
use App\Models\Region;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserVinculo;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request): View|Response
    {
        $tenant = TenantContext::current();

        $query = $tenant->scopeUsers(User::query())
            ->with(['person', 'vinculos' => fn ($vinculoQuery) => $vinculoQuery
                ->with(['role', 'network', 'region', 'unit'])
                ->orderBy('id')])
            ->withCount('vinculos')
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('person', fn ($personQuery) => $personQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('document', 'like', "%{$search}%")
                        ->orWhere('document_raw', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('network_id')) {
            $query->whereHas('vinculos', fn ($vinculoQuery) => $vinculoQuery
                ->where('network_id', $request->integer('network_id')));
        }

        $users = $query->paginate(15)->withQueryString();
        $networks = $tenant->networksQuery()->get(['id', 'name']);

        if ($request->ajax()) {
            return response()->view('pages.admin.users._table', compact('users'));
        }

        return view('pages.admin.users.index', compact('users', 'networks'));
    }

    /**
     * Display the specified user and all their links.
     */
    public function show(User $user): View
    {
        $tenant = TenantContext::current();

        abort_unless($tenant->canAccessUser($user), 404);

        $user->load(['person', 'vinculos' => function ($vinculoQuery) use ($tenant) {
            $tenant->scopeUserVinculos($vinculoQuery)
                ->with(['role', 'network', 'region', 'unit'])
                ->orderByDesc('id');
        }]);

        return view('pages.admin.users.show', compact('user'));
    }

    /**
     * Return the user and primary link as JSON (used by the edit modal).
     */
    public function editData(User $user): JsonResponse
    {
        $tenant = TenantContext::current();

        abort_unless($tenant->canAccessUser($user), 404);

        $vinculo = $this->primaryVinculo($user, $tenant);

        return response()->json([
            'id' => $user->id,
            'person_id' => $user->person_id,
            'name' => $user->name,
            'email' => $user->email,
            'network_id' => $vinculo?->network_id,
            'region_id' => $vinculo?->region_id,
            'unit_id' => $vinculo?->unit_id,
            'role_id' => $vinculo?->role_id,
            'vinculo_status' => $vinculo?->status ?? 'active',
        ]);
    }

    /**
     * Store a newly created user with their primary link.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::current();

        abort_unless($tenant->isPlatform() || $tenant->networkId(), 403);

        if (! $tenant->isPlatform()) {
            $request->merge(['network_id' => $tenant->networkId()]);
        }

        $validated = $this->validateUser($request, $tenant);
        $this->validateRoleScope($validated, $tenant);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'person_id' => $validated['person_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            $user->vinculos()->create($this->vinculoData($validated));

            return $user;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuário cadastrado com sucesso.',
                'data' => $user,
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário cadastrado com sucesso.');
    }

    /**
     * Update the specified user and their primary link.
     */
    public function update(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::current();

        abort_unless($tenant->canAccessUser($user), 404);

        if (! $tenant->isPlatform()) {
            $request->merge(['network_id' => $tenant->networkId()]);
        }

        $validated = $this->validateUser($request, $tenant, $user);
        $this->validateRoleScope($validated, $tenant);

        DB::transaction(function () use ($validated, $user, $tenant) {
            $data = [
                'person_id' => $validated['person_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];

            if (! empty($validated['password'])) {
                $data['password'] = $validated['password'];
            }

            $user->update($data);

            $vinculo = $this->primaryVinculo($user, $tenant);

            if ($vinculo) {
                $vinculo->update($this->vinculoData($validated));
            } else {
                $user->vinculos()->create($this->vinculoData($validated));
            }
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuário atualizado com sucesso.',
                'data' => $user->fresh(),
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário atualizado com sucesso.');
    }

    /**
     * Toggle the status of the user's primary link.
     */
    public function toggleStatus(User $user): JsonResponse|RedirectResponse
    {
        $tenant = TenantContext::current();

        abort_unless($tenant->canAccessUser($user), 404);

        $vinculo = $this->primaryVinculo($user, $tenant);

        if (! $vinculo) {
            $message = 'Este usuário não possui vínculo para ativar ou inativar.';

            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('admin.users.index')->with('error', $message);
        }

        $vinculo->update([
            'status' => $vinculo->status === 'active' ? 'inactive' : 'active',
        ]);

        $message = $vinculo->status === 'active'
            ? 'Usuário ativado com sucesso.'
            : 'Usuário inativado com sucesso.';

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $vinculo->status,
            ]);
        }

        return redirect()->route('admin.users.index')->with('success', $message);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::current();

        abort_unless($tenant->canAccessUser($user), 404);

        if ($user->is(auth()->user())) {
            $message = 'Você não pode excluir o seu próprio usuário.';

            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('admin.users.index')->with('error', $message);
        }

        if (! $tenant->canDeleteUser($user)) {
            $message = 'Este usuário possui vínculos fora do seu escopo e não pode ser excluído.';

            if (request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('admin.users.index')->with('error', $message);
        }

        $user->delete();

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Usuário removido com sucesso.',
            ]);
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuário removido com sucesso.');
    }

    /**
     * Validate the user request data.
     *
     * @return array<string, mixed>
     */
    private function validateUser(Request $request, TenantContext $tenant, ?User $user = null): array
    {
        $validated = $request->validate([
            'person_id' => ['required', 'exists:people,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => $user
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed'],
            'network_id' => ['nullable', 'exists:networks,id'],
            'region_id' => ['nullable', 'exists:regions,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'role_id' => ['required', 'exists:roles,id'],
            'vinculo_status' => ['required', Rule::in(['active', 'inactive'])],
        ]);

        $this->validateScope($validated);
        $this->validatePersonScope($validated, $tenant);

        return $validated;
    }

    /**
     * Ensure the selected person is available to the current context.
     *
     * @param  array<string, mixed>  $validated
     */
    private function validatePersonScope(array $validated, TenantContext $tenant): void
    {
        $person = Person::find($validated['person_id']);

        if ($person && ! $tenant->canAccessPerson($person)) {
            throw ValidationException::withMessages([
                'person_id' => 'A pessoa selecionada não está disponível para o seu escopo.',
            ]);
        }
    }

    /**
     * Ensure the selected region/unit belongs to the selected network.
     *
     * @param  array<string, mixed>  $validated
     */
    private function validateScope(array $validated): void
    {
        $networkId = $validated['network_id'] ?? null;
        $regionId = $validated['region_id'] ?? null;
        $unitId = $validated['unit_id'] ?? null;

        if (($regionId || $unitId) && ! $networkId) {
            throw ValidationException::withMessages([
                'network_id' => 'Selecione a rede para vincular uma região ou unidade.',
            ]);
        }

        if ($regionId && $networkId) {
            $regionBelongsToNetwork = Region::query()
                ->whereKey($regionId)
                ->where('network_id', $networkId)
                ->exists();

            if (! $regionBelongsToNetwork) {
                throw ValidationException::withMessages([
                    'region_id' => 'A região selecionada não pertence à rede informada.',
                ]);
            }
        }

        if ($unitId) {
            $unit = Unit::find($unitId);

            if (! $unit || $unit->network_id !== (int) $networkId) {
                throw ValidationException::withMessages([
                    'unit_id' => 'A unidade selecionada não pertence à rede informada.',
                ]);
            }

            if ($regionId && (int) $unit->region_id !== (int) $regionId) {
                throw ValidationException::withMessages([
                    'unit_id' => 'A unidade selecionada não pertence à região informada.',
                ]);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function vinculoData(array $validated): array
    {
        return [
            'network_id' => $validated['network_id'] ?? null,
            'region_id' => $validated['region_id'] ?? null,
            'unit_id' => $validated['unit_id'] ?? null,
            'role_id' => $validated['role_id'],
            'status' => $validated['vinculo_status'],
            'granted_by' => auth()->id(),
        ];
    }

    /**
     * Ensure the selected role can be delegated by the current user.
     *
     * @param  array<string, mixed>  $validated
     */
    private function validateRoleScope(array $validated, TenantContext $tenant): void
    {
        $role = Role::find($validated['role_id']);

        if (! $role) {
            return;
        }

        if ($tenant->isPlatform()) {
            return;
        }

        if ($role->level === 'platform'
            || ($role->network_id !== null && $role->network_id !== $tenant->networkId())) {
            throw ValidationException::withMessages([
                'role_id' => 'O perfil selecionado não está disponível para a sua rede.',
            ]);
        }
    }

    private function primaryVinculo(User $user, TenantContext $tenant): ?UserVinculo
    {
        $query = $tenant->scopeUserVinculos($user->vinculos()->getQuery());

        return (clone $query)
            ->where('status', 'active')
            ->orderBy('id')
            ->first()
            ?? $query->orderBy('id')->first();
    }
}
