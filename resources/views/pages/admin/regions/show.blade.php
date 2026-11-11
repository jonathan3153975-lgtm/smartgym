@extends('layouts.app')

@section('title', 'Região - '.$region->name)

@section('content')
<div class="container mx-auto max-w-5xl p-4 sm:p-6 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="min-w-0">
            <a href="{{ route('admin.regions.index') }}" class="text-sm text-text-secondary hover:text-cyan transition-colors">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para regiões
            </a>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-2xl font-bold truncate">{{ $region->name }}</h1>
                @if ($region->isActive())
                    <span class="badge badge-success">Ativa</span>
                @else
                    <span class="badge badge-secondary">Inativa</span>
                @endif
            </div>
            <p class="text-text-secondary mt-1">
                Rede: {{ $region->network?->name ?? '—' }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" onclick="openRegionModal({{ $region->id }})" class="btn-outline">
                <i class="fas fa-edit mr-2"></i>
                Editar região
            </button>
            <button type="button" onclick="openUnitModal()" class="btn-primary">
                <i class="fas fa-building mr-2"></i>
                Nova unidade
            </button>
        </div>
    </div>

    <div class="card">
        <h2 class="font-semibold mb-4 flex items-center gap-2">
            <i class="fas fa-circle-info text-cyan"></i>
            Dados da região
        </h2>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">
            <div>
                <dt class="text-text-secondary text-sm">Nome</dt>
                <dd class="font-medium mt-1">{{ $region->name }}</dd>
            </div>
            <div>
                <dt class="text-text-secondary text-sm">Rede</dt>
                <dd class="font-medium mt-1">{{ $region->network?->name ?? '—' }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-text-secondary text-sm">Descrição</dt>
                <dd class="font-medium mt-1">{{ $region->description ?? 'Não informada' }}</dd>
            </div>
            <div>
                <dt class="text-text-secondary text-sm">Criada em</dt>
                <dd class="font-medium mt-1">{{ $region->created_at->format('d/m/Y H:i') }}</dd>
            </div>
            <div>
                <dt class="text-text-secondary text-sm">Atualizada em</dt>
                <dd class="font-medium mt-1">{{ $region->updated_at->format('d/m/Y H:i') }}</dd>
            </div>
        </dl>
    </div>

    <div class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold flex items-center gap-2">
                <i class="fas fa-building text-purple"></i>
                Unidades da região
            </h2>
            <span class="badge badge-info">{{ $region->units->count() }} unidade(s)</span>
        </div>

        @if ($region->units->count() > 0)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach ($region->units as $unit)
                    <div class="rounded-xl border border-custom bg-surface-elevated p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-medium truncate">{{ $unit->name }}</p>
                                <p class="text-xs text-text-secondary font-mono">{{ $unit->identifier }}</p>
                            </div>
                            @if ($unit->isActive())
                                <span class="badge badge-success whitespace-nowrap">Ativa</span>
                            @elseif ($unit->status === 'maintenance')
                                <span class="badge badge-warning whitespace-nowrap">Manutenção</span>
                            @else
                                <span class="badge badge-secondary whitespace-nowrap">Inativa</span>
                            @endif
                        </div>

                        <p class="mt-2 text-sm text-text-secondary">
                            <i class="fas fa-location-dot mr-1"></i>
                            {{ $unit->city ? $unit->city.($unit->state ? '/'.$unit->state : '') : 'Endereço não informado' }}
                        </p>
                        @if ($unit->phone)
                            <p class="mt-1 text-sm text-text-secondary">
                                <i class="fas fa-phone mr-1"></i>{{ $unit->phone }}
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-10">
                <i class="fas fa-building text-3xl text-text-secondary mb-3"></i>
                <p class="font-medium">Nenhuma unidade vinculada</p>
                <p class="text-sm text-text-secondary mt-1">Cadastre a primeira unidade desta região.</p>
                <button type="button" onclick="openUnitModal()" class="btn-outline mt-4">
                    <i class="fas fa-plus mr-2"></i>
                    Cadastrar unidade
                </button>
            </div>
        @endif
    </div>
</div>

@include('pages.admin.regions._modal', ['network' => $region->network])
@include('pages.admin.units._modal', ['network' => $region->network, 'region' => $region])
@endsection
