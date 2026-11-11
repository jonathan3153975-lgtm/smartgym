@if ($plans->count() > 0)
    {{-- Desktop table --}}
    <div class="hidden lg:block table-container">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Plano</th>
                    <th>Descrição</th>
                    <th class="text-center">Parâmetros</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plans as $plan)
                    <tr>
                        <td>
                            <div class="font-medium">{{ $plan->name }}</div>
                            <div class="text-xs text-text-secondary font-mono">{{ $plan->slug }}</div>
                        </td>
                        <td class="max-w-xs">
                            <span class="text-sm text-text-secondary line-clamp-2">
                                {{ $plan->description ?? 'Sem descrição' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="font-medium">{{ $plan->parameters_count }}</div>
                            @if ($plan->parameters_count > 0)
                                <div class="text-xs text-text-secondary">
                                    R$ {{ number_format((float) $plan->parameters_sum_value, 2, ',', '.') }}
                                </div>
                            @endif
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
                                <button type="button"
                                        data-plan-id="{{ $plan->id }}"
                                        data-plan-name="{{ $plan->name }}"
                                        class="open-plan-parameters inline-flex items-center px-2 py-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                        title="Parâmetros do plano">
                                    <i class="fas fa-sliders"></i>
                                    <span class="ml-1 text-sm">Parâmetros</span>
                                </button>
                                <button type="button"
                                        data-plan-id="{{ $plan->id }}"
                                        class="edit-plan p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                        title="Editar plano">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button"
                                        data-plan-id="{{ $plan->id }}"
                                        data-plan-name="{{ $plan->name }}"
                                        class="delete-plan p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                        title="Excluir plano">
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
        @foreach ($plans as $plan)
            <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold truncate">{{ $plan->name }}</p>
                        <p class="text-xs text-text-secondary font-mono truncate">{{ $plan->slug }}</p>
                    </div>
                    @if ($plan->isActive())
                        <span class="badge badge-success whitespace-nowrap">Ativo</span>
                    @else
                        <span class="badge badge-secondary whitespace-nowrap">Inativo</span>
                    @endif
                </div>

                <p class="mt-2 text-sm text-text-secondary">
                    {{ $plan->description ?? 'Sem descrição' }}
                </p>

                <div class="mt-3 flex items-center justify-between">
                    <div class="text-sm text-text-secondary">
                        <i class="fas fa-sliders mr-1"></i>
                        {{ $plan->parameters_count }} parâmetro(s)
                        @if ($plan->parameters_count > 0)
                            <span class="ml-2">
                                R$ {{ number_format((float) $plan->parameters_sum_value, 2, ',', '.') }}
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center gap-1">
                        <button type="button"
                                data-plan-id="{{ $plan->id }}"
                                data-plan-name="{{ $plan->name }}"
                                class="open-plan-parameters p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Parâmetros do plano">
                            <i class="fas fa-sliders"></i>
                        </button>
                        <button type="button"
                                data-plan-id="{{ $plan->id }}"
                                class="edit-plan p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Editar plano">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button"
                                data-plan-id="{{ $plan->id }}"
                                data-plan-name="{{ $plan->name }}"
                                class="delete-plan p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Excluir plano">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t border-custom pt-4">
        {{ $plans->links() }}
    </div>
@else
    <div class="text-center py-12">
        <i class="fas fa-layer-group text-4xl text-text-secondary mb-4"></i>
        <h4 class="text-lg font-medium mb-2">
            {{ request('search') ? 'Nenhum plano encontrado' : 'Nenhum plano cadastrado' }}
        </h4>
        <p class="text-text-secondary mb-4">
            {{ request('search')
                ? 'Tente alterar os termos da busca'
                : 'Comece cadastrando o primeiro plano SaaS da plataforma' }}
        </p>
        @if (request('search'))
            <a href="{{ route('admin.plans.index') }}" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Limpar busca
            </a>
        @else
            <button type="button" onclick="openPlanModal()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Cadastrar Primeiro Plano
            </button>
        @endif
    </div>
@endif
