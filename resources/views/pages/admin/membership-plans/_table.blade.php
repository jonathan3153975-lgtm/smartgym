@if ($plans->count() > 0)
    @php($tenant = \App\Support\TenantContext::current())

    <div class="hidden lg:block table-container">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Plano</th>
                    <th>Período</th>
                    <th>Acessos</th>
                    <th>Unidades</th>
                    <th>Modalidades</th>
                    <th>Valor</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plans as $plan)
                    @php($canManage = \App\Support\RecordScope::canManage($plan, $tenant))
                    <tr>
                        <td>
                            <div class="font-medium">{{ $plan->name }}</div>
                            <div class="text-xs text-text-secondary">
                                <span class="badge badge-info">{{ $plan->created_level_label }}</span>
                                {{ $plan->description ? '· '.$plan->description : '' }}
                            </div>
                        </td>
                        <td class="text-sm">
                            <div>{{ $plan->period_label }}</div>
                            <div class="text-xs text-text-secondary">{{ $plan->duration_days }} dias de acesso</div>
                        </td>
                        <td class="text-sm">
                            <div>{{ $plan->weekly_accesses }}x por semana</div>
                            <div class="text-xs text-text-secondary">{{ $plan->daily_checkins }} check-in(s)/dia</div>
                        </td>
                        <td class="text-sm">
                            {{ $plan->all_units ? 'Todas' : $plan->units_count.' unidade(s)' }}
                        </td>
                        <td class="text-sm">
                            {{ $plan->all_modalities ? 'Todas' : $plan->modalities_count.' modalidade(s)' }}
                        </td>
                        <td class="text-sm font-medium">
                            {{ $plan->price !== null ? 'R$ '.number_format((float) $plan->price, 2, ',', '.') : '—' }}
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                @if ($canManage)
                                    <button type="button"
                                            data-plan-id="{{ $plan->id }}"
                                            class="edit-membership-plan p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                            title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button"
                                            data-plan-id="{{ $plan->id }}"
                                            data-plan-name="{{ $plan->name }}"
                                            class="delete-membership-plan p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
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
        @foreach ($plans as $plan)
            @php($canManage = \App\Support\RecordScope::canManage($plan, $tenant))
            <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ $plan->name }}</p>
                        <p class="text-xs text-text-secondary">{{ $plan->period_label }} · {{ $plan->duration_days }} dias</p>
                    </div>
                    <span class="badge badge-info whitespace-nowrap">{{ $plan->created_level_label }}</span>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-text-secondary">
                    <p><i class="fas fa-calendar mr-1"></i>{{ $plan->weekly_accesses }}x/semana · {{ $plan->daily_checkins }} check-in(s)/dia</p>
                    <p><i class="fas fa-building mr-1"></i>{{ $plan->all_units ? 'Todas as unidades' : $plan->units_count.' unidade(s)' }}</p>
                    <p class="col-span-2"><i class="fas fa-dumbbell mr-1"></i>{{ $plan->all_modalities ? 'Todas as modalidades' : $plan->modalities_count.' modalidade(s)' }}</p>
                    <p class="col-span-2 font-medium text-text-primary">
                        {{ $plan->price !== null ? 'R$ '.number_format((float) $plan->price, 2, ',', '.') : 'Sem valor informado' }}
                    </p>
                </div>

                <div class="mt-3 flex items-center justify-end gap-1">
                    @if ($canManage)
                        <button type="button"
                                data-plan-id="{{ $plan->id }}"
                                class="edit-membership-plan p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button"
                                data-plan-id="{{ $plan->id }}"
                                data-plan-name="{{ $plan->name }}"
                                class="delete-membership-plan p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
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
        {{ $plans->links() }}
    </div>
@else
    <div class="py-12 text-center">
        <i class="fas fa-layer-group text-4xl text-text-secondary mb-4"></i>
        <h4 class="mb-2 text-lg font-medium">
            {{ request('search') || request('period') ? 'Nenhum plano encontrado' : 'Nenhum plano cadastrado' }}
        </h4>
        <p class="mb-4 text-text-secondary">
            {{ request('search') || request('period') ? 'Tente alterar os filtros da busca' : 'Comece cadastrando o primeiro plano' }}
        </p>
        @if (request('search') || request('period'))
            <a href="{{ route('admin.membership-plans.index') }}" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Limpar filtros
            </a>
        @else
            @can('membership_plans.create')
                <button type="button" onclick="openMembershipPlanModal()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>
                    Cadastrar primeiro plano
                </button>
            @endcan
        @endif
    </div>
@endif
