@if ($regions->count() > 0)
    {{-- Desktop table --}}
    <div class="hidden lg:block table-container">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Região</th>
                    <th>Rede</th>
                    <th>Descrição</th>
                    <th class="text-center">Unidades</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($regions as $region)
                    <tr>
                        <td class="font-medium">{{ $region->name }}</td>
                        <td>{{ $region->network?->name ?? '—' }}</td>
                        <td class="max-w-xs">
                            <span class="text-sm text-text-secondary line-clamp-2">
                                {{ $region->description ?? 'Sem descrição' }}
                            </span>
                        </td>
                        <td class="text-center font-medium">{{ $region->units_count }}</td>
                        <td>
                            @if ($region->isActive())
                                <span class="badge badge-success">Ativa</span>
                            @else
                                <span class="badge badge-secondary">Inativa</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.regions.show', $region) }}"
                                   class="p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                   title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button"
                                        data-region-id="{{ $region->id }}"
                                        class="edit-region p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button"
                                        data-region-id="{{ $region->id }}"
                                        data-region-name="{{ $region->name }}"
                                        data-region-active="{{ $region->isActive() ? '1' : '0' }}"
                                        class="toggle-region p-2 rounded-lg hover:bg-surface-elevated transition-colors {{ $region->isActive() ? 'text-green' : 'text-text-secondary' }}"
                                        title="{{ $region->isActive() ? 'Inativar' : 'Ativar' }}">
                                    <i class="fas {{ $region->isActive() ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>
                                <button type="button"
                                        data-region-id="{{ $region->id }}"
                                        data-region-name="{{ $region->name }}"
                                        class="delete-region p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
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
        @foreach ($regions as $region)
            <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold truncate">{{ $region->name }}</p>
                        <p class="text-xs text-text-secondary truncate">{{ $region->network?->name ?? '—' }}</p>
                    </div>
                    @if ($region->isActive())
                        <span class="badge badge-success whitespace-nowrap">Ativa</span>
                    @else
                        <span class="badge badge-secondary whitespace-nowrap">Inativa</span>
                    @endif
                </div>

                <p class="mt-2 text-sm text-text-secondary">
                    {{ $region->description ?? 'Sem descrição' }}
                </p>

                <div class="mt-3 flex items-center justify-between">
                    <span class="text-sm text-text-secondary">
                        <i class="fas fa-building mr-1"></i>{{ $region->units_count }} unidade(s)
                    </span>

                    <div class="flex items-center gap-1">
                        <a href="{{ route('admin.regions.show', $region) }}"
                           class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                           title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button type="button"
                                data-region-id="{{ $region->id }}"
                                class="edit-region p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button"
                                data-region-id="{{ $region->id }}"
                                data-region-name="{{ $region->name }}"
                                data-region-active="{{ $region->isActive() ? '1' : '0' }}"
                                class="toggle-region p-2 rounded-lg hover:bg-surface transition-colors {{ $region->isActive() ? 'text-green' : 'text-text-secondary' }}"
                                title="{{ $region->isActive() ? 'Inativar' : 'Ativar' }}">
                            <i class="fas {{ $region->isActive() ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                        </button>
                        <button type="button"
                                data-region-id="{{ $region->id }}"
                                data-region-name="{{ $region->name }}"
                                class="delete-region p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t border-custom pt-4">
        {{ $regions->links() }}
    </div>
@else
    <div class="text-center py-12">
        <i class="fas fa-map-marked-alt text-4xl text-text-secondary mb-4"></i>
        <h4 class="text-lg font-medium mb-2">
            {{ request('search') ? 'Nenhuma região encontrada' : 'Nenhuma região cadastrada' }}
        </h4>
        <p class="text-text-secondary mb-4">
            {{ request('search') ? 'Tente alterar os termos da busca' : 'Comece cadastrando a primeira região' }}
        </p>
        @if (request('search'))
            <a href="{{ route('admin.regions.index') }}" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Limpar busca
            </a>
        @else
            <button type="button" onclick="openRegionModal()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Cadastrar Primeira Região
            </button>
        @endif
    </div>
@endif
