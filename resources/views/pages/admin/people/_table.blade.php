@if ($people->count() > 0)
    {{-- Desktop table --}}
    <div class="hidden lg:block table-container">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Pessoa</th>
                    <th>Tipo / Categoria</th>
                    <th>Unidade</th>
                    <th>Contato</th>
                    <th class="text-center">Usuários</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($people as $person)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg text-xs font-bold"
                                     style="background: {{ $person->isCompany() ? 'var(--color-info-soft)' : 'var(--color-surface-elevated)' }}; color: {{ $person->isCompany() ? 'var(--color-info)' : 'var(--color-primary)' }};">
                                    <i class="fas {{ $person->isCompany() ? 'fa-building' : 'fa-user' }}"></i>
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate font-medium">{{ $person->name }}</div>
                                    <div class="text-xs text-text-secondary">
                                        {{ $person->document_label }} {{ $person->document ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="text-sm">{{ $person->person_type_label }}</div>
                            <span class="badge {{ $person->isStaff() ? 'badge-info' : 'badge-secondary' }} mt-1">
                                {{ $person->category_label }}
                            </span>
                        </td>
                        <td class="text-sm">
                            {{ $person->unit?->name ?? '—' }}
                        </td>
                        <td class="text-sm">
                            <div>{{ $person->email ?? '—' }}</div>
                            @if ($person->phone)
                                <div class="text-xs text-text-secondary">{{ $person->phone }}</div>
                            @endif
                        </td>
                        <td class="text-center font-medium">{{ $person->users_count }}</td>
                        <td>
                            @if ($person->status === 'active')
                                <span class="badge badge-success">Ativa</span>
                            @else
                                <span class="badge badge-secondary">Inativa</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.people.show', $person) }}"
                                   class="p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                   title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('people.edit')
                                    <button type="button"
                                            data-person-id="{{ $person->id }}"
                                            class="edit-person p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button"
                                            data-person-id="{{ $person->id }}"
                                            data-person-name="{{ $person->name }}"
                                            data-person-active="{{ $person->status === 'active' ? '1' : '0' }}"
                                            class="toggle-person p-2 rounded-lg hover:bg-surface-elevated transition-colors {{ $person->status === 'active' ? 'text-green' : 'text-text-secondary' }}"
                                            title="{{ $person->status === 'active' ? 'Inativar' : 'Ativar' }}">
                                        <i class="fas {{ $person->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                    </button>
                                @endcan
                                @can('people.delete')
                                    <button type="button"
                                            data-person-id="{{ $person->id }}"
                                            data-person-name="{{ $person->name }}"
                                            class="delete-person p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                            title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile cards --}}
    <div class="lg:hidden space-y-3">
        @foreach ($people as $person)
            <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $person->name }}</p>
                        <p class="truncate text-xs text-text-secondary">
                            {{ $person->document_label }} {{ $person->document ?? '—' }}
                        </p>
                    </div>
                    @if ($person->status === 'active')
                        <span class="badge badge-success whitespace-nowrap">Ativa</span>
                    @else
                        <span class="badge badge-secondary whitespace-nowrap">Inativa</span>
                    @endif
                </div>

                <div class="mt-3 space-y-1 text-sm text-text-secondary">
                    <p>
                        <i class="fas {{ $person->isCompany() ? 'fa-building' : 'fa-user' }} w-4 text-center mr-1"></i>
                        {{ $person->person_type_label }} &middot; {{ $person->category_label }}
                    </p>
                    <p>
                        <i class="fas fa-building w-4 text-center mr-1"></i>
                        {{ $person->unit?->name ?? 'Sem unidade' }}
                    </p>
                    @if ($person->email)
                        <p class="truncate">
                            <i class="fas fa-envelope w-4 text-center mr-1"></i>
                            {{ $person->email }}
                        </p>
                    @endif
                </div>

                <div class="mt-3 flex items-center justify-end gap-1">
                    <a href="{{ route('admin.people.show', $person) }}"
                       class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                       title="Visualizar">
                        <i class="fas fa-eye"></i>
                    </a>
                    @can('people.edit')
                        <button type="button"
                                data-person-id="{{ $person->id }}"
                                class="edit-person p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button"
                                data-person-id="{{ $person->id }}"
                                data-person-name="{{ $person->name }}"
                                data-person-active="{{ $person->status === 'active' ? '1' : '0' }}"
                                class="toggle-person p-2 rounded-lg hover:bg-surface transition-colors {{ $person->status === 'active' ? 'text-green' : 'text-text-secondary' }}"
                                title="{{ $person->status === 'active' ? 'Inativar' : 'Ativar' }}">
                            <i class="fas {{ $person->status === 'active' ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                        </button>
                    @endcan
                    @can('people.delete')
                        <button type="button"
                                data-person-id="{{ $person->id }}"
                                data-person-name="{{ $person->name }}"
                                class="delete-person p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    @endcan
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t border-custom pt-4">
        {{ $people->links() }}
    </div>
@else
    <div class="py-12 text-center">
        <i class="fas fa-address-book text-4xl text-text-secondary mb-4"></i>
        <h4 class="mb-2 text-lg font-medium">
            {{ request('search') || request('person_type') || request('category') || request('unit_id') ? 'Nenhuma pessoa encontrada' : 'Nenhuma pessoa cadastrada' }}
        </h4>
        <p class="mb-4 text-text-secondary">
            {{ request('search') || request('person_type') || request('category') || request('unit_id') ? 'Tente alterar os filtros da busca' : 'Comece cadastrando a primeira pessoa da rede' }}
        </p>
        @if (request('search') || request('person_type') || request('category') || request('unit_id'))
            <a href="{{ route('admin.people.index') }}" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Limpar filtros
            </a>
        @else
            @can('people.create')
                <button type="button" onclick="openPersonModal()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>
                    Cadastrar primeira pessoa
                </button>
            @endcan
        @endif
    </div>
@endif
