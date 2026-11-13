@extends('layouts.app')

@section('title', 'Unidade - '.$unit->name)

@section('content')
<div class="container mx-auto max-w-5xl p-4 sm:p-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('admin.units.index') }}" class="text-sm text-text-secondary hover:text-cyan transition-colors">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para unidades
            </a>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-2xl font-bold truncate">{{ $unit->name }}</h1>
                @if ($unit->isActive())
                    <span class="badge badge-success">Ativa</span>
                @elseif ($unit->status === 'maintenance')
                    <span class="badge badge-warning">Manutenção</span>
                @else
                    <span class="badge badge-secondary">Inativa</span>
                @endif
            </div>
            <p class="text-text-secondary mt-1 font-mono">{{ $unit->identifier }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ $unit->network ? route('admin.networks.show', $unit->network) : route('admin.networks.index') }}"
               class="btn-outline">
                <i class="fas fa-sitemap mr-2"></i>
                Ver rede
            </a>
            <button type="button" onclick="openUnitModal({{ $unit->id }})" class="btn-primary">
                <i class="fas fa-edit mr-2"></i>
                Editar unidade
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 card">
            <h2 class="font-semibold mb-4 flex items-center gap-2">
                <i class="fas fa-circle-info text-cyan"></i>
                Dados da unidade
            </h2>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <dt class="text-text-secondary text-sm">Nome</dt>
                    <dd class="font-medium mt-1">{{ $unit->name }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">Código</dt>
                    <dd class="font-medium mt-1 font-mono">{{ $unit->identifier }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">Rede</dt>
                    <dd class="font-medium mt-1">{{ $unit->network?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">Região</dt>
                    <dd class="font-medium mt-1">{{ $unit->region?->name ?? 'Sem região' }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">Telefone</dt>
                    <dd class="font-medium mt-1">{{ $unit->phone ?? 'Não informado' }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary text-sm">E-mail</dt>
                    <dd class="font-medium mt-1">{{ $unit->email ?? 'Não informado' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-text-secondary text-sm">Endereço</dt>
                    <dd class="font-medium mt-1">
                        @php
                            $address = collect([
                                trim(collect([$unit->street, $unit->number])->filter()->implode(', ')),
                                $unit->complement,
                                $unit->neighborhood,
                                trim(collect([$unit->city, $unit->state])->filter()->implode(' - ')),
                                $unit->zip_code ? 'CEP '.$unit->zip_code : null,
                            ])->filter()->implode(', ');
                        @endphp
                        {{ $address !== '' ? $address : 'Não informado' }}
                    </dd>
                </div>
            </dl>
        </div>

        <div class="card">
            <h2 class="font-semibold mb-4 flex items-center gap-2">
                <i class="fas fa-clock text-lime"></i>
                Registro
            </h2>
            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between rounded-lg bg-bg-secondary p-3">
                    <span class="text-text-secondary">Criada em</span>
                    <span>{{ $unit->created_at->format('d/m/Y') }}</span>
                </div>
                <div class="flex items-center justify-between rounded-lg bg-bg-secondary p-3">
                    <span class="text-text-secondary">Atualizada em</span>
                    <span>{{ $unit->updated_at->format('d/m/Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-credit-card text-cyan"></i>
                    Pagamento online
                </h2>
                <p class="mt-1 text-xs text-text-secondary">
                    Mercado Pago usado no checkout desta unidade.
                </p>
            </div>

            @if ($mercadoPago['unit_configured'] ?? false)
                <span class="badge badge-success">Credenciais da unidade</span>
            @elseif ($mercadoPago['network_configured'] ?? false)
                <span class="badge badge-info">Usando credenciais da rede</span>
            @else
                <span class="badge badge-warning">Não configurado</span>
            @endif
        </div>

        <p class="mt-3 text-sm text-text-secondary">
            @if ($mercadoPago['source'] ?? null)
                Ambiente:
                <span class="font-medium text-text-primary">
                    {{ \App\Models\PaymentGatewaySetting::ENVIRONMENTS[$mercadoPago['environment']] ?? $mercadoPago['environment'] }}
                </span>
                &middot; origem:
                <span class="font-medium text-text-primary">
                    {{ $mercadoPago['source'] === 'unit' ? 'unidade' : 'rede' }}
                </span>
            @else
                Nenhuma credencial disponível. Configure na unidade ou nas configurações da rede.
            @endif
        </p>
    </div>
</div>

@include('pages.admin.units._modal', ['network' => $unit->network, 'region' => $unit->region])
@endsection
