@if ($modalities->count() > 0)
    @php($tenant = \App\Support\TenantContext::current())

    <div class="hidden lg:block table-container">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Modalidade</th>
                    <th>Categoria</th>
                    <th>Nível</th>
                    <th class="text-center">Unidades</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($modalities as $modality)
                    @php($canManage = \App\Support\RecordScope::canManage($modality, $tenant))
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if ($modality->image_url)
                                    <img src="{{ $modality->image_url }}"
                                         alt="{{ $modality->name }}"
                                         class="h-10 w-10 flex-shrink-0 rounded-lg border border-custom object-cover">
                                @else
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-lg border border-custom bg-surface-elevated">
                                        <i class="fas fa-dumbbell text-text-secondary"></i>
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <div class="truncate font-medium">{{ $modality->name }}</div>
                                    <div class="text-xs text-text-secondary">
                                        Criada por {{ $modality->createdBy?->name ?? '—' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm">{{ $modality->category?->name ?? 'Sem categoria' }}</td>
                        <td class="text-sm">
                            <span class="badge badge-info">{{ $modality->created_level_label }}</span>
                            <div class="mt-1 text-xs text-text-secondary">
                                {{ $modality->region?->name ?? $modality->unit?->name ?? $modality->network?->name ?? '—' }}
                            </div>
                        </td>
                        <td class="text-center font-medium">{{ $modality->units_count }}</td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if ($canManage)
                                    <button type="button"
                                            data-modality-id="{{ $modality->id }}"
                                            class="edit-modality p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button"
                                            data-modality-id="{{ $modality->id }}"
                                            data-modality-name="{{ $modality->name }}"
                                            class="manage-modality-units p-2 rounded-lg hover:bg-surface-elevated transition-colors text-cyan"
                                            title="Vincular unidades">
                                        <i class="fas fa-building-circle-check"></i>
                                    </button>
                                    <button type="button"
                                            data-modality-id="{{ $modality->id }}"
                                            data-modality-name="{{ $modality->name }}"
                                            class="delete-modality p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                            title="Excluir">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @else
                                    <span class="p-2 text-text-secondary/50" title="Somente o nível que criou pode editar">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="lg:hidden space-y-3">
        @foreach ($modalities as $modality)
            @php($canManage = \App\Support\RecordScope::canManage($modality, $tenant))
            <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                <div class="flex items-start gap-3">
                    @if ($modality->image_url)
                        <img src="{{ $modality->image_url }}"
                             alt="{{ $modality->name }}"
                             class="h-12 w-12 flex-shrink-0 rounded-lg border border-custom object-cover">
                    @else
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg border border-custom bg-surface">
                            <i class="fas fa-dumbbell text-text-secondary"></i>
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold">{{ $modality->name }}</p>
                        <p class="text-xs text-text-secondary">{{ $modality->category?->name ?? 'Sem categoria' }}</p>
                        <p class="mt-1 text-xs text-text-secondary">
                            {{ $modality->created_level_label }}
                            &middot; {{ $modality->units_count }} unidade(s)
                        </p>
                    </div>
                </div>

                <div class="mt-3 flex items-center justify-end gap-1">
                    @if ($canManage)
                        <button type="button"
                                data-modality-id="{{ $modality->id }}"
                                class="edit-modality p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button"
                                data-modality-id="{{ $modality->id }}"
                                data-modality-name="{{ $modality->name }}"
                                class="manage-modality-units p-2 rounded-lg hover:bg-surface transition-colors text-cyan"
                                title="Vincular unidades">
                            <i class="fas fa-building-circle-check"></i>
                        </button>
                        <button type="button"
                                data-modality-id="{{ $modality->id }}"
                                data-modality-name="{{ $modality->name }}"
                                class="delete-modality p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Excluir">
                            <i class="fas fa-trash"></i>
                        </button>
                    @else
                        <span class="p-2 text-text-secondary/50" title="Somente o nível que criou pode editar">
                            <i class="fas fa-lock"></i>
                        </span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t border-custom pt-4">
        {{ $modalities->links() }}
    </div>
@else
    <div class="py-12 text-center">
        <i class="fas fa-dumbbell text-4xl text-text-secondary mb-4"></i>
        <h4 class="mb-2 text-lg font-medium">
            {{ request('search') || request('category_id') ? 'Nenhuma modalidade encontrada' : 'Nenhuma modalidade cadastrada' }}
        </h4>
        <p class="mb-4 text-text-secondary">
            {{ request('search') || request('category_id') ? 'Tente alterar os filtros da busca' : 'Comece cadastrando a primeira modalidade' }}
        </p>
        @if (request('search') || request('category_id'))
            <a href="{{ route('admin.modalities.index') }}" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Limpar filtros
            </a>
        @else
            @can('modalities.create')
                <button type="button" onclick="openModalityModal()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>
                    Cadastrar primeira modalidade
                </button>
            @endcan
        @endif
    </div>
@endif
