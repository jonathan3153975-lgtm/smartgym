@if ($users->count() > 0)
    {{-- Desktop table --}}
    <div class="hidden lg:block table-container">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Perfil</th>
                    <th>Escopo</th>
                    <th class="text-center">Vínculos</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    @php($vinculo = $user->vinculos->firstWhere('status', 'active') ?? $user->vinculos->first())
                    <tr>
                        <td>
                            <div class="font-medium">{{ $user->name }}</div>
                            <div class="text-xs text-text-secondary">{{ $user->email }}</div>
                            @if ($user->person)
                                <div class="mt-0.5 text-xs text-cyan">
                                    <i class="fas fa-id-card mr-1"></i>{{ $user->person->name }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if ($vinculo?->role)
                                <div class="text-sm">{{ $vinculo->role->display_name }}</div>
                                <div class="text-xs text-text-secondary">{{ ucfirst($vinculo->role->level) }}</div>
                            @else
                                <span class="text-text-secondary text-sm">Sem vínculo</span>
                            @endif
                        </td>
                        <td class="text-sm">
                            @if ($vinculo)
                                {{ $vinculo->network?->name ?? '—' }}
                                @if ($vinculo->region)
                                    <span class="text-text-secondary"> / {{ $vinculo->region->name }}</span>
                                @endif
                                @if ($vinculo->unit)
                                    <span class="text-text-secondary"> / {{ $vinculo->unit->name }}</span>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-center font-medium">{{ $user->vinculos_count }}</td>
                        <td>
                            @if ($vinculo?->status === 'active')
                                <span class="badge badge-success">Ativo</span>
                            @elseif ($vinculo)
                                <span class="badge badge-secondary">Inativo</span>
                            @else
                                <span class="badge badge-info">Sem vínculo</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.users.show', $user) }}"
                                   class="p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                   title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button"
                                        data-user-id="{{ $user->id }}"
                                        class="edit-user p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button"
                                        data-user-id="{{ $user->id }}"
                                        data-user-name="{{ $user->name }}"
                                        data-user-active="{{ $vinculo?->status === 'active' ? '1' : '0' }}"
                                        class="toggle-user p-2 rounded-lg hover:bg-surface-elevated transition-colors {{ $vinculo?->status === 'active' ? 'text-green' : 'text-text-secondary' }}"
                                        title="{{ $vinculo?->status === 'active' ? 'Inativar' : 'Ativar' }}">
                                    <i class="fas {{ $vinculo?->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>
                                <button type="button"
                                        data-user-id="{{ $user->id }}"
                                        data-user-name="{{ $user->name }}"
                                        class="delete-user p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                        title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="lg:hidden space-y-3">
        @foreach ($users as $user)
            @php($vinculo = $user->vinculos->firstWhere('status', 'active') ?? $user->vinculos->first())
            <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold truncate">{{ $user->name }}</p>
                        <p class="text-xs text-text-secondary truncate">{{ $user->email }}</p>
                        @if ($user->person)
                            <p class="mt-0.5 text-xs text-cyan truncate">
                                <i class="fas fa-id-card mr-1"></i>{{ $user->person->name }}
                            </p>
                        @endif
                    </div>
                    @if ($vinculo?->status === 'active')
                        <span class="badge badge-success whitespace-nowrap">Ativo</span>
                    @else
                        <span class="badge badge-secondary whitespace-nowrap">Inativo</span>
                    @endif
                </div>

                <div class="mt-3 space-y-1 text-sm text-text-secondary">
                    <p>
                        <i class="fas fa-user-shield w-4 text-center mr-1"></i>
                        {{ $vinculo?->role?->display_name ?? 'Sem vínculo' }}
                    </p>
                    <p>
                        <i class="fas fa-sitemap w-4 text-center mr-1"></i>
                        {{ $vinculo?->network?->name ?? '—' }}
                        @if ($vinculo?->region)
                            &middot; {{ $vinculo->region->name }}
                        @endif
                        @if ($vinculo?->unit)
                            &middot; {{ $vinculo->unit->name }}
                        @endif
                    </p>
                </div>

                <div class="mt-3 flex items-center justify-end gap-1">
                    <a href="{{ route('admin.users.show', $user) }}"
                       class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                       title="Visualizar">
                        <i class="fas fa-eye"></i>
                    </a>
                    <button type="button"
                            data-user-id="{{ $user->id }}"
                            class="edit-user p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button"
                            data-user-id="{{ $user->id }}"
                            data-user-name="{{ $user->name }}"
                            data-user-active="{{ $vinculo?->status === 'active' ? '1' : '0' }}"
                            class="toggle-user p-2 rounded-lg hover:bg-surface transition-colors {{ $vinculo?->status === 'active' ? 'text-green' : 'text-text-secondary' }}"
                            title="{{ $vinculo?->status === 'active' ? 'Inativar' : 'Ativar' }}">
                        <i class="fas {{ $vinculo?->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                    </button>
                    <button type="button"
                            data-user-id="{{ $user->id }}"
                            data-user-name="{{ $user->name }}"
                            class="delete-user p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                            title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t border-custom pt-4">
        {{ $users->links() }}
    </div>
@else
    <div class="text-center py-12">
        <i class="fas fa-users text-4xl text-text-secondary mb-4"></i>
        <h4 class="text-lg font-medium mb-2">
            {{ request('search') ? 'Nenhum usuário encontrado' : 'Nenhum usuário cadastrado' }}
        </h4>
        <p class="text-text-secondary mb-4">
            {{ request('search') ? 'Tente alterar os termos da busca' : 'Comece cadastrando o primeiro usuário' }}
        </p>
        @if (request('search'))
            <a href="{{ route('admin.users.index') }}" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Limpar busca
            </a>
        @else
            <button type="button" onclick="openUserModal()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Cadastrar Primeiro Usuário
            </button>
        @endif
    </div>
@endif
