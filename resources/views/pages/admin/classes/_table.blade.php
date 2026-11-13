@if ($classes->count() > 0)
    <div class="hidden lg:block table-container">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Turma</th>
                    <th>Unidade</th>
                    <th>Dia e horário</th>
                    <th>Professor</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($classes as $class)
                    <tr>
                        <td>
                            <div class="font-medium">{{ $class->name }}</div>
                            <div class="text-xs text-text-secondary">{{ $class->modality?->name ?? 'Sem modalidade' }}</div>
                        </td>
                        <td class="text-sm">{{ $class->unit?->name ?? '—' }}</td>
                        <td class="text-sm">
                            <div>{{ $class->weekday_label }}</div>
                            <div class="text-xs text-text-secondary">{{ $class->time_label }}</div>
                            <div class="text-xs text-text-secondary">{{ $class->vacancies }} vagas</div>
                            @if ($class->drop_in_price)
                                <div class="text-xs text-text-secondary">
                                    Aula avulsa: R$ {{ number_format((float) $class->drop_in_price, 2, ',', '.') }}
                                </div>
                            @endif
                        </td>
                        <td class="text-sm">{{ $class->teacher?->name ?? 'Sem professor' }}</td>
                        <td>
                            @if ($class->status === 'active')
                                <span class="badge badge-success">Ativa</span>
                            @else
                                <span class="badge badge-secondary">Inativa</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button"
                                        data-class-id="{{ $class->id }}"
                                        class="edit-class p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button"
                                        data-class-id="{{ $class->id }}"
                                        data-class-name="{{ $class->name }}"
                                        class="delete-class p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
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

    <div class="lg:hidden space-y-3">
        @foreach ($classes as $class)
            <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $class->name }}</p>
                        <p class="text-xs text-text-secondary">{{ $class->modality?->name ?? 'Sem modalidade' }}</p>
                        <p class="text-xs text-text-secondary">{{ $class->unit?->name ?? '—' }}</p>
                    </div>
                    @if ($class->status === 'active')
                        <span class="badge badge-success whitespace-nowrap">Ativa</span>
                    @else
                        <span class="badge badge-secondary whitespace-nowrap">Inativa</span>
                    @endif
                </div>

                <div class="mt-3 space-y-1 text-sm text-text-secondary">
                    <p><i class="fas fa-calendar w-4 text-center mr-1"></i>{{ $class->weekday_label }} &middot; {{ $class->time_label }}</p>
                    <p><i class="fas fa-chair w-4 text-center mr-1"></i>{{ $class->vacancies }} vagas</p>
                    @if ($class->drop_in_price)
                        <p><i class="fas fa-ticket w-4 text-center mr-1"></i>Aula avulsa: R$ {{ number_format((float) $class->drop_in_price, 2, ',', '.') }}</p>
                    @endif
                    <p><i class="fas fa-chalkboard-user w-4 text-center mr-1"></i>{{ $class->teacher?->name ?? 'Sem professor' }}</p>
                </div>

                <div class="mt-3 flex items-center justify-end gap-1">
                    <button type="button"
                            data-class-id="{{ $class->id }}"
                            class="edit-class p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button"
                            data-class-id="{{ $class->id }}"
                            data-class-name="{{ $class->name }}"
                            class="delete-class p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                            title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t border-custom pt-4">
        {{ $classes->links() }}
    </div>
@else
    <div class="py-12 text-center">
        <i class="fas fa-chalkboard text-4xl text-text-secondary mb-4"></i>
        <h4 class="mb-2 text-lg font-medium">
            {{ request('search') || request('unit_id') || request('weekday') !== null ? 'Nenhuma turma encontrada' : 'Nenhuma turma cadastrada' }}
        </h4>
        <p class="mb-4 text-text-secondary">
            {{ request('search') || request('unit_id') || request('weekday') !== null ? 'Tente alterar os filtros da busca' : 'Comece cadastrando a primeira turma' }}
        </p>
        @if (request('search') || request('unit_id') || request('weekday') !== null)
            <a href="{{ route('admin.classes.index') }}" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Limpar filtros
            </a>
        @else
            <button type="button" onclick="openClassModal()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Cadastrar primeira turma
            </button>
        @endif
    </div>
@endif
