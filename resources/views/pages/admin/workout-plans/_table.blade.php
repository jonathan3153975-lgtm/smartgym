@if ($plans->count() > 0)
    <div class="hidden lg:block table-container">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Treino</th>
                    <th>Vínculo</th>
                    <th>Criador</th>
                    <th>Sessões</th>
                    <th>Visibilidade</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plans as $plan)
                    <tr>
                        <td>
                            <div class="font-medium">{{ $plan->name }}</div>
                            <div class="text-xs text-text-secondary">{{ $plan->objective ?: 'Sem objetivo informado' }}</div>
                        </td>
                        <td class="text-sm">
                            @if ($plan->student)
                                {{ $plan->student->name }}
                            @else
                                <span class="badge badge-info">Genérico</span>
                            @endif
                        </td>
                        <td class="text-sm">{{ $plan->createdBy?->name ?? 'Aluno' }}</td>
                        <td class="text-sm">{{ $plan->sessions_count }}</td>
                        <td>
                            <span class="badge {{ $plan->isPublic() ? 'badge-success' : 'badge-secondary' }}">
                                {{ $plan->isPublic() ? 'Público' : 'Privado' }}
                            </span>
                        </td>
                        <td>
                            @if ($plan->isActive())
                                <span class="badge badge-success">Ativo</span>
                            @else
                                <span class="badge badge-secondary">Inativo</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.workout-plans.builder', $plan) }}"
                                   class="p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                   title="Montar treino">
                                    <i class="fas fa-list-check"></i>
                                </a>
                                <button type="button"
                                        data-plan-id="{{ $plan->id }}"
                                        class="edit-workout-plan p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button"
                                        data-plan-id="{{ $plan->id }}"
                                        class="toggle-workout-plan p-2 rounded-lg hover:bg-surface-elevated transition-colors {{ $plan->isActive() ? 'text-green' : 'text-text-secondary' }}"
                                        title="{{ $plan->isActive() ? 'Inativar' : 'Ativar' }}">
                                    <i class="fas {{ $plan->isActive() ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                </button>
                                <button type="button"
                                        data-plan-id="{{ $plan->id }}"
                                        data-plan-name="{{ $plan->name }}"
                                        class="delete-workout-plan p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
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
        @foreach ($plans as $plan)
            <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $plan->name }}</p>
                        <p class="text-xs text-text-secondary">{{ $plan->student?->name ?? 'Treino genérico' }}</p>
                    </div>
                    @if ($plan->isActive())
                        <span class="badge badge-success whitespace-nowrap">Ativo</span>
                    @else
                        <span class="badge badge-secondary whitespace-nowrap">Inativo</span>
                    @endif
                </div>

                <div class="mt-3 space-y-1 text-sm text-text-secondary">
                    <p><i class="fas fa-user mr-1 w-4 text-center"></i>{{ $plan->createdBy?->name ?? 'Aluno' }}</p>
                    <p><i class="fas fa-list-check mr-1 w-4 text-center"></i>{{ $plan->sessions_count }} sessão(ões)</p>
                    <p>
                        <i class="fas fa-eye mr-1 w-4 text-center"></i>
                        {{ $plan->isPublic() ? 'Público' : 'Privado' }}
                    </p>
                </div>

                <div class="mt-3 flex items-center justify-end gap-1">
                    <a href="{{ route('admin.workout-plans.builder', $plan) }}"
                       class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                       title="Montar treino">
                        <i class="fas fa-list-check"></i>
                    </a>
                    <button type="button"
                            data-plan-id="{{ $plan->id }}"
                            class="edit-workout-plan p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                            title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button"
                            data-plan-id="{{ $plan->id }}"
                            class="toggle-workout-plan p-2 rounded-lg hover:bg-surface transition-colors {{ $plan->isActive() ? 'text-green' : 'text-text-secondary' }}"
                            title="{{ $plan->isActive() ? 'Inativar' : 'Ativar' }}">
                        <i class="fas {{ $plan->isActive() ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                    </button>
                    <button type="button"
                            data-plan-id="{{ $plan->id }}"
                            data-plan-name="{{ $plan->name }}"
                            class="delete-workout-plan p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                            title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t border-custom pt-4">
        {{ $plans->links() }}
    </div>
@else
    <div class="py-12 text-center">
        <i class="fas fa-clipboard-list mb-4 text-4xl text-text-secondary"></i>
        <h4 class="mb-2 text-lg font-medium">
            {{ request('search') || request('status') ? 'Nenhum plano encontrado' : 'Nenhum plano de treino cadastrado' }}
        </h4>
        <p class="mb-4 text-text-secondary">
            {{ request('search') || request('status') ? 'Tente alterar os filtros da busca' : 'Comece criando o primeiro plano de treino' }}
        </p>
        @if (request('search') || request('status'))
            <a href="{{ route('admin.workout-plans.index') }}" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Limpar filtros
            </a>
        @else
            <button type="button" onclick="openWorkoutPlanModal()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Cadastrar primeiro plano
            </button>
        @endif
    </div>
@endif
