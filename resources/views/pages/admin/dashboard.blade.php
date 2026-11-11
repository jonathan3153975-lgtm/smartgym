@extends('layouts.app')

@section('title', 'Painel Administrativo')

@section('content')
<div class="container mx-auto max-w-6xl p-4 sm:p-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Painel administrativo</h1>
        <p class="text-text-secondary mt-1">Visão geral dos cadastros da plataforma</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @if ($managementNetwork)
            <a href="{{ route('admin.networks.show', $managementNetwork) }}" class="card group">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background: color-mix(in srgb, var(--color-primary) 15%, transparent);">
                        <i class="fas fa-diagram-project text-xl" style="color: var(--color-cyan-neon);"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-text-secondary text-sm">Minha rede</p>
                        <p class="font-bold truncate">{{ $managementNetwork->name }}</p>
                    </div>
                </div>
            </a>
        @endif

        @can('networks.manage')
            <a href="{{ route('admin.networks.index') }}" class="card group">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background: color-mix(in srgb, var(--color-primary) 15%, transparent);">
                        <i class="fas fa-sitemap text-xl" style="color: var(--color-cyan-neon);"></i>
                    </div>
                    <div>
                        <p class="text-text-secondary text-sm">Redes</p>
                        <p class="text-2xl font-bold">{{ $networksCount }}</p>
                    </div>
                </div>
            </a>
        @endcan

        @can('regions.manage')
            <a href="{{ route('admin.regions.index') }}" class="card group">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background: color-mix(in srgb, var(--color-secondary) 15%, transparent);">
                        <i class="fas fa-map-marked-alt text-xl" style="color: var(--color-purple-neon);"></i>
                    </div>
                    <div>
                        <p class="text-text-secondary text-sm">Regiões</p>
                        <p class="text-2xl font-bold">{{ $regionsCount }}</p>
                    </div>
                </div>
            </a>
        @endcan

        @can('units.manage')
            <a href="{{ route('admin.units.index') }}" class="card group">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background: color-mix(in srgb, var(--color-accent) 15%, transparent);">
                        <i class="fas fa-building text-xl" style="color: var(--color-lime-green);"></i>
                    </div>
                    <div>
                        <p class="text-text-secondary text-sm">Unidades</p>
                        <p class="text-2xl font-bold">{{ $unitsCount }}</p>
                    </div>
                </div>
            </a>
        @endcan

        @can('users.manage')
            <a href="{{ route('admin.users.index') }}" class="card group">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background: color-mix(in srgb, var(--color-warning) 15%, transparent);">
                        <i class="fas fa-users text-xl" style="color: var(--color-yellow);"></i>
                    </div>
                    <div>
                        <p class="text-text-secondary text-sm">Usuários</p>
                        <p class="text-2xl font-bold">{{ $usersCount }}</p>
                    </div>
                </div>
            </a>
        @endcan

        @can('roles.manage')
            <a href="{{ route('admin.roles.index') }}" class="card group">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background: color-mix(in srgb, var(--color-success) 15%, transparent);">
                        <i class="fas fa-user-shield text-xl" style="color: var(--color-green);"></i>
                    </div>
                    <div>
                        <p class="text-text-secondary text-sm">Perfis</p>
                        <p class="text-2xl font-bold">{{ $rolesCount }}</p>
                    </div>
                </div>
            </a>
        @endcan

        @can('plans.manage')
            <a href="{{ route('admin.plans.index') }}" class="card group">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" style="background: color-mix(in srgb, var(--color-danger) 15%, transparent);">
                        <i class="fas fa-layer-group text-xl" style="color: var(--color-red);"></i>
                    </div>
                    <div>
                        <p class="text-text-secondary text-sm">Planos</p>
                        <p class="text-2xl font-bold">{{ $plansCount }}</p>
                    </div>
                </div>
            </a>
        @endcan
    </div>
</div>
@endsection
