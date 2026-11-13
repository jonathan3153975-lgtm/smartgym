@if ($exercises->count() > 0)
    <div class="hidden lg:block table-container">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Exercício</th>
                    <th>Grupo muscular</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($exercises as $exercise)
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                @if ($exercise->image_url)
                                    <img src="{{ $exercise->image_url }}" alt="{{ $exercise->name }}"
                                         class="h-10 w-10 rounded-lg border border-custom object-cover">
                                @endif
                                <div>
                                    <div class="font-medium">{{ $exercise->name }}</div>
                                    <div class="text-xs text-text-secondary">{{ $exercise->equipment_label }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge badge-info">{{ $exercise->muscle_group_label }}</span></td>
                        <td>
                            @if ($exercise->isActive())
                                <span class="badge badge-success">Ativo</span>
                            @else
                                <span class="badge badge-secondary">Inativo</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button"
                                        data-exercise-id="{{ $exercise->id }}"
                                        class="edit-exercise p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button"
                                        data-exercise-id="{{ $exercise->id }}"
                                        class="toggle-exercise p-2 rounded-lg hover:bg-surface-elevated transition-colors {{ $exercise->isActive() ? 'text-green' : 'text-text-secondary' }}"
                                        title="{{ $exercise->isActive() ? 'Inativar' : 'Ativar' }}">
                                    <i class="fas {{ $exercise->isActive() ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>
                                <button type="button"
                                        data-exercise-id="{{ $exercise->id }}"
                                        data-exercise-name="{{ $exercise->name }}"
                                        class="delete-exercise p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
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

    <div class="space-y-3 lg:hidden">
        @foreach ($exercises as $exercise)
            <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        @if ($exercise->image_url)
                            <img src="{{ $exercise->image_url }}" alt="{{ $exercise->name }}"
                                 class="h-12 w-12 flex-shrink-0 rounded-lg border border-custom object-cover">
                        @endif
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $exercise->name }}</p>
                            <p class="text-xs text-text-secondary">{{ $exercise->equipment_label }}</p>
                        </div>
                    </div>
                    @if ($exercise->isActive())
                        <span class="badge badge-success whitespace-nowrap">Ativo</span>
                    @else
                        <span class="badge badge-secondary whitespace-nowrap">Inativo</span>
                    @endif
                </div>

                <p class="mt-3">
                    <span class="badge badge-info">{{ $exercise->muscle_group_label }}</span>
                </p>

                <div class="mt-3 flex items-center justify-end gap-1">
                    <button type="button"
                            data-exercise-id="{{ $exercise->id }}"
                            class="edit-exercise p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button"
                            data-exercise-id="{{ $exercise->id }}"
                            class="toggle-exercise p-2 rounded-lg hover:bg-surface transition-colors {{ $exercise->isActive() ? 'text-green' : 'text-text-secondary' }}"
                            title="{{ $exercise->isActive() ? 'Inativar' : 'Ativar' }}">
                        <i class="fas {{ $exercise->isActive() ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                    </button>
                    <button type="button"
                            data-exercise-id="{{ $exercise->id }}"
                            data-exercise-name="{{ $exercise->name }}"
                            class="delete-exercise p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                            title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t border-custom pt-4">
        {{ $exercises->links() }}
    </div>
@else
    <div class="py-12 text-center">
        <i class="fas fa-person-running mb-4 text-4xl text-text-secondary"></i>
        <h4 class="mb-2 text-lg font-medium">
            {{ request('search') || request('muscle_group') || request('status') ? 'Nenhum exercício encontrado' : 'Nenhum exercício cadastrado' }}
        </h4>
        <p class="mb-4 text-text-secondary">
            {{ request('search') || request('muscle_group') || request('status') ? 'Tente alterar os filtros da busca' : 'Comece cadastrando o primeiro exercício' }}
        </p>
        @if (request('search') || request('muscle_group') || request('status'))
            <a href="{{ route('admin.exercises.index') }}" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Limpar filtros
            </a>
        @else
            <button type="button" onclick="openExerciseModal()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Cadastrar primeiro exercício
            </button>
        @endif
    </div>
@endif
