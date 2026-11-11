@if ($networks->count() > 0)
    {{-- Desktop table --}}
    <div class="hidden lg:block table-container">
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Rede</th>
                    <th>Responsável</th>
                    <th>Contato</th>
                    <th class="text-center">Regiões</th>
                    <th class="text-center">Unidades</th>
                    <th>Status</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($networks as $network)
                    <tr>
                        <td>
                            <div class="font-medium">{{ $network->name }}</div>
                            <div class="text-xs text-text-secondary">
                                {{ $network->document ?? 'CNPJ não informado' }}
                                @if ($network->city)
                                    &middot; {{ $network->city }}{{ $network->state ? '/'.$network->state : '' }}
                                @endif
                            </div>
                        </td>
                        <td>
                            <div>{{ $network->responsible_name ?? 'Não informado' }}</div>
                            @if ($network->responsible_cpf)
                                <div class="text-xs text-text-secondary font-mono">{{ $network->responsible_cpf }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="text-sm">{{ $network->responsible_email ?? '—' }}</div>
                            @if ($network->responsible_phone)
                                <div class="text-xs text-text-secondary">{{ $network->responsible_phone }}</div>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="font-medium">{{ $network->regions_count }}</span>
                        </td>
                        <td class="text-center">
                            <span class="font-medium">{{ $network->units_count }}</span>
                        </td>
                        <td>
                            @if ($network->isActive())
                                <span class="badge badge-success">Ativo</span>
                            @elseif ($network->status === 'inactive')
                                <span class="badge badge-secondary">Inativo</span>
                            @elseif ($network->status === 'suspended')
                                <span class="badge badge-warning">Suspenso</span>
                            @else
                                <span class="badge badge-info">Pendente</span>
                            @endif
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('admin.networks.show', $network) }}"
                                   class="p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                   title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button"
                                        onclick="openNetworkModal({{ $network->id }})"
                                        class="p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button"
                                        data-network-id="{{ $network->id }}"
                                        data-network-name="{{ $network->name }}"
                                        data-network-active="{{ $network->isActive() ? '1' : '0' }}"
                                        class="toggle-network-status p-2 rounded-lg hover:bg-surface-elevated transition-colors {{ $network->isActive() ? 'text-green' : 'text-text-secondary' }}"
                                        title="{{ $network->isActive() ? 'Inativar' : 'Ativar' }}">
                                    <i class="fas {{ $network->isActive() ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
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
        @foreach ($networks as $network)
            <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold truncate">{{ $network->name }}</p>
                        <p class="text-xs text-text-secondary truncate">
                            {{ $network->document ?? 'CNPJ não informado' }}
                        </p>
                    </div>
                    @if ($network->isActive())
                        <span class="badge badge-success whitespace-nowrap">Ativo</span>
                    @elseif ($network->status === 'inactive')
                        <span class="badge badge-secondary whitespace-nowrap">Inativo</span>
                    @elseif ($network->status === 'suspended')
                        <span class="badge badge-warning whitespace-nowrap">Suspenso</span>
                    @else
                        <span class="badge badge-info whitespace-nowrap">Pendente</span>
                    @endif
                </div>

                <div class="mt-3 space-y-1 text-sm">
                    <p class="text-text-secondary">
                        <i class="fas fa-user w-4 text-center mr-1"></i>
                        {{ $network->responsible_name ?? 'Responsável não informado' }}
                        @if ($network->responsible_cpf)
                            <span class="font-mono text-xs">({{ $network->responsible_cpf }})</span>
                        @endif
                    </p>
                    <p class="text-text-secondary">
                        <i class="fas fa-location-dot w-4 text-center mr-1"></i>
                        {{ $network->city ? $network->city.($network->state ? '/'.$network->state : '') : 'Endereço não informado' }}
                    </p>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <div class="flex items-center gap-4 text-sm text-text-secondary">
                        <span><i class="fas fa-map-marked-alt mr-1"></i>{{ $network->regions_count }}</span>
                        <span><i class="fas fa-building mr-1"></i>{{ $network->units_count }}</span>
                    </div>

                    <div class="flex items-center gap-1">
                        <a href="{{ route('admin.networks.show', $network) }}"
                           class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                           title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button type="button"
                                onclick="openNetworkModal({{ $network->id }})"
                                class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                title="Editar">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button"
                                data-network-id="{{ $network->id }}"
                                data-network-name="{{ $network->name }}"
                                data-network-active="{{ $network->isActive() ? '1' : '0' }}"
                                class="toggle-network-status p-2 rounded-lg hover:bg-surface transition-colors {{ $network->isActive() ? 'text-green' : 'text-text-secondary' }}"
                                title="{{ $network->isActive() ? 'Inativar' : 'Ativar' }}">
                            <i class="fas {{ $network->isActive() ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 border-t border-custom pt-4">
        {{ $networks->links() }}
    </div>
@else
    <div class="text-center py-12">
        <i class="fas fa-sitemap text-4xl text-text-secondary mb-4"></i>
        <h4 class="text-lg font-medium mb-2">
            {{ request('search') ? 'Nenhuma rede encontrada' : 'Nenhuma rede cadastrada' }}
        </h4>
        <p class="text-text-secondary mb-4">
            {{ request('search')
                ? 'Tente alterar os termos da busca'
                : 'Comece cadastrando a primeira rede da plataforma' }}
        </p>
        @if (request('search'))
            <a href="{{ route('admin.networks.index') }}" class="btn-outline">
                <i class="fas fa-times mr-2"></i>
                Limpar busca
            </a>
        @else
            <button type="button" onclick="openNetworkModal()" class="btn-primary">
                <i class="fas fa-plus mr-2"></i>
                Cadastrar Primeira Rede
            </button>
        @endif
    </div>
@endif
