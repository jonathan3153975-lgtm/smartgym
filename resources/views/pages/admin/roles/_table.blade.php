@if ($roles->count() > 0)
    {{-- Desktop table --}}
    <div class="hidden lg:block table-container">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Perfil</th>
                    <th>Nível</th>
                    <th class="text-center">Permissões</th>
                    <th class="text-center">Usuários</th>
                    <th>Tipo</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($roles as $role)
                    <tr>
                        <td>
                            <div class="font-medium">{{ $role->display_name }}</div>
                            <div class="text-xs text-text-secondary font-mono">{{ $role->name }}</div>
                        </td>
                        <td class="text-sm">{{ ucfirst($role->level) }}</td>
                        <td class="text-center font-medium">{{ $role->permissions_count }}</td>
                        <td class="text-center font-medium">{{ $role->user_vinculos_count }}</td>
                        <td>
                            @if ($role->is_system)
                                <span class="badge badge-info">Sistema</span>
                            @else
                                <span class="badge badge-secondary">Personalizado</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button"
                                        data-role-id="{{ $role->id }}"
                                        data-role-readonly="{{ $role->canBeManagedBy(auth()->user()) ? '0' : '1' }}"
                                        class="view-role p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                        title="Visualizar permissões">
                                    <i class="fas fa-eye"></i>
                                </button>
                                @if ($role->canBeManagedBy(auth()->user()))
                                    <button type="button"
                                            data-role-id="{{ $role->id }}"
                                            class="edit-role p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button"
                                            data-role-id="{{ $role->id }}"
                                            data-role-name="{{ $role->display_name }}"
                                            class="delete-role p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                            title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="lg:hidden space-y-3">
        @foreach ($roles as $role)
            <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold truncate">{{ $role->display_name }}</p>
                        <p class="text-xs text-text-secondary font-mono">{{ $role->name }}</p>
                    </div>
                    @if ($role->is_system)
                        <span class="badge badge-info whitespace-nowrap">Sistema</span>
                    @else
                        <span class="badge badge-secondary whitespace-nowrap">Personalizado</span>
                    @endif
                </div>

                <div class="mt-3 flex items-center gap-4 text-sm text-text-secondary">
                    <span><i class="fas fa-layer-group mr-1"></i>{{ ucfirst($role->level) }}</span>
                    <span><i class="fas fa-key mr-1"></i>{{ $role->permissions_count }}</span>
                    <span><i class="fas fa-users mr-1"></i>{{ $role->user_vinculos_count }}</span>
                </div>

                <div class="mt-3 flex items-center justify-end gap-1">
                    <button type="button"
                            data-role-id="{{ $role->id }}"
                            data-role-readonly="{{ $role->canBeManagedBy(auth()->user()) ? '0' : '1' }}"
                            class="view-role p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                            title="Visualizar permissões">
                        <i class="fas fa-eye"></i>
                    </button>
                    @if ($role->canBeManagedBy(auth()->user()))
                        <button type="button"
                                data-role-id="{{ $role->id }}"
                                class="edit-role p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button"
                                data-role-id="{{ $role->id }}"
                                data-role-name="{{ $role->display_name }}"
                                class="delete-role p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t border-custom pt-4">
        {{ $roles->links() }}
    </div>
@else
    <div class="text-center py-12">
        <i class="fas fa-user-shield text-4xl text-text-secondary mb-4"></i>
        <h4 class="text-lg font-medium mb-2">
            {{ request('search') ? 'Nenhum perfil encontrado' : 'Nenhum perfil cadastrado' }}
        </h4>
        <p class="text-text-secondary mb-4">
            {{ request('search') ? 'Tente alterar os termos da busca' : 'Cadastre o primeiro perfil personalizado' }}
        </p>
        @if (request('search'))
            <a href="{{ route('admin.roles.index') }}" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Limpar busca
            </a>
        @else
            <button type="button" onclick="openRoleModal()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Cadastrar Primeiro Perfil
            </button>
        @endif
    </div>
@endif
