@if ($units->count() > 0)
    {{-- Desktop table --}}
    <div class="hidden lg:block table-container">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Unidade</th>
                    <th>Rede / Região</th>
                    <th>Cidade</th>
                    <th>Contato</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($units as $unit)
                    <tr>
                        <td>
                            <div class="font-medium">{{ $unit->name }}</div>
                            <div class="text-xs text-text-secondary font-mono">{{ $unit->identifier }}</div>
                        </td>
                        <td>
                            <div class="text-sm">{{ $unit->network?->name ?? '—' }}</div>
                            <div class="text-xs text-text-secondary">{{ $unit->region?->name ?? 'Sem região' }}</div>
                        </td>
                        <td class="text-sm">
                            {{ $unit->city ? $unit->city.($unit->state ? '/'.$unit->state : '') : '—' }}
                        </td>
                        <td>
                            <div class="text-sm">{{ $unit->phone ?? '—' }}</div>
                            @if ($unit->email)
                                <div class="text-xs text-text-secondary">{{ $unit->email }}</div>
                            @endif
                        </td>
                        <td>
                            @if ($unit->isActive())
                                <span class="badge badge-success">Ativa</span>
                            @elseif ($unit->status === 'maintenance')
                                <span class="badge badge-warning">Manutenção</span>
                            @else
                                <span class="badge badge-secondary">Inativa</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.units.show', $unit) }}"
                                   class="p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                   title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button"
                                        data-unit-id="{{ $unit->id }}"
                                        class="edit-unit p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button"
                                        data-unit-id="{{ $unit->id }}"
                                        data-unit-name="{{ $unit->name }}"
                                        data-unit-active="{{ $unit->isActive() ? '1' : '0' }}"
                                        class="toggle-unit p-2 rounded-lg hover:bg-surface-elevated transition-colors {{ $unit->isActive() ? 'text-green' : 'text-text-secondary' }}"
                                        title="{{ $unit->isActive() ? 'Inativar' : 'Ativar' }}">
                                    <i class="fas {{ $unit->isActive() ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>
                                <button type="button"
                                        data-unit-id="{{ $unit->id }}"
                                        data-unit-name="{{ $unit->name }}"
                                        class="delete-unit p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
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
        @foreach ($units as $unit)
            <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold truncate">{{ $unit->name }}</p>
                        <p class="text-xs text-text-secondary font-mono">#{{ $unit->identifier }}</p>
                    </div>
                    @if ($unit->isActive())
                        <span class="badge badge-success whitespace-nowrap">Ativa</span>
                    @elseif ($unit->status === 'maintenance')
                        <span class="badge badge-warning whitespace-nowrap">Manutenção</span>
                    @else
                        <span class="badge badge-secondary whitespace-nowrap">Inativa</span>
                    @endif
                </div>

                <div class="mt-3 space-y-1 text-sm text-text-secondary">
                    <p>
                        <i class="fas fa-sitemap w-4 text-center mr-1"></i>
                        {{ $unit->network?->name ?? '—' }}
                        @if ($unit->region)
                            &middot; {{ $unit->region->name }}
                        @endif
                    </p>
                    <p>
                        <i class="fas fa-location-dot w-4 text-center mr-1"></i>
                        {{ $unit->city ? $unit->city.($unit->state ? '/'.$unit->state : '') : 'Endereço não informado' }}
                    </p>
                </div>

                <div class="mt-3 flex items-center justify-end gap-1">
                    <a href="{{ route('admin.units.show', $unit) }}"
                       class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                       title="Visualizar">
                        <i class="fas fa-eye"></i>
                    </a>
                    <button type="button"
                            data-unit-id="{{ $unit->id }}"
                            class="edit-unit p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button"
                            data-unit-id="{{ $unit->id }}"
                            data-unit-name="{{ $unit->name }}"
                            data-unit-active="{{ $unit->isActive() ? '1' : '0' }}"
                            class="toggle-unit p-2 rounded-lg hover:bg-surface transition-colors {{ $unit->isActive() ? 'text-green' : 'text-text-secondary' }}"
                            title="{{ $unit->isActive() ? 'Inativar' : 'Ativar' }}">
                        <i class="fas {{ $unit->isActive() ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                    </button>
                    <button type="button"
                            data-unit-id="{{ $unit->id }}"
                            data-unit-name="{{ $unit->name }}"
                            class="delete-unit p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                            title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t border-custom pt-4">
        {{ $units->links() }}
    </div>
@else
    <div class="text-center py-12">
        <i class="fas fa-building text-4xl text-text-secondary mb-4"></i>
        <h4 class="text-lg font-medium mb-2">
            {{ request('search') ? 'Nenhuma unidade encontrada' : 'Nenhuma unidade cadastrada' }}
        </h4>
        <p class="text-text-secondary mb-4">
            {{ request('search') ? 'Tente alterar os termos da busca' : 'Comece cadastrando a primeira unidade' }}
        </p>
        @if (request('search'))
            <a href="{{ route('admin.units.index') }}" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Limpar busca
            </a>
        @else
            <button type="button" onclick="openUnitModal()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Cadastrar Primeira Unidade
            </button>
        @endif
    </div>
@endif
