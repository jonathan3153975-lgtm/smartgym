@extends('layouts.app')

@section('title', 'Dashboard')

@php
    $money = fn ($value): string => 'R$ '.number_format((float) $value, 2, ',', '.');
    $percent = fn (?float $value): string => $value === null
        ? 'sem base anterior'
        : (($value > 0 ? '+' : '').number_format($value, 1, ',', '.').'%');
    $deltaTone = function (?float $value): string {
        if ($value === null) {
            return 'dash-delta-flat';
        }

        return $value > 0 ? 'dash-delta-up' : ($value < 0 ? 'dash-delta-down' : 'dash-delta-flat');
    };
    $deltaIcon = fn (?float $value): string => $value !== null && $value < 0 ? 'arrow-down-right' : 'arrow-up-right';
    $queryWith = function (array $overrides): string {
        $query = request()->query();

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
            } else {
                $query[$key] = $value;
            }
        }

        return route('dashboard', $query);
    };

    $activeChips = [];

    if ($filters['network_id'] && $scope['network_name']) {
        $activeChips[] = ['label' => 'Rede: '.$scope['network_name'], 'remove' => $queryWith(['network_id' => null, 'region_id' => null, 'unit_id' => null])];
    }

    if ($filters['region_id'] && $scope['region_name']) {
        $activeChips[] = ['label' => 'Região: '.$scope['region_name'], 'remove' => $queryWith(['region_id' => null, 'unit_id' => null])];
    }

    if ($filters['unit_id'] && $scope['unit_name']) {
        $activeChips[] = ['label' => 'Unidade: '.$scope['unit_name'], 'remove' => $queryWith(['unit_id' => null])];
    }

    if ($filters['status']) {
        $activeChips[] = [
            'label' => 'Situação: '.match ($filters['status']) {
                'paid' => 'recebido',
                'pending_future' => 'a receber',
                default => 'vencido',
            },
            'remove' => $queryWith(['status' => null]),
        ];
    }

    if ($filters['product']) {
        $activeChips[] = ['label' => 'Produto: '.\App\Support\Dashboard\DashboardMetrics::PRODUCTS[$filters['product']], 'remove' => $queryWith(['product' => null])];
    }

    if ($filters['method']) {
        $activeChips[] = ['label' => 'Pagamento: '.\App\Support\Dashboard\DashboardMetrics::METHODS[$filters['method']], 'remove' => $queryWith(['method' => null])];
    }

    if ($filters['segment']) {
        $activeChips[] = [
            'label' => 'Alunos: '.match ($filters['segment']) {
                'new' => 'novos',
                'trial' => 'experimentais',
                'converted' => 'convertidos',
                'without_plan' => 'sem plano',
                default => 'recorrentes',
            },
            'remove' => $queryWith(['segment' => null]),
        ];
    }

    $maxProduct = collect($revenue['products']['received'])->max('amount') ?: 1;
    $maxMethod = collect($revenue['methods']['received'])->max('amount') ?: 1;

    $pendingProducts = [];

    foreach (\App\Support\Dashboard\DashboardMetrics::PRODUCTS as $key => $label) {
        $future = collect($revenue['products']['pending_future'])->firstWhere('key', $key);
        $overdue = collect($revenue['products']['overdue'])->firstWhere('key', $key);

        if (($future['amount'] ?? 0) <= 0 && ($overdue['amount'] ?? 0) <= 0) {
            continue;
        }

        $pendingProducts[] = [
            'key' => $key,
            'label' => $label,
            'future' => (float) ($future['amount'] ?? 0),
            'overdue' => (float) ($overdue['amount'] ?? 0),
        ];
    }
@endphp

@push('styles')
<style>
    .dash-shell {
        --dash-radius: 1.15rem;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        margin: 0 auto;
        max-width: 1600px;
        padding: 1.25rem;
    }

    @media (min-width: 640px) {
        .dash-shell { padding: 1.75rem; }
    }

    .dash-card {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--dash-radius);
        overflow: hidden;
        position: relative;
    }

    .dash-pad { padding: 1.15rem 1.25rem; }

    .dash-kpi {
        display: block;
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    .dash-kpi:hover {
        border-color: color-mix(in srgb, var(--color-primary) 45%, var(--color-border));
        box-shadow: 0 18px 38px -26px color-mix(in srgb, var(--color-primary) 65%, transparent);
        transform: translateY(-2px);
    }

    .dash-kpi.is-active {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 1px color-mix(in srgb, var(--color-primary) 55%, transparent);
    }

    .dash-kpi.is-active::after {
        background: var(--color-primary);
        content: '';
        height: 3px;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
    }

    .dash-hero {
        background:
            radial-gradient(120% 160% at 0% 0%, color-mix(in srgb, var(--color-primary) 26%, transparent) 0%, transparent 55%),
            radial-gradient(90% 140% at 100% 0%, color-mix(in srgb, var(--color-info) 18%, transparent) 0%, transparent 55%),
            var(--color-surface);
    }

    .dash-icon-box {
        align-items: center;
        background: color-mix(in srgb, var(--tone, var(--color-primary)) 16%, transparent);
        border: 1px solid color-mix(in srgb, var(--tone, var(--color-primary)) 28%, transparent);
        border-radius: .9rem;
        color: var(--tone, var(--color-primary));
        display: grid;
        flex-shrink: 0;
        height: 2.65rem;
        place-items: center;
        width: 2.65rem;
    }

    .dash-icon-box svg { height: 1.15rem; width: 1.15rem; stroke-width: 1.9; }

    .dash-chip {
        align-items: center;
        border-radius: 999px;
        display: inline-flex;
        font-size: .7rem;
        font-weight: 700;
        gap: .3rem;
        letter-spacing: .01em;
        padding: .22rem .55rem;
    }

    .dash-chip svg { height: .8rem; width: .8rem; stroke-width: 2.3; }

    .dash-delta-up { background: var(--color-success-soft); color: var(--color-success); }
    .dash-delta-down { background: var(--color-danger-soft); color: var(--color-danger); }
    .dash-delta-flat { background: var(--color-surface-elevated); color: var(--color-text-secondary); }

    .dash-bar {
        background: var(--color-surface-elevated);
        border-radius: 999px;
        height: .38rem;
        overflow: hidden;
    }

    .dash-bar > span {
        border-radius: 999px;
        display: block;
        height: 100%;
        transition: width .3s ease;
    }

    .dash-row {
        align-items: center;
        border: 1px solid transparent;
        border-radius: .8rem;
        display: flex;
        gap: .7rem;
        padding: .55rem .6rem;
        transition: background .15s ease, border-color .15s ease;
    }

    .dash-row:hover {
        background: color-mix(in srgb, var(--color-primary) 7%, transparent);
        border-color: color-mix(in srgb, var(--color-primary) 22%, transparent);
    }

    .dash-row.is-active {
        background: color-mix(in srgb, var(--color-primary) 11%, transparent);
        border-color: color-mix(in srgb, var(--color-primary) 38%, transparent);
    }

    .dash-rank {
        align-items: center;
        background: var(--color-surface-elevated);
        border-radius: .55rem;
        color: var(--color-text-secondary);
        display: inline-flex;
        flex-shrink: 0;
        font-size: .66rem;
        font-weight: 700;
        height: 1.5rem;
        justify-content: center;
        width: 1.5rem;
    }

    .dash-scope-pill {
        background: color-mix(in srgb, var(--color-surface-elevated) 75%, transparent);
        border: 1px solid var(--color-border);
        border-radius: 999px;
        color: var(--color-text-secondary);
        font-size: .72rem;
        font-weight: 600;
        padding: .25rem .6rem;
    }

    .dash-chart { min-height: 300px; }
    .dash-chart-sm { min-height: 270px; }

    .dash-table { border-collapse: separate; border-spacing: 0; width: 100%; }
    .dash-table th {
        color: var(--color-text-secondary);
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .08em;
        padding: .6rem .75rem;
        text-align: left;
        text-transform: uppercase;
        white-space: nowrap;
    }
    .dash-table td {
        border-top: 1px solid var(--color-border);
        color: var(--color-text-primary);
        font-size: .82rem;
        padding: .7rem .75rem;
        vertical-align: middle;
    }
    .dash-table tbody tr:hover { background: color-mix(in srgb, var(--color-primary) 5%, transparent); }

    .dash-legend-dot {
        border-radius: 999px;
        display: inline-block;
        height: .55rem;
        width: .55rem;
    }

    .dash-muted { color: var(--color-text-secondary); }
    .dash-disabled { cursor: not-allowed; opacity: .55; }
    .dash-disabled:hover { opacity: .7; }

    @media (max-width: 640px) {
        .dash-pad { padding: 1rem; }
    }
</style>
@endpush

@section('content')
<div class="dash-shell">
    {{-- Hero / scope --}}
    <header class="dash-card dash-hero">
        <div class="dash-pad flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="min-w-0">
                <p class="flex items-center gap-2 text-[0.7rem] font-bold uppercase tracking-[0.18em]" style="color: var(--color-info);">
                    <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                    Painel de gestão
                </p>
                <h1 class="mt-2 text-2xl font-bold sm:text-3xl">Visão geral da operação</h1>
                <p class="mt-1.5 max-w-2xl text-sm dash-muted">
                    Faturamento, recebíveis e comportamento dos alunos no período selecionado.
                </p>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="dash-scope-pill">
                        <i data-lucide="shield-check" class="mr-1 inline h-3.5 w-3.5 align-[-2px]"></i>
                        {{ $scope['level_label'] }}
                    </span>

                    @if ($scope['network_name'])
                        <span class="dash-scope-pill">
                            <i data-lucide="network" class="mr-1 inline h-3.5 w-3.5 align-[-2px]"></i>
                            {{ $scope['network_name'] }}
                        </span>
                    @endif

                    @if ($scope['region_name'])
                        <span class="dash-scope-pill">
                            <i data-lucide="map" class="mr-1 inline h-3.5 w-3.5 align-[-2px]"></i>
                            {{ $scope['region_name'] }}
                        </span>
                    @endif

                    @if ($scope['unit_name'])
                        <span class="dash-scope-pill">
                            <i data-lucide="building-2" class="mr-1 inline h-3.5 w-3.5 align-[-2px]"></i>
                            {{ $scope['unit_name'] }}
                        </span>
                    @elseif ($scope['unit_count'] > 1)
                        <span class="dash-scope-pill">
                            <i data-lucide="building-2" class="mr-1 inline h-3.5 w-3.5 align-[-2px]"></i>
                            {{ $scope['unit_count'] }} unidades
                        </span>
                    @endif

                    <span class="dash-scope-pill">
                        <i data-lucide="calendar-range" class="mr-1 inline h-3.5 w-3.5 align-[-2px]"></i>
                        {{ $period['label'] }}
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" disabled title="Disponível após o módulo de relatórios" class="btn-outline dash-disabled !px-3 !py-2 text-sm">
                    <i data-lucide="clipboard-list" class="mr-1 inline h-4 w-4 align-[-3px]"></i>
                    Painel de pendências
                </button>
                <button type="button" disabled title="Disponível após o módulo de relatórios" class="btn-outline dash-disabled !px-3 !py-2 text-sm">
                    <i data-lucide="user-x" class="mr-1 inline h-4 w-4 align-[-3px]"></i>
                    Alunos sem consumo
                </button>
            </div>
        </div>
    </header>

    {{-- Filters --}}
    <form method="GET" action="{{ route('dashboard') }}" class="dash-card">
        <div class="dash-pad space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="dash-icon-box" style="--tone: var(--color-info); width: 2.1rem; height: 2.1rem;">
                        <i data-lucide="sliders-horizontal"></i>
                    </span>
                    <div>
                        <p class="text-sm font-semibold">Filtros</p>
                        <p class="text-xs dash-muted">Ajuste o escopo e o período de análise</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-1.5">
                    @foreach ($presets as $key => $preset)
                        <a href="{{ $queryWith(['preset' => $key, 'from' => $preset['from'], 'to' => $preset['to']]) }}"
                           class="rounded-full border px-3 py-1.5 text-xs font-semibold transition-colors"
                           style="border-color: {{ $filters['preset'] === $key ? 'var(--color-primary)' : 'var(--color-border)' }};
                                  background: {{ $filters['preset'] === $key ? 'color-mix(in srgb, var(--color-primary) 16%, transparent)' : 'transparent' }};
                                  color: {{ $filters['preset'] === $key ? 'var(--color-primary)' : 'var(--color-text-secondary)' }};">
                            {{ \App\Support\Dashboard\DashboardMetrics::PRESETS[$key] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
                @if ($options['network_filter'])
                    <div>
                        <label for="filterNetwork" class="label-field !mb-1.5 !text-xs">Rede</label>
                        <select id="filterNetwork" name="network_id" class="input-field" onchange="this.form.requestSubmit()">
                            <option value="">Todas as redes</option>
                            @foreach ($options['networks'] as $network)
                                <option value="{{ $network->id }}" @selected((int) $filters['network_id'] === (int) $network->id)>{{ $network->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($options['region_filter'])
                    <div>
                        <label for="filterRegion" class="label-field !mb-1.5 !text-xs">Região</label>
                        <select id="filterRegion" name="region_id" class="input-field" onchange="this.form.requestSubmit()">
                            <option value="">Todas as regiões</option>
                            @foreach ($options['regions'] as $region)
                                <option value="{{ $region->id }}" @selected((int) $filters['region_id'] === (int) $region->id)>{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($options['unit_filter'])
                    <div>
                        <label for="filterUnit" class="label-field !mb-1.5 !text-xs">Unidade</label>
                        <select id="filterUnit" name="unit_id" class="input-field" onchange="this.form.requestSubmit()">
                            <option value="">Todas as unidades</option>
                            @foreach ($options['units'] as $unit)
                                <option value="{{ $unit->id }}" @selected((int) $filters['unit_id'] === (int) $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label for="filterFrom" class="label-field !mb-1.5 !text-xs">Data inicial</label>
                    <input type="date" id="filterFrom" name="from" value="{{ $filters['from'] }}" max="{{ $filters['to'] }}" class="input-field">
                </div>

                <div>
                    <label for="filterTo" class="label-field !mb-1.5 !text-xs">Data final</label>
                    <input type="date" id="filterTo" name="to" value="{{ $filters['to'] }}" max="{{ now()->toDateString() }}" class="input-field">
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-primary flex-1 !py-2.5 text-sm">
                        <i data-lucide="search" class="mr-1 inline h-4 w-4 align-[-3px]"></i>
                        Aplicar
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn-outline !px-3 !py-2.5 text-sm" title="Limpar filtros">
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    </a>
                </div>
            </div>

            <input type="hidden" name="preset" value="{{ $filters['preset'] }}">
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
            <input type="hidden" name="product" value="{{ $filters['product'] }}">
            <input type="hidden" name="method" value="{{ $filters['method'] }}">
            <input type="hidden" name="segment" value="{{ $filters['segment'] }}">

            @if (count($activeChips) > 0)
                <div class="flex flex-wrap items-center gap-2 border-t pt-3" style="border-color: var(--color-border);">
                    <span class="text-[0.7rem] font-bold uppercase tracking-wider dash-muted">Ativos</span>
                    @foreach ($activeChips as $chip)
                        <a href="{{ $chip['remove'] }}"
                           class="dash-chip dash-delta-flat !gap-1.5 transition-opacity hover:opacity-80">
                            {{ $chip['label'] }}
                            <i data-lucide="x" class="h-3 w-3"></i>
                        </a>
                    @endforeach
                    <a href="{{ route('dashboard') }}" class="text-xs font-semibold underline-offset-2 hover:underline" style="color: var(--color-primary);">
                        limpar tudo
                    </a>
                </div>
            @endif
        </div>
    </form>

    {{-- Financial summary --}}
    <section class="space-y-3">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-bold">
                    <i data-lucide="wallet" class="h-5 w-5" style="color: var(--color-primary);"></i>
                    Resultado financeiro
                </h2>
                <p class="mt-0.5 text-xs dash-muted">
                    Comparativo com {{ $period['previous_label'] }}. Cobranças futuras e vencidas consideram o saldo em aberto.
                </p>
            </div>
            <span class="dash-chip dash-delta-flat">
                <i data-lucide="calendar-clock"></i>
                {{ $period['days'] }} dias no período
            </span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-[1.35fr_1fr_1fr_1fr]">
            <a href="{{ $queryWith(['status' => null, 'product' => null, 'method' => null]) }}"
               class="dash-card dash-kpi {{ $filters['status'] === null && $filters['product'] === null && $filters['method'] === null ? 'is-active' : '' }}">
                <div class="dash-pad">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] dash-muted">Total esperado</p>
                            <p class="mt-2 text-3xl font-bold tracking-tight">{{ $money($revenue['total_expected']) }}</p>
                        </div>
                        <span class="dash-icon-box" style="--tone: var(--color-primary);">
                            <i data-lucide="landmark"></i>
                        </span>
                    </div>

                    <div class="mt-4 flex items-center gap-2">
                        <span class="dash-chip {{ $deltaTone($revenue['comparison']['total_expected']) }}">
                            <i data-lucide="{{ $deltaIcon($revenue['comparison']['total_expected']) }}"></i>
                            {{ $percent($revenue['comparison']['total_expected']) }}
                        </span>
                        <span class="text-[0.7rem] dash-muted">vs. período anterior</span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 border-t pt-3 text-xs" style="border-color: var(--color-border);">
                        <div>
                            <p class="dash-muted">Já recebido</p>
                            <p class="mt-0.5 font-bold" style="color: var(--color-success);">{{ $money($revenue['received']) }}</p>
                        </div>
                        <div>
                            <p class="dash-muted">Em aberto</p>
                            <p class="mt-0.5 font-bold" style="color: var(--color-info);">{{ $money($revenue['pending_future'] + $revenue['overdue']) }}</p>
                        </div>
                    </div>

                    <div class="dash-bar mt-3">
                        <span style="width: {{ $revenue['total_expected'] > 0 ? min(100, round(($revenue['received'] / $revenue['total_expected']) * 100)) : 0 }}%; background: var(--color-success);"></span>
                    </div>
                </div>
            </a>

            <a href="{{ $queryWith(['status' => 'paid']) }}"
               class="dash-card dash-kpi {{ $filters['status'] === 'paid' ? 'is-active' : '' }}">
                <div class="dash-pad">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] dash-muted">Recebido</p>
                            <p class="mt-2 text-2xl font-bold">{{ $money($revenue['received']) }}</p>
                        </div>
                        <span class="dash-icon-box" style="--tone: var(--color-success);">
                            <i data-lucide="trending-up"></i>
                        </span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="dash-chip {{ $deltaTone($revenue['comparison']['received']) }}">
                            <i data-lucide="{{ $deltaIcon($revenue['comparison']['received']) }}"></i>
                            {{ $percent($revenue['comparison']['received']) }}
                        </span>
                        <span class="text-[0.7rem] dash-muted">vs. anterior</span>
                    </div>
                </div>
            </a>

            <a href="{{ $queryWith(['status' => 'pending_future']) }}"
               class="dash-card dash-kpi {{ $filters['status'] === 'pending_future' ? 'is-active' : '' }}">
                <div class="dash-pad">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] dash-muted">A receber</p>
                            <p class="mt-2 text-2xl font-bold">{{ $money($revenue['pending_future']) }}</p>
                        </div>
                        <span class="dash-icon-box" style="--tone: var(--color-info);">
                            <i data-lucide="clock-3"></i>
                        </span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="dash-chip {{ $deltaTone($revenue['comparison']['pending_future']) }}">
                            <i data-lucide="{{ $deltaIcon($revenue['comparison']['pending_future']) }}"></i>
                            {{ $percent($revenue['comparison']['pending_future']) }}
                        </span>
                        <span class="text-[0.7rem] dash-muted">futuras</span>
                    </div>
                </div>
            </a>

            <a href="{{ $queryWith(['status' => 'overdue']) }}"
               class="dash-card dash-kpi {{ $filters['status'] === 'overdue' ? 'is-active' : '' }}">
                <div class="dash-pad">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] dash-muted">Vencidas</p>
                            <p class="mt-2 text-2xl font-bold">{{ $money($revenue['overdue']) }}</p>
                        </div>
                        <span class="dash-icon-box" style="--tone: var(--color-danger);">
                            <i data-lucide="triangle-alert"></i>
                        </span>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <span class="dash-chip {{ $deltaTone($revenue['comparison']['overdue']) }}">
                            <i data-lucide="{{ $deltaIcon($revenue['comparison']['overdue']) }}"></i>
                            {{ $percent($revenue['comparison']['overdue']) }}
                        </span>
                        <span class="text-[0.7rem] dash-muted">em atraso</span>
                    </div>
                </div>
            </a>
        </div>
    </section>

    {{-- Financial charts --}}
    <section class="grid grid-cols-1 gap-4 xl:grid-cols-3">
        <div class="dash-card xl:col-span-2">
            <div class="dash-pad">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-bold">
                            <i data-lucide="bar-chart-3" class="h-4 w-4" style="color: var(--color-primary);"></i>
                            Fluxo financeiro no período
                        </h3>
                        <p class="mt-0.5 text-xs dash-muted">Clique em uma coluna para aplicar aquele intervalo como filtro.</p>
                    </div>
                    <div class="flex items-center gap-3 text-[0.7rem] dash-muted">
                        <span class="flex items-center gap-1.5"><span class="dash-legend-dot" style="background: var(--color-success);"></span> Recebido</span>
                        <span class="flex items-center gap-1.5"><span class="dash-legend-dot" style="background: var(--color-info);"></span> A receber</span>
                        <span class="flex items-center gap-1.5"><span class="dash-legend-dot" style="background: var(--color-danger);"></span> Vencido</span>
                    </div>
                </div>
                <div id="revenueChart" class="dash-chart mt-3"></div>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-pad">
                <h3 class="flex items-center gap-2 text-sm font-bold">
                    <i data-lucide="pie-chart" class="h-4 w-4" style="color: var(--color-accent);"></i>
                    Recebido por produto
                </h3>
                <p class="mt-0.5 text-xs dash-muted">Clique em uma fatia para filtrar os demais indicadores.</p>
                <div id="productChart" class="dash-chart-sm mt-2"></div>
            </div>
        </div>
    </section>

    {{-- Financial details --}}
    <section class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        {{-- Received by product --}}
        <div class="dash-card">
            <div class="dash-pad space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="flex items-center gap-2 text-sm font-bold">
                        <i data-lucide="package-check" class="h-4 w-4" style="color: var(--color-success);"></i>
                        Recebido por produto
                    </h3>
                    @if ($filters['product'])
                        <a href="{{ $queryWith(['product' => null]) }}" class="text-[0.7rem] font-semibold" style="color: var(--color-primary);">limpar</a>
                    @endif
                </div>

                @forelse ($revenue['products']['received'] as $product)
                    <a href="{{ $queryWith(['product' => $product['key']]) }}"
                       class="dash-row {{ $filters['product'] === $product['key'] ? 'is-active' : '' }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="truncate text-sm font-medium">{{ $product['label'] }}</span>
                                <span class="text-sm font-bold" style="color: var(--color-success);">{{ $money($product['amount']) }}</span>
                            </div>
                            <div class="dash-bar mt-1.5">
                                <span style="width: {{ $maxProduct > 0 ? max(4, round(($product['amount'] / $maxProduct) * 100)) : 0 }}%; background: var(--color-success);"></span>
                            </div>
                            <p class="mt-1 text-[0.68rem] dash-muted">{{ $product['count'] }} lançamento(s)</p>
                        </div>
                    </a>
                @empty
                    <p class="rounded-lg border border-dashed p-4 text-center text-xs dash-muted" style="border-color: var(--color-border);">
                        Nenhum recebimento no período.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- Pending by product --}}
        <div class="dash-card">
            <div class="dash-pad space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="flex items-center gap-2 text-sm font-bold">
                        <i data-lucide="receipt-text" class="h-4 w-4" style="color: var(--color-info);"></i>
                        A receber por produto
                    </h3>
                    <span class="dash-chip dash-delta-flat">{{ \Carbon\Carbon::parse($filters['to'])->format('d/m/Y') }}</span>
                </div>

                @forelse ($pendingProducts as $product)
                    <a href="{{ $queryWith(['product' => $product['key']]) }}"
                       class="dash-row {{ $filters['product'] === $product['key'] ? 'is-active' : '' }}">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-baseline justify-between gap-2">
                                <span class="truncate text-sm font-medium">{{ $product['label'] }}</span>
                                <span class="text-sm font-bold" style="color: var(--color-info);">{{ $money($product['future'] + $product['overdue']) }}</span>
                            </div>
                            <div class="dash-bar mt-1.5 flex">
                                @php
                                    $totalPending = max(1, $product['future'] + $product['overdue']);
                                @endphp
                                <span style="width: {{ round(($product['future'] / $totalPending) * 100) }}%; background: var(--color-info);"></span>
                                <span style="width: {{ round(($product['overdue'] / $totalPending) * 100) }}%; background: var(--color-danger);"></span>
                            </div>
                            <p class="mt-1 flex flex-wrap gap-x-3 text-[0.68rem] dash-muted">
                                <span>{{ $money($product['future']) }} futuras</span>
                                <span style="color: {{ $product['overdue'] > 0 ? 'var(--color-danger)' : 'inherit' }};">{{ $money($product['overdue']) }} vencidas</span>
                            </p>
                        </div>
                    </a>
                @empty
                    <p class="rounded-lg border border-dashed p-4 text-center text-xs dash-muted" style="border-color: var(--color-border);">
                        Nenhuma cobrança em aberto.
                    </p>
                @endforelse
            </div>
        </div>

        {{-- Methods --}}
        <div class="dash-card">
            <div class="dash-pad space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="flex items-center gap-2 text-sm font-bold">
                        <i data-lucide="credit-card" class="h-4 w-4" style="color: var(--color-secondary);"></i>
                        Formas de pagamento
                    </h3>
                    @if ($filters['method'])
                        <a href="{{ $queryWith(['method' => null]) }}" class="text-[0.7rem] font-semibold" style="color: var(--color-primary);">limpar</a>
                    @endif
                </div>

                <div>
                    <p class="text-[0.68rem] font-bold uppercase tracking-wider dash-muted">Recebido</p>
                    <div class="mt-2 space-y-1.5">
                        @forelse ($revenue['methods']['received'] as $method)
                            <a href="{{ $queryWith(['method' => $method['key']]) }}"
                               class="dash-row !px-2 !py-1.5 {{ $filters['method'] === $method['key'] ? 'is-active' : '' }}">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <span class="truncate text-xs font-medium">{{ $method['label'] }}</span>
                                        <span class="text-xs font-bold">{{ $money($method['amount']) }}</span>
                                    </div>
                                    <div class="dash-bar mt-1" style="height: .28rem;">
                                        <span style="width: {{ $maxMethod > 0 ? max(4, round(($method['amount'] / $maxMethod) * 100)) : 0 }}%; background: var(--color-secondary);"></span>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <p class="text-xs dash-muted">Nenhum recebimento no período.</p>
                        @endforelse
                    </div>
                </div>

                <div class="border-t pt-3" style="border-color: var(--color-border);">
                    <p class="text-[0.68rem] font-bold uppercase tracking-wider dash-muted">A receber</p>
                    <div class="mt-2 space-y-1.5">
                        @forelse ($revenue['methods']['pending'] as $method)
                            <a href="{{ $queryWith(['method' => $method['key']]) }}"
                               class="dash-row !px-2 !py-1.5 {{ $filters['method'] === $method['key'] ? 'is-active' : '' }}">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline justify-between gap-2">
                                        <span class="truncate text-xs font-medium">{{ $method['label'] }}</span>
                                        <span class="text-xs font-bold">{{ $money($method['amount']) }}</span>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <p class="text-xs dash-muted">Nenhuma cobrança em aberto.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Students summary --}}
    <section class="space-y-3">
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-bold">
                    <i data-lucide="users" class="h-5 w-5" style="color: var(--color-info);"></i>
                    Alunos
                </h2>
                <p class="mt-0.5 text-xs dash-muted">
                    Permanência, novos alunos e conversão da aula experimental.
                </p>
            </div>
            <span class="dash-chip dash-delta-flat">
                <i data-lucide="user-round-check"></i>
                {{ number_format($students['total_active'], 0, ',', '.') }} alunos ativos
            </span>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <a href="{{ $queryWith(['segment' => 'recurring']) }}"
               class="dash-card dash-kpi {{ ($filters['segment'] ?? 'recurring') === 'recurring' ? 'is-active' : '' }}">
                <div class="dash-pad">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] dash-muted">Recorrentes</p>
                            <p class="mt-1.5 text-2xl font-bold">{{ number_format($students['recurring'], 0, ',', '.') }}</p>
                        </div>
                        <span class="dash-icon-box" style="--tone: var(--color-success);">
                            <i data-lucide="repeat-2"></i>
                        </span>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <span class="dash-chip {{ $deltaTone($students['recurring_change']) }}">
                            <i data-lucide="{{ $deltaIcon($students['recurring_change']) }}"></i>
                            {{ $percent($students['recurring_change']) }}
                        </span>
                        <span class="text-[0.66rem] dash-muted">com plano ativo</span>
                    </div>
                </div>
            </a>

            <a href="{{ $queryWith(['segment' => 'new']) }}"
               class="dash-card dash-kpi {{ $filters['segment'] === 'new' ? 'is-active' : '' }}">
                <div class="dash-pad">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] dash-muted">Novos</p>
                            <p class="mt-1.5 text-2xl font-bold">{{ number_format($students['new'], 0, ',', '.') }}</p>
                        </div>
                        <span class="dash-icon-box" style="--tone: var(--color-primary);">
                            <i data-lucide="user-plus"></i>
                        </span>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <span class="dash-chip {{ $deltaTone($students['new_change']) }}">
                            <i data-lucide="{{ $deltaIcon($students['new_change']) }}"></i>
                            {{ $percent($students['new_change']) }}
                        </span>
                        <span class="text-[0.66rem] dash-muted">no período</span>
                    </div>
                </div>
            </a>

            <a href="{{ $queryWith(['segment' => 'trial']) }}"
               class="dash-card dash-kpi {{ $filters['segment'] === 'trial' ? 'is-active' : '' }}">
                <div class="dash-pad">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] dash-muted">Experimentais</p>
                            <p class="mt-1.5 text-2xl font-bold">{{ number_format($students['trial'], 0, ',', '.') }}</p>
                        </div>
                        <span class="dash-icon-box" style="--tone: var(--color-accent);">
                            <i data-lucide="flask-conical"></i>
                        </span>
                    </div>
                    <div class="mt-2 text-[0.66rem] dash-muted">aula experimental no período</div>
                </div>
            </a>

            <a href="{{ $queryWith(['segment' => 'converted']) }}"
               class="dash-card dash-kpi {{ $filters['segment'] === 'converted' ? 'is-active' : '' }}">
                <div class="dash-pad">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] dash-muted">Convertidos</p>
                            <p class="mt-1.5 text-2xl font-bold">{{ number_format($students['trial_converted'], 0, ',', '.') }}</p>
                        </div>
                        <span class="dash-icon-box" style="--tone: var(--color-info);">
                            <i data-lucide="badge-check"></i>
                        </span>
                    </div>
                    <div class="mt-2 flex items-center gap-1.5">
                        <span class="dash-chip {{ $students['trial_conversion_rate'] !== null && $students['trial_conversion_rate'] >= 50 ? 'dash-delta-up' : 'dash-delta-flat' }}">
                            <i data-lucide="percent"></i>
                            {{ $students['trial_conversion_rate'] !== null ? number_format($students['trial_conversion_rate'], 1, ',', '.').'%' : '—' }}
                        </span>
                        <span class="text-[0.66rem] dash-muted">conversão</span>
                    </div>
                </div>
            </a>

            <a href="{{ $queryWith(['segment' => 'without_plan']) }}"
               class="dash-card dash-kpi {{ $filters['segment'] === 'without_plan' ? 'is-active' : '' }}">
                <div class="dash-pad">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.12em] dash-muted">Sem plano</p>
                            <p class="mt-1.5 text-2xl font-bold">{{ number_format($students['without_plan'], 0, ',', '.') }}</p>
                        </div>
                        <span class="dash-icon-box" style="--tone: var(--color-danger);">
                            <i data-lucide="user-round-x"></i>
                        </span>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <span class="dash-chip {{ $deltaTone($students['without_plan_change']) }}">
                            <i data-lucide="{{ $deltaIcon($students['without_plan_change']) }}"></i>
                            {{ $percent($students['without_plan_change']) }}
                        </span>
                        <span class="text-[0.66rem] dash-muted">sem consumo</span>
                    </div>
                </div>
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="dash-card xl:col-span-2">
                <div class="dash-pad">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-bold">
                                <i data-lucide="chart-no-axes-column-increasing" class="h-4 w-4" style="color: var(--color-primary);"></i>
                                Novos alunos e experimentais
                            </h3>
                            <p class="mt-0.5 text-xs dash-muted">Clique em uma coluna para analisar aquele intervalo.</p>
                        </div>
                        <span class="dash-chip dash-delta-flat">{{ $period['label'] }}</span>
                    </div>
                    <div id="studentChart" class="dash-chart-sm mt-2"></div>
                </div>
            </div>

            <div class="dash-card">
                <div class="dash-pad">
                    <h3 class="flex items-center gap-2 text-sm font-bold">
                        <i data-lucide="chart-pie" class="h-4 w-4" style="color: var(--color-accent);"></i>
                        Composição dos alunos
                    </h3>
                    <p class="mt-0.5 text-xs dash-muted">Clique em uma fatia para ver a relação abaixo.</p>
                    <div id="compositionChart" class="dash-chart-sm mt-1"></div>
                </div>
            </div>
        </div>

        <div class="dash-card">
            <div class="dash-pad">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-bold">
                            <i data-lucide="list-filter" class="h-4 w-4" style="color: var(--color-info);"></i>
                            {{ $students['highlight']['label'] }}
                        </h3>
                        <p class="mt-0.5 text-xs dash-muted">
                            {{ number_format($students['highlight']['total'], 0, ',', '.') }} aluno(s) no segmento
                            @if ($students['highlight']['total'] > 12)
                                &middot; exibindo os 12 primeiros
                            @endif
                        </p>
                    </div>
                    <button type="button" disabled title="Disponível após o módulo de relatórios" class="btn-outline dash-disabled !px-3 !py-2 text-xs">
                        <i data-lucide="external-link" class="mr-1 inline h-3.5 w-3.5 align-[-2px]"></i>
                        Abrir relação completa
                    </button>
                </div>

                @if ($students['highlight']['students']->isEmpty())
                    <p class="mt-4 rounded-lg border border-dashed p-5 text-center text-xs dash-muted" style="border-color: var(--color-border);">
                        Nenhum aluno neste segmento para o período selecionado.
                    </p>
                @else
                    <div class="mt-3 overflow-x-auto">
                        <table class="dash-table">
                            <thead>
                                <tr>
                                    <th>Aluno</th>
                                    <th>Unidade</th>
                                    <th>Cadastro</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students['highlight']['students'] as $student)
                                    <tr>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <span class="flex h-7 w-7 items-center justify-center rounded-full text-[0.65rem] font-bold"
                                                      style="background: color-mix(in srgb, var(--color-primary) 18%, transparent); color: var(--color-primary);">
                                                    {{ mb_strtoupper(mb_substr($student->name, 0, 1)) }}
                                                </span>
                                                <div class="min-w-0">
                                                    <p class="truncate font-medium">{{ $student->name }}</p>
                                                    <p class="truncate text-[0.68rem] dash-muted">{{ $student->email ?? 'Sem e-mail' }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="dash-muted">{{ $student->units->first()?->unit?->name ?? '—' }}</td>
                                        <td class="dash-muted">{{ $student->created_at?->format('d/m/Y') ?? '—' }}</td>
                                        <td>
                                            <span class="badge {{ $student->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                                                {{ $student->status === 'active' ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Transactions --}}
    <section class="dash-card">
        <div class="dash-pad">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="flex items-center gap-2 text-lg font-bold">
                        <i data-lucide="list-checks" class="h-5 w-5" style="color: var(--color-primary);"></i>
                        Lançamentos
                    </h2>
                    <p class="mt-0.5 text-xs dash-muted">
                        Recebimentos do período e cobranças em aberto, conforme os filtros ativos.
                    </p>
                </div>
                <span class="dash-chip dash-delta-flat">{{ $transactions->count() }} lançamento(s)</span>
            </div>

            @if ($transactions->isEmpty())
                <p class="mt-4 rounded-lg border border-dashed p-6 text-center text-sm dash-muted" style="border-color: var(--color-border);">
                    Nenhum lançamento encontrado para os filtros selecionados.
                </p>
            @else
                <div class="mt-3 overflow-x-auto">
                    <table class="dash-table">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>Produto</th>
                                <th>Unidade</th>
                                <th>Data</th>
                                <th>Pagamento</th>
                                <th>Situação</th>
                                <th class="text-right">Valor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transactions as $transaction)
                                <tr>
                                    <td>
                                        <p class="font-medium">{{ $transaction['charge']->student?->name ?? '—' }}</p>
                                        <p class="max-w-[260px] truncate text-[0.68rem] dash-muted">{{ $transaction['charge']->description }}</p>
                                    </td>
                                    <td>
                                        <span class="badge {{ $transaction['status'] === 'paid' ? 'badge-info' : 'badge-secondary' }}">
                                            {{ $transaction['product_label'] }}
                                        </span>
                                    </td>
                                    <td class="dash-muted">{{ $transaction['charge']->unit?->name ?? '—' }}</td>
                                    <td class="dash-muted">{{ $transaction['date']?->format('d/m/Y') ?? '—' }}</td>
                                    <td class="dash-muted">{{ $transaction['method_label'] }}</td>
                                    <td>
                                        <span class="badge {{ match ($transaction['status']) {
                                            'paid' => 'badge-success',
                                            'overdue' => 'badge-danger',
                                            default => 'badge-warning',
                                        } }}">
                                            {{ $transaction['status_label'] }}
                                        </span>
                                    </td>
                                    <td class="text-right font-semibold">
                                        {{ $money($transaction['amount']) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.min.js"></script>
<script>
    (function () {
        if (window.lucide) {
            window.lucide.createIcons();
        }

        if (typeof ApexCharts === 'undefined') {
            return;
        }

        const dashboardUrl = @json(route('dashboard'));
        const revenueSeries = @json($revenue['series']);
        const productSeries = @json($revenue['products']['received']);
        const methodSeries = @json($revenue['methods']['received']);
        const studentSeries = @json($students['series']);
        const composition = @json($students['composition']);

        const css = (name, fallback) => {
            const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

            return value || fallback;
        };

        const palette = {
            primary: css('--color-primary', '#0d6efd'),
            success: css('--color-success', '#10b981'),
            info: css('--color-info', '#00d4ff'),
            warning: css('--color-warning', '#ffd600'),
            danger: css('--color-danger', '#ef4444'),
            secondary: css('--color-secondary', '#64748b'),
            accent: css('--color-accent', '#ffd600'),
        };

        const isDark = () => document.documentElement.classList.contains('dark');
        const foreColor = () => css('--color-text-secondary', isDark() ? '#a9b8cc' : '#475569');
        const gridColor = () => css('--color-border', isDark() ? '#263b57' : '#cbd5e1');

        const money = (value) => new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        }).format(Number(value || 0));

        const shortMoney = (value) => new Intl.NumberFormat('pt-BR', {
            notation: 'compact',
            maximumFractionDigits: 1,
        }).format(Number(value || 0));

        const navigate = (overrides) => {
            const url = new URL(dashboardUrl, window.location.origin);
            const params = new URLSearchParams(window.location.search);

            Object.entries(overrides).forEach(([key, value]) => {
                if (value === null || value === undefined || value === '') {
                    params.delete(key);
                } else {
                    params.set(key, value);
                }
            });

            url.search = params.toString();
            window.location.href = url.toString();
        };

        const baseChart = {
            chart: {
                background: 'transparent',
                foreColor: foreColor(),
                toolbar: { show: false },
                fontFamily: 'inherit',
                animations: { speed: 420 },
            },
            theme: { mode: isDark() ? 'dark' : 'light' },
            grid: { borderColor: gridColor(), strokeDashArray: 4 },
            tooltip: { theme: isDark() ? 'dark' : 'light' },
        };

        const charts = [];

        // Revenue evolution
        const revenueEl = document.querySelector('#revenueChart');

        if (revenueEl) {
            const chart = new ApexCharts(revenueEl, {
                ...baseChart,
                chart: {
                    ...baseChart.chart,
                    type: 'bar',
                    height: 320,
                    stacked: true,
                    events: {
                        dataPointSelection: (event, context, config) => {
                            const range = revenueSeries.ranges[config.dataPointIndex];

                            if (range) {
                                navigate({ from: range.from, to: range.to, preset: 'custom' });
                            }
                        },
                    },
                },
                series: [
                    { name: 'Recebido', data: revenueSeries.received },
                    { name: 'A receber', data: revenueSeries.pending },
                    { name: 'Vencido', data: revenueSeries.overdue },
                ],
                colors: [palette.success, palette.info, palette.danger],
                plotOptions: {
                    bar: { columnWidth: '52%', borderRadius: 5, borderRadiusApplication: 'end' },
                },
                dataLabels: { enabled: false },
                stroke: { show: false },
                xaxis: {
                    categories: revenueSeries.labels,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: { style: { fontSize: '11px' } },
                },
                yaxis: {
                    labels: { formatter: (value) => 'R$ ' + shortMoney(value) },
                },
                legend: { show: false },
                tooltip: { y: { formatter: (value) => money(value) } },
                noData: { text: 'Sem movimentação no período' },
            });

            chart.render();
            charts.push(chart);
        }

        // Products donut
        const productEl = document.querySelector('#productChart');

        if (productEl) {
            const total = productSeries.reduce((sum, item) => sum + Number(item.amount || 0), 0);

            const chart = new ApexCharts(productEl, {
                ...baseChart,
                chart: {
                    ...baseChart.chart,
                    type: 'donut',
                    height: 300,
                    events: {
                        dataPointSelection: (event, context, config) => {
                            const item = productSeries[config.dataPointIndex];

                            if (item) {
                                navigate({ product: item.key });
                            }
                        },
                    },
                },
                series: productSeries.map((item) => Number(item.amount || 0)),
                labels: productSeries.map((item) => item.label),
                colors: [palette.success, palette.info, palette.primary, palette.warning, palette.accent, palette.secondary],
                dataLabels: { enabled: false },
                stroke: { width: 2, colors: [css('--color-surface', '#0d1b30')] },
                legend: { position: 'bottom', fontSize: '11px', markers: { size: 6 } },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '64%',
                            labels: {
                                show: true,
                                name: { show: true, fontSize: '11px', color: foreColor() },
                                value: {
                                    show: true,
                                    fontSize: '16px',
                                    fontWeight: 700,
                                    color: css('--color-text-primary', '#f8fafc'),
                                    formatter: (value) => money(value),
                                },
                                total: {
                                    show: true,
                                    label: 'Recebido',
                                    fontSize: '11px',
                                    color: foreColor(),
                                    formatter: () => money(total),
                                },
                            },
                        },
                    },
                },
                tooltip: { y: { formatter: (value) => money(value) } },
                noData: { text: 'Sem recebimentos' },
            });

            chart.render();
            charts.push(chart);
        }

        // Students chart
        const studentEl = document.querySelector('#studentChart');

        if (studentEl) {
            const chart = new ApexCharts(studentEl, {
                ...baseChart,
                chart: {
                    ...baseChart.chart,
                    type: 'bar',
                    height: 290,
                    events: {
                        dataPointSelection: (event, context, config) => {
                            const range = studentSeries.ranges[config.dataPointIndex];

                            if (range) {
                                navigate({ from: range.from, to: range.to, preset: 'custom' });
                            }
                        },
                    },
                },
                series: [
                    { name: 'Novos alunos', data: studentSeries.new },
                    { name: 'Experimentais', data: studentSeries.trials },
                ],
                colors: [palette.primary, palette.accent],
                plotOptions: { bar: { columnWidth: '46%', borderRadius: 5, borderRadiusApplication: 'end' } },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: studentSeries.labels,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: { style: { fontSize: '11px' } },
                },
                yaxis: { labels: { formatter: (value) => Number(value).toFixed(0) } },
                legend: { position: 'top', horizontalAlign: 'right', fontSize: '11px', markers: { size: 6 } },
                tooltip: { y: { formatter: (value) => Number(value).toFixed(0) + ' aluno(s)' } },
                noData: { text: 'Sem novos alunos no período' },
            });

            chart.render();
            charts.push(chart);
        }

        // Composition donut
        const compositionEl = document.querySelector('#compositionChart');

        if (compositionEl) {
            const chart = new ApexCharts(compositionEl, {
                ...baseChart,
                chart: {
                    ...baseChart.chart,
                    type: 'donut',
                    height: 300,
                    events: {
                        dataPointSelection: (event, context, config) => {
                            const item = composition[config.dataPointIndex];

                            if (item) {
                                navigate({ segment: item.key });
                            }
                        },
                    },
                },
                series: composition.map((item) => Number(item.value || 0)),
                labels: composition.map((item) => item.label),
                colors: [palette.success, palette.warning, palette.primary, palette.danger],
                dataLabels: { enabled: false },
                stroke: { width: 2, colors: [css('--color-surface', '#0d1b30')] },
                legend: { position: 'bottom', fontSize: '11px', markers: { size: 6 } },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '64%',
                            labels: {
                                show: true,
                                name: { show: true, fontSize: '11px', color: foreColor() },
                                value: {
                                    show: true,
                                    fontSize: '16px',
                                    fontWeight: 700,
                                    color: css('--color-text-primary', '#f8fafc'),
                                    formatter: (value) => Number(value).toFixed(0),
                                },
                                total: {
                                    show: true,
                                    label: 'Alunos',
                                    fontSize: '11px',
                                    color: foreColor(),
                                    formatter: (w) => String(w.globals.seriesTotals.reduce((sum, value) => sum + Number(value), 0)),
                                },
                            },
                        },
                    },
                },
                tooltip: { y: { formatter: (value) => Number(value).toFixed(0) + ' aluno(s)' } },
                noData: { text: 'Sem alunos no período' },
            });

            chart.render();
            charts.push(chart);
        }

        // Keep chart text readable when the theme toggles.
        new MutationObserver(() => {
            charts.forEach((chart) => {
                chart.updateOptions({
                    chart: { foreColor: foreColor() },
                    theme: { mode: isDark() ? 'dark' : 'light' },
                    grid: { borderColor: gridColor() },
                    tooltip: { theme: isDark() ? 'dark' : 'light' },
                }, false, false);
            });
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
    })();
</script>
@endpush
