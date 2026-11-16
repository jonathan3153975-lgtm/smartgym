@extends('layouts.app')

@section('title', 'Caixa')

@php
    $money = fn ($value): string => 'R$ '.number_format((float) $value, 2, ',', '.');
    $queryWith = function (array $overrides): string {
        $query = request()->query();

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
            } else {
                $query[$key] = $value;
            }
        }

        return route('management.cash.index', $query);
    };
    $rowStatus = function ($charge): string {
        if ($charge->status === 'paid') {
            return 'paid';
        }

        if (! $charge->due_date) {
            return 'pending';
        }

        if ($charge->due_date->lt(today())) {
            return 'overdue';
        }

        return $charge->due_date->gt(today()) ? 'upcoming' : 'pending';
    };
    $methods = \App\Models\StudentSalePayment::METHODS;
@endphp

@push('styles')
<style>
    .cash-shell { margin: 0 auto; max-width: 1500px; padding: 1.25rem; display: flex; flex-direction: column; gap: 1.25rem; }
    @media (min-width: 640px) { .cash-shell { padding: 1.75rem; } }

    .cash-card { background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 1rem; }
    .cash-pad { padding: 1rem 1.15rem; }

    .cash-icon { height: 2.5rem; width: 2.5rem; border-radius: .8rem; display: grid; place-items: center; flex-shrink: 0;
        background: color-mix(in srgb, var(--tone, var(--color-primary)) 15%, transparent);
        border: 1px solid color-mix(in srgb, var(--tone, var(--color-primary)) 28%, transparent);
        color: var(--tone, var(--color-primary)); }
    .cash-icon svg { height: 1.1rem; width: 1.1rem; stroke-width: 1.9; }

    .cash-kpi { display: block; transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease; }
    .cash-kpi:hover { transform: translateY(-2px); border-color: color-mix(in srgb, var(--color-primary) 45%, var(--color-border));
        box-shadow: 0 16px 34px -26px color-mix(in srgb, var(--color-primary) 60%, transparent); }
    .cash-kpi.is-active { border-color: var(--color-primary); box-shadow: 0 0 0 1px color-mix(in srgb, var(--color-primary) 50%, transparent); }

    .cash-row-date { white-space: nowrap; font-variant-numeric: tabular-nums; }
    .cash-row-title { align-items: center; display: flex; flex-wrap: wrap; gap: .5rem; }
    .cash-row-sub { color: var(--color-text-secondary); font-size: .74rem; margin-top: .18rem; }
    .cash-row-sub strong { color: var(--color-text-primary); font-weight: 600; }

    .cash-actions { align-items: center; display: flex; gap: .15rem; justify-content: flex-end; }
    .cash-action { align-items: center; border: 1px solid transparent; border-radius: .55rem; color: var(--color-text-secondary);
        display: inline-flex; height: 2rem; justify-content: center; transition: all .15s ease; width: 2rem; }
    .cash-action:hover { background: var(--color-surface-elevated); border-color: var(--color-border); color: var(--color-primary); }
    .cash-action.is-danger:hover { color: var(--color-danger); }
    .cash-action svg { height: .95rem; width: .95rem; }
    .cash-action[disabled] { cursor: not-allowed; opacity: .5; }

    .cash-badge { border-radius: 999px; display: inline-flex; align-items: center; gap: .3rem; font-size: .68rem; font-weight: 700; padding: .22rem .55rem; white-space: nowrap; }
    .cash-badge svg { height: .75rem; width: .75rem; }
    .cash-badge-paid { background: var(--color-success-soft); color: var(--color-success); }
    .cash-badge-pending { background: var(--color-warning-soft); color: var(--color-warning); }
    .cash-badge-overdue { background: var(--color-danger-soft); color: var(--color-danger); }
    .cash-badge-upcoming { background: var(--color-info-soft); color: var(--color-info); }

    .cash-table th { color: var(--color-text-secondary); font-size: .67rem; font-weight: 700; letter-spacing: .08em; padding: .7rem .8rem; text-align: left; text-transform: uppercase; white-space: nowrap; }
    .cash-table td { border-top: 1px solid var(--color-border); padding: .8rem; vertical-align: middle; }
    .cash-table tbody tr { transition: background .15s ease; }
    .cash-table tbody tr:hover { background: color-mix(in srgb, var(--color-primary) 5%, transparent); }

    .cash-tabs { display: flex; flex-wrap: wrap; gap: .4rem; }
    .cash-tab { border: 1px solid var(--color-border); border-radius: 999px; color: var(--color-text-secondary); font-size: .74rem; font-weight: 600; padding: .35rem .7rem; transition: all .15s ease; }
    .cash-tab:hover { border-color: var(--color-primary); color: var(--color-primary); }
    .cash-tab.is-active { background: color-mix(in srgb, var(--color-primary) 15%, transparent); border-color: var(--color-primary); color: var(--color-primary); }

    .cash-modal-grid { display: grid; grid-template-columns: repeat(1, minmax(0, 1fr)); gap: .6rem; }
    @media (min-width: 640px) { .cash-modal-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .cash-modal-action { align-items: flex-start; border: 1px solid var(--color-border); border-radius: .75rem; display: flex; gap: .65rem; padding: .7rem; text-align: left; transition: all .15s ease; width: 100%; }
    .cash-modal-action:hover { background: color-mix(in srgb, var(--color-primary) 6%, transparent); border-color: var(--color-primary); }
    .cash-modal-action .cash-icon { height: 2.1rem; width: 2.1rem; }
    .cash-modal-action .cash-icon svg { height: .95rem; width: .95rem; }

    .cash-student-option { align-items: center; border: 1px solid var(--color-border); border-radius: .65rem; display: flex; justify-content: space-between; padding: .55rem .7rem; transition: all .15s ease; }
    .cash-student-option:hover { background: color-mix(in srgb, var(--color-primary) 6%, transparent); border-color: var(--color-primary); }

    .cash-empty { border: 1px dashed var(--color-border); border-radius: .8rem; padding: 2.5rem 1rem; text-align: center; }
</style>
@endpush

@section('content')
<div class="cash-shell">
    {{-- Header --}}
    <header class="cash-card" style="background: radial-gradient(110% 150% at 0% 0%, color-mix(in srgb, var(--color-primary) 22%, transparent), transparent 55%), var(--color-surface);">
        <div class="cash-pad flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="flex items-center gap-2 text-[0.68rem] font-bold uppercase tracking-[0.18em]" style="color: var(--color-info);">
                    <i data-lucide="briefcase-business" class="h-4 w-4"></i>
                    Gerenciamento
                </p>
                <h1 class="mt-1.5 text-2xl font-bold sm:text-3xl">Caixa</h1>
                <p class="mt-1 max-w-2xl text-sm" style="color: var(--color-text-secondary);">
                    Movimentação de vendas, recebimentos, cobranças pendentes e valores em atraso.
                </p>

                <div class="mt-3 flex flex-wrap items-center gap-2 text-[0.72rem]">
                    <span class="rounded-full border px-2.5 py-1 font-semibold" style="border-color: var(--color-border); color: var(--color-text-secondary);">
                        {{ $scope['level_label'] }}
                    </span>
                    @if ($scope['network_name'])
                        <span class="rounded-full border px-2.5 py-1 font-semibold" style="border-color: var(--color-border); color: var(--color-text-secondary);">
                            {{ $scope['network_name'] }}
                        </span>
                    @endif
                    @if ($scope['region_name'])
                        <span class="rounded-full border px-2.5 py-1 font-semibold" style="border-color: var(--color-border); color: var(--color-text-secondary);">
                            {{ $scope['region_name'] }}
                        </span>
                    @endif
                    @if ($scope['unit_name'])
                        <span class="rounded-full border px-2.5 py-1 font-semibold" style="border-color: var(--color-border); color: var(--color-text-secondary);">
                            {{ $scope['unit_name'] }}
                        </span>
                    @elseif ($scope['unit_count'] > 1)
                        <span class="rounded-full border px-2.5 py-1 font-semibold" style="border-color: var(--color-border); color: var(--color-text-secondary);">
                            {{ $scope['unit_count'] }} unidades
                        </span>
                    @endif
                    <span class="rounded-full border px-2.5 py-1 font-semibold" style="border-color: var(--color-border); color: var(--color-text-secondary);">
                        Referência: {{ $selectedDate->format('d/m/Y') }}
                    </span>
                </div>
            </div>

            @can('students.edit')
                <button type="button" id="openNewSale" class="btn-primary self-start !px-4 !py-2.5 text-sm lg:self-auto">
                    <i data-lucide="circle-plus" class="mr-1 inline h-4 w-4 align-[-3px]"></i>
                    Registrar nova venda
                </button>
            @endcan
        </div>
    </header>

    {{-- Filters --}}
    <form method="GET" action="{{ route('management.cash.index') }}" class="cash-card">
        <div class="cash-pad space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="cash-icon" style="--tone: var(--color-info); height: 2rem; width: 2rem;">
                        <i data-lucide="sliders-horizontal"></i>
                    </span>
                    <div>
                        <p class="text-sm font-semibold">Filtros</p>
                        <p class="text-xs" style="color: var(--color-text-secondary);">Escopo, data de referência e situação</p>
                    </div>
                </div>

                <div class="cash-tabs">
                    <a href="{{ $queryWith(['period' => 'today', 'date' => null]) }}"
                       class="cash-tab {{ $period === 'today' ? 'is-active' : '' }}">Hoje</a>
                    <a href="{{ $queryWith(['period' => 'yesterday', 'date' => null]) }}"
                       class="cash-tab {{ $period === 'yesterday' ? 'is-active' : '' }}">Ontem</a>
                    <a href="{{ $queryWith(['period' => 'tomorrow', 'date' => null]) }}"
                       class="cash-tab {{ $period === 'tomorrow' ? 'is-active' : '' }}">Amanhã</a>
                    <button type="button"
                            class="cash-tab {{ $period === 'custom' ? 'is-active' : '' }}"
                            onclick="document.getElementById('cashDate').focus(); document.getElementById('cashDate').showPicker?.();">
                        Data
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
                @if ($scope['network_filter'])
                    <div>
                        <label for="cashNetwork" class="label-field !mb-1.5 !text-xs">Rede</label>
                        <select id="cashNetwork" name="network_id" class="input-field" onchange="this.form.requestSubmit()">
                            <option value="">Todas as redes</option>
                            @foreach ($scope['networks'] as $network)
                                <option value="{{ $network->id }}" @selected((int) $filters['network_id'] === (int) $network->id)>{{ $network->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($scope['region_filter'])
                    <div>
                        <label for="cashRegion" class="label-field !mb-1.5 !text-xs">Região</label>
                        <select id="cashRegion" name="region_id" class="input-field" onchange="this.form.requestSubmit()">
                            <option value="">Todas as regiões</option>
                            @foreach ($scope['regions'] as $region)
                                <option value="{{ $region->id }}" @selected((int) $filters['region_id'] === (int) $region->id)>{{ $region->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($scope['unit_filter'])
                    <div>
                        <label for="cashUnit" class="label-field !mb-1.5 !text-xs">Unidade</label>
                        <select id="cashUnit" name="unit_id" class="input-field" onchange="this.form.requestSubmit()">
                            <option value="">Todas as unidades</option>
                            @foreach ($scope['units'] as $unit)
                                <option value="{{ $unit->id }}" @selected((int) $filters['unit_id'] === (int) $unit->id)>{{ $unit->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label for="cashDate" class="label-field !mb-1.5 !text-xs">Data</label>
                    <input type="date" id="cashDate" name="date" value="{{ $filters['date'] }}" class="input-field">
                </div>

                <div>
                    <label for="cashStatus" class="label-field !mb-1.5 !text-xs">Situação</label>
                    <select id="cashStatus" name="status" class="input-field" onchange="this.form.requestSubmit()">
                        <option value="all" @selected($filters['status'] === 'all')>Todas</option>
                        <option value="paid" @selected($filters['status'] === 'paid')>Pago</option>
                        <option value="pending" @selected($filters['status'] === 'pending')>Pendente (hoje)</option>
                        <option value="overdue" @selected($filters['status'] === 'overdue')>Atrasado</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="btn-primary flex-1 !py-2.5 text-sm">
                        <i data-lucide="search" class="mr-1 inline h-4 w-4 align-[-3px]"></i>
                        Aplicar
                    </button>
                    <a href="{{ route('management.cash.index') }}" class="btn-outline !px-3 !py-2.5" title="Limpar filtros">
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    </a>
                </div>
            </div>

            <input type="hidden" name="period" value="{{ $period }}">

            <p class="text-[0.7rem]" style="color: var(--color-text-secondary);">
                Atrasados são considerados até a data de referência, limitada a hoje. Ao selecionar uma data futura,
                a referência de atraso continua sendo o dia atual.
            </p>
        </div>
    </form>

    {{-- Totals --}}
    <section class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ $queryWith(['status' => 'paid']) }}"
           class="cash-card cash-kpi {{ $filters['status'] === 'paid' ? 'is-active' : '' }}">
            <div class="cash-pad">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.12em]" style="color: var(--color-text-secondary);">Recebido</p>
                        <p class="mt-1.5 text-2xl font-bold">{{ $money($totals['received']) }}</p>
                        <p class="mt-1 text-[0.7rem]" style="color: var(--color-text-secondary);">{{ $totals['received_count'] }} lançamento(s)</p>
                    </div>
                    <span class="cash-icon" style="--tone: var(--color-success);"><i data-lucide="trending-up"></i></span>
                </div>
            </div>
        </a>

        <a href="{{ $queryWith(['status' => 'pending']) }}"
           class="cash-card cash-kpi {{ $filters['status'] === 'pending' ? 'is-active' : '' }}">
            <div class="cash-pad">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.12em]" style="color: var(--color-text-secondary);">Pendente hoje</p>
                        <p class="mt-1.5 text-2xl font-bold">{{ $money($totals['pending']) }}</p>
                        <p class="mt-1 text-[0.7rem]" style="color: var(--color-text-secondary);">{{ $totals['pending_count'] }} lançamento(s)</p>
                    </div>
                    <span class="cash-icon" style="--tone: var(--color-warning);"><i data-lucide="clock-3"></i></span>
                </div>
            </div>
        </a>

        <a href="{{ $queryWith(['status' => 'overdue']) }}"
           class="cash-card cash-kpi {{ $filters['status'] === 'overdue' ? 'is-active' : '' }}">
            <div class="cash-pad">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.12em]" style="color: var(--color-text-secondary);">Atrasado</p>
                        <p class="mt-1.5 text-2xl font-bold">{{ $money($totals['overdue']) }}</p>
                        <p class="mt-1 text-[0.7rem]" style="color: var(--color-text-secondary);">{{ $totals['overdue_count'] }} lançamento(s)</p>
                    </div>
                    <span class="cash-icon" style="--tone: var(--color-danger);"><i data-lucide="triangle-alert"></i></span>
                </div>
            </div>
        </a>

        <a href="{{ $queryWith(['status' => 'all']) }}"
           class="cash-card cash-kpi {{ $filters['status'] === 'all' ? 'is-active' : '' }}">
            <div class="cash-pad">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[0.68rem] font-bold uppercase tracking-[0.12em]" style="color: var(--color-text-secondary);">Em aberto</p>
                        <p class="mt-1.5 text-2xl font-bold">{{ $money($totals['pending'] + $totals['overdue']) }}</p>
                        <p class="mt-1 text-[0.7rem]" style="color: var(--color-text-secondary);">{{ $totals['pending_count'] + $totals['overdue_count'] }} lançamento(s)</p>
                    </div>
                    <span class="cash-icon" style="--tone: var(--color-info);"><i data-lucide="wallet"></i></span>
                </div>
            </div>
        </a>
    </section>

    {{-- Movement --}}
    <section class="cash-card overflow-hidden">
        <div class="cash-pad flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="flex items-center gap-2 text-lg font-bold">
                    <i data-lucide="arrow-left-right" class="h-5 w-5" style="color: var(--color-primary);"></i>
                    Movimentação de vendas
                </h2>
                <p class="mt-0.5 text-xs" style="color: var(--color-text-secondary);">
                    {{ $charges->total() }} registro(s) para {{ $selectedDate->format('d/m/Y') }}
                </p>
            </div>
            <span class="badge badge-secondary">{{ ucfirst($filters['status'] === 'all' ? 'todas as situações' : $filters['status']) }}</span>
        </div>

        @if ($charges->isEmpty())
            <div class="px-4 pb-5">
                <div class="cash-empty">
                    <i data-lucide="inbox" class="mx-auto h-8 w-8" style="color: var(--color-text-secondary);"></i>
                    <p class="mt-2 text-sm font-medium">Nenhum lançamento encontrado</p>
                    <p class="mt-1 text-xs" style="color: var(--color-text-secondary);">Ajuste a data, o escopo ou a situação para visualizar outros registros.</p>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="cash-table w-full text-sm">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Aluno / cobrança</th>
                            <th>Situação</th>
                            <th class="text-right">Valor</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($charges as $charge)
                            @php
                                $statusKey = $rowStatus($charge);
                                $actions = $chargeActions[$charge->id];
                                $reference = $charge->status === 'paid' ? $charge->paid_at : $charge->due_date;
                                $installmentPlan = $installments[$charge->id] ?? null;
                            @endphp
                            <tr>
                                <td class="cash-row-date">
                                    <span class="font-semibold">{{ $reference?->format('d/m/Y') ?? '—' }}</span>
                                    <span class="block text-[0.68rem]" style="color: var(--color-text-secondary);">
                                        {{ $charge->status === 'paid' ? 'recebido' : 'vencimento' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="cash-row-title">
                                        <span class="font-semibold">{{ $charge->student?->name ?? 'Aluno não identificado' }}</span>
                                        @if ($charge->unit)
                                            <span class="text-[0.68rem]" style="color: var(--color-text-secondary);">· {{ $charge->unit->name }}</span>
                                        @endif
                                    </div>
                                    <p class="cash-row-sub">
                                        Pagamento <strong>{{ $actions['method'] }}</strong>
                                        &middot; {{ $charge->description }}
                                        @if ($charge->sale?->code)
                                            &middot; <span class="font-mono">{{ $charge->sale->code }}</span>
                                        @endif
                                    </p>
                                </td>
                                <td>
                                    @if ($statusKey === 'paid')
                                        <span class="cash-badge cash-badge-paid">
                                            <i data-lucide="check-circle-2"></i>
                                            Recebido
                                        </span>
                                    @elseif ($statusKey === 'overdue')
                                        <span class="cash-badge cash-badge-overdue">
                                            <i data-lucide="triangle-alert"></i>
                                            Atrasado desde {{ $charge->due_date?->format('d/m/Y') ?? '—' }}
                                        </span>
                                    @elseif ($statusKey === 'upcoming')
                                        <span class="cash-badge cash-badge-upcoming">
                                            <i data-lucide="calendar-clock"></i>
                                            A vencer {{ $charge->due_date?->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="cash-badge cash-badge-pending">
                                            <i data-lucide="clock-3"></i>
                                            Pendente hoje
                                        </span>
                                    @endif
                                </td>
                                <td class="text-right font-semibold">{{ $money($charge->amount) }}</td>
                                <td>
                                    <div class="cash-actions">
                                        @if ($statusKey === 'paid')
                                            <a href="{{ route('management.cash.receipt', $charge) }}"
                                               class="cash-action" title="Recibo">
                                                <i data-lucide="receipt-text"></i>
                                            </a>
                                            <a href="{{ route('management.cash.receipt', ['charge' => $charge, 'doc' => 'comprovante']) }}"
                                               class="cash-action" title="Comprovante">
                                                <i data-lucide="file-check-2"></i>
                                            </a>
                                            <button type="button"
                                                    class="cash-action open-installments"
                                                    data-charge-id="{{ $charge->id }}"
                                                    title="Próximas parcelas">
                                                <i data-lucide="calendar-clock"></i>
                                            </button>
                                        @else
                                            @can('students.edit')
                                                <button type="button"
                                                        class="cash-action open-charge"
                                                        data-charge-id="{{ $charge->id }}"
                                                        title="Gerar cobrança">
                                                    <i data-lucide="qr-code"></i>
                                                </button>

                                                <form id="cash-confirm-{{ $charge->id }}"
                                                      method="POST"
                                                      action="{{ route('management.cash.confirm', $charge) }}">
                                                    @csrf
                                                    <input type="hidden" name="method" value="manual">
                                                    <button type="button"
                                                            class="cash-action js-confirm-payment"
                                                            data-form="cash-confirm-{{ $charge->id }}"
                                                            data-student="{{ $charge->student?->name }}"
                                                            data-amount="{{ $money($charge->amount) }}"
                                                            title="Confirmar pagamento">
                                                        <i data-lucide="badge-check"></i>
                                                    </button>
                                                </form>

                                                @if ($statusKey === 'overdue')
                                                    <form id="cash-notify-{{ $charge->id }}"
                                                          method="POST"
                                                          action="{{ route('management.cash.notify', $charge) }}">
                                                        @csrf
                                                        <button type="button"
                                                                class="cash-action js-notify-student"
                                                                data-form="cash-notify-{{ $charge->id }}"
                                                                data-student="{{ $charge->student?->name }}"
                                                                title="Enviar notificação">
                                                            <i data-lucide="bell-ring"></i>
                                                        </button>
                                                    </form>

                                                    <button type="button"
                                                            class="cash-action js-generate-link"
                                                            data-charge-id="{{ $charge->id }}"
                                                            title="Gerar link de pagamento">
                                                        <i data-lucide="link"></i>
                                                    </button>
                                                @endif
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t px-4 py-3" style="border-color: var(--color-border);">
                {{ $charges->links() }}
            </div>
        @endif
    </section>
</div>

{{-- New sale modal --}}
@can('students.edit')
    <div id="newSaleModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="newSaleTitle">
        <div class="modal-content modal-content-lg">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h2 id="newSaleTitle" class="text-xl font-bold">Registrar nova venda</h2>
                    <p class="mt-1 text-sm" style="color: var(--color-text-secondary);">Selecione o aluno para abrir a venda presencial.</p>
                </div>
                <button type="button" class="rounded-lg p-2 transition-colors hover:bg-surface" data-close-modal="newSaleModal" aria-label="Fechar">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <input type="search" id="newSaleSearch" class="input-field" placeholder="Buscar aluno por nome...">

            <div id="newSaleList" class="mt-3 max-h-80 space-y-1.5 overflow-y-auto pr-1">
                @forelse ($saleStudents as $saleStudent)
                    <a href="{{ route('students.sales.create', $saleStudent) }}"
                       class="cash-student-option"
                       data-student-name="{{ mb_strtolower($saleStudent->name) }}">
                        <span class="flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full text-[0.65rem] font-bold"
                                  style="background: color-mix(in srgb, var(--color-primary) 18%, transparent); color: var(--color-primary);">
                                {{ mb_strtoupper(mb_substr($saleStudent->name, 0, 1)) }}
                            </span>
                            <span class="text-sm font-medium">{{ $saleStudent->name }}</span>
                        </span>
                        <i data-lucide="arrow-right" class="h-4 w-4" style="color: var(--color-text-secondary);"></i>
                    </a>
                @empty
                    <p class="rounded-lg border border-dashed p-6 text-center text-sm" style="border-color: var(--color-border); color: var(--color-text-secondary);">
                        Nenhum aluno ativo disponível neste escopo.
                    </p>
                @endforelse
            </div>

            <p id="newSaleEmpty" class="hidden mt-3 text-center text-sm" style="color: var(--color-text-secondary);">
                Nenhum aluno encontrado para a busca.
            </p>
        </div>
    </div>
@endcan

{{-- Charge actions modal --}}
<div id="chargeModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="chargeModalTitle">
    <div class="modal-content modal-content-lg">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h2 id="chargeModalTitle" class="text-xl font-bold">Gerar cobrança</h2>
                <p id="chargeModalSubtitle" class="mt-1 text-sm" style="color: var(--color-text-secondary);"></p>
            </div>
            <button type="button" class="rounded-lg p-2 transition-colors hover:bg-surface" data-close-modal="chargeModal" aria-label="Fechar">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="rounded-xl border p-3" style="border-color: var(--color-border); background: var(--color-bg-secondary);">
            <p id="chargeModalDescription" class="font-semibold"></p>
            <div class="mt-1 flex flex-wrap items-center justify-between gap-2 text-sm">
                <span id="chargeModalDue" style="color: var(--color-text-secondary);"></span>
                <strong id="chargeModalAmount" class="text-lg"></strong>
            </div>
        </div>

        <div id="chargeInterestBlock" class="mt-3 hidden rounded-xl border p-3" style="border-color: var(--color-border);">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-sm font-semibold">Juros / multa por atraso</p>
                <p class="text-[0.7rem]" style="color: var(--color-text-secondary);">Informe percentual ou valor fixo</p>
            </div>
            <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <div>
                    <label class="label-field !mb-1 !text-xs" for="interestPercent">Percentual (%)</label>
                    <input type="number" id="interestPercent" min="0" max="100" step="0.01" class="input-field" placeholder="0,00">
                </div>
                <div>
                    <label class="label-field !mb-1 !text-xs" for="interestAmount">Valor fixo (R$)</label>
                    <input type="number" id="interestAmount" min="0" step="0.01" class="input-field" placeholder="0,00">
                </div>
            </div>
            <div class="mt-2 flex items-center justify-between rounded-lg px-2 py-1.5 text-sm" style="background: var(--color-surface-elevated);">
                <span style="color: var(--color-text-secondary);">Total atualizado</span>
                <strong id="interestTotal" class="text-base"></strong>
            </div>
        </div>

        <div class="cash-modal-grid mt-4">
            <form id="chargePixForm" method="POST">
                @csrf
                <input type="hidden" name="interest_percent" class="js-interest-percent">
                <input type="hidden" name="interest_amount" class="js-interest-amount">
                <button type="submit" class="cash-modal-action">
                    <span class="cash-icon" style="--tone: var(--color-info);"><i data-lucide="qr-code"></i></span>
                    <span>
                        <span class="block text-sm font-semibold">Gerar QR Code Pix</span>
                        <span class="block text-xs" style="color: var(--color-text-secondary);">Cria o Pix no Mercado Pago</span>
                    </span>
                </button>
            </form>

            <form id="chargeEmailForm" method="POST">
                @csrf
                <input type="hidden" name="interest_percent" class="js-interest-percent">
                <input type="hidden" name="interest_amount" class="js-interest-amount">
                <button type="submit" class="cash-modal-action">
                    <span class="cash-icon" style="--tone: var(--color-primary);"><i data-lucide="mail"></i></span>
                    <span>
                        <span class="block text-sm font-semibold">Enviar por e-mail</span>
                        <span class="block text-xs" style="color: var(--color-text-secondary);">Envia o link do portal ao aluno</span>
                    </span>
                </button>
            </form>

            <button type="button" id="chargeLinkButton" class="cash-modal-action">
                <span class="cash-icon" style="--tone: var(--color-accent);"><i data-lucide="link"></i></span>
                <span>
                    <span class="block text-sm font-semibold">Gerar link de pagamento</span>
                    <span class="block text-xs" style="color: var(--color-text-secondary);">Checkout do Mercado Pago</span>
                </span>
            </button>

            <a href="#" id="chargeWhatsApp" target="_blank" rel="noopener" class="cash-modal-action">
                <span class="cash-icon" style="--tone: var(--color-success);"><i data-lucide="message-circle"></i></span>
                <span>
                    <span class="block text-sm font-semibold">Enviar por WhatsApp</span>
                    <span class="block text-xs" style="color: var(--color-text-secondary);">Abre a conversa com a mensagem</span>
                </span>
            </a>
        </div>

        <div class="mt-4 border-t pt-4" style="border-color: var(--color-border);">
            <p class="mb-2 text-xs font-bold uppercase tracking-wider" style="color: var(--color-text-secondary);">Baixa manual</p>
            <form id="chargeConfirmForm" method="POST" class="flex flex-col gap-2 sm:flex-row">
                @csrf
                <input type="hidden" name="interest_percent" class="js-interest-percent">
                <input type="hidden" name="interest_amount" class="js-interest-amount">
                <select name="method" class="input-field sm:max-w-[240px]">
                    @foreach ($methods as $methodKey => $methodLabel)
                        <option value="{{ $methodKey }}">{{ $methodLabel }}</option>
                    @endforeach
                    <option value="manual">Outro / manual</option>
                </select>
                <button type="submit" class="btn-primary flex-1">
                    <i data-lucide="badge-check" class="mr-1 inline h-4 w-4 align-[-3px]"></i>
                    Confirmar pagamento
                </button>
            </form>
        </div>
    </div>
</div>

{{-- Installments modal --}}
<div id="installmentsModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="installmentsTitle">
    <div class="modal-content modal-content-lg">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h2 id="installmentsTitle" class="text-xl font-bold">Próximas parcelas</h2>
                <p id="installmentsSubtitle" class="mt-1 text-sm" style="color: var(--color-text-secondary);"></p>
            </div>
            <button type="button" class="rounded-lg p-2 transition-colors hover:bg-surface" data-close-modal="installmentsModal" aria-label="Fechar">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div id="installmentsContent"></div>
    </div>
</div>

{{-- Payment link modal --}}
<div id="linkModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="linkModalTitle">
    <div class="modal-content">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h2 id="linkModalTitle" class="text-xl font-bold">Link de pagamento</h2>
                <p class="mt-1 text-sm" style="color: var(--color-text-secondary);">Compartilhe o link com o aluno.</p>
            </div>
            <button type="button" class="rounded-lg p-2 transition-colors hover:bg-surface" data-close-modal="linkModal" aria-label="Fechar">
                <i data-lucide="x"></i>
            </button>
        </div>

        <input type="text" id="paymentLinkInput" class="input-field" readonly>

        <div class="mt-3 flex flex-col gap-2 sm:flex-row">
            <button type="button" id="copyPaymentLink" class="btn-primary flex-1">
                <i data-lucide="copy" class="mr-1 inline h-4 w-4 align-[-3px]"></i>
                Copiar link
            </button>
            <a id="openPaymentLink" href="#" target="_blank" rel="noopener" class="btn-outline flex-1 text-center">
                Abrir link
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/lucide@0.468.0/dist/umd/lucide.min.js"></script>
<script>
    (function () {
        if (window.lucide) {
            window.lucide.createIcons();
        }

        const chargeActions = @json($chargeActions);
        const installmentPlans = @json($installments);

        const money = (value) => Number(value || 0).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        });

        function openModal(id) {
            document.getElementById(id)?.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id)?.classList.add('hidden');
            document.body.style.overflow = '';
        }

        document.querySelectorAll('[data-close-modal]').forEach((button) => {
            button.addEventListener('click', () => closeModal(button.dataset.closeModal));
        });

        document.querySelectorAll('.modal-overlay').forEach((overlay) => {
            overlay.addEventListener('click', (event) => {
                if (event.target === overlay) {
                    closeModal(overlay.id);
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                document.querySelectorAll('.modal-overlay:not(.hidden)').forEach((overlay) => closeModal(overlay.id));
            }
        });

        // New sale
        const newSaleModal = document.getElementById('newSaleModal');
        const newSaleSearch = document.getElementById('newSaleSearch');
        const newSaleList = document.getElementById('newSaleList');
        const newSaleEmpty = document.getElementById('newSaleEmpty');

        document.getElementById('openNewSale')?.addEventListener('click', () => {
            if (newSaleSearch) {
                newSaleSearch.value = '';
            }

            newSaleList?.querySelectorAll('.cash-student-option').forEach((option) => option.classList.remove('hidden'));
            newSaleEmpty?.classList.add('hidden');
            openModal('newSaleModal');
            newSaleSearch?.focus();
        });

        newSaleSearch?.addEventListener('input', function () {
            const term = this.value.trim().toLowerCase();
            let visible = 0;

            newSaleList?.querySelectorAll('.cash-student-option').forEach((option) => {
                const matches = ! term || (option.dataset.studentName || '').includes(term);
                option.classList.toggle('hidden', ! matches);

                if (matches) {
                    visible++;
                }
            });

            newSaleEmpty?.classList.toggle('hidden', visible > 0);
        });

        // Confirm payment
        document.querySelectorAll('.js-confirm-payment').forEach((button) => {
            button.addEventListener('click', function () {
                const form = document.getElementById(this.dataset.form);

                alertConfirm(
                    'Confirmar pagamento',
                    `Confirmar o recebimento de ${this.dataset.amount} de ${this.dataset.student}?`,
                    () => form?.submit()
                );
            });
        });

        // Notify student
        document.querySelectorAll('.js-notify-student').forEach((button) => {
            button.addEventListener('click', function () {
                const form = document.getElementById(this.dataset.form);

                alertConfirm(
                    'Enviar notificação',
                    `Enviar um aviso sobre a cobrança para ${this.dataset.student}?`,
                    () => form?.submit()
                );
            });
        });

        // Charge actions modal
        const chargeModal = document.getElementById('chargeModal');
        const chargePixForm = document.getElementById('chargePixForm');
        const chargeEmailForm = document.getElementById('chargeEmailForm');
        const chargeConfirmForm = document.getElementById('chargeConfirmForm');
        const chargeLinkButton = document.getElementById('chargeLinkButton');
        const chargeWhatsApp = document.getElementById('chargeWhatsApp');
        const interestBlock = document.getElementById('chargeInterestBlock');
        const interestPercentInput = document.getElementById('interestPercent');
        const interestAmountInput = document.getElementById('interestAmount');
        const interestTotalLabel = document.getElementById('interestTotal');
        let activeChargeId = null;

        function activeCharge() {
            return activeChargeId ? chargeActions[activeChargeId] : null;
        }

        function syncInterestInputs() {
            const action = activeCharge();

            if (! action) {
                return;
            }

            const percent = Number(interestPercentInput?.value || 0);
            const fixed = Number(interestAmountInput?.value || 0);
            const base = Number(action.amount_value || 0);
            const interest = fixed > 0 ? fixed : (base * percent) / 100;
            const total = base + interest;

            if (interestTotalLabel) {
                interestTotalLabel.textContent = money(total);
            }

            document.querySelectorAll('#chargeModal .js-interest-percent').forEach((input) => {
                input.value = fixed > 0 ? '' : String(percent);
            });

            document.querySelectorAll('#chargeModal .js-interest-amount').forEach((input) => {
                input.value = fixed > 0 ? String(fixed) : '';
            });
        }

        function openChargeModal(id) {
            activeChargeId = Number(id);
            const action = chargeActions[activeChargeId];

            if (! action) {
                return;
            }

            document.getElementById('chargeModalSubtitle').textContent = action.student;
            document.getElementById('chargeModalDescription').textContent = action.description;
            document.getElementById('chargeModalDue').textContent = action.due_date
                ? 'Vencimento: ' + action.due_date
                : 'Sem vencimento definido';
            document.getElementById('chargeModalAmount').textContent = action.amount;
            chargePixForm.action = action.pix_url;
            chargeEmailForm.action = action.email_url;
            chargeConfirmForm.action = action.confirm_url;

            interestBlock?.classList.toggle('hidden', ! action.is_overdue);

            if (interestPercentInput) {
                interestPercentInput.value = action.interest_percent ?? '';
            }

            if (interestAmountInput) {
                interestAmountInput.value = action.interest_amount ?? '';
            }

            syncInterestInputs();

            const phone = String(action.phone || '').replace(/\D/g, '');
            const message = `Olá ${action.student}, sua cobrança de ${action.amount} referente a "${action.description}" está em aberto. Regularize pelo portal do aluno ou fale com a unidade.`;
            chargeWhatsApp.href = phone
                ? `https://wa.me/${phone.length <= 11 ? '55' + phone : phone}?text=${encodeURIComponent(message)}`
                : '#';
            chargeWhatsApp.classList.toggle('opacity-50', ! phone);
            chargeWhatsApp.classList.toggle('pointer-events-none', ! phone);

            openModal('chargeModal');
        }

        document.querySelectorAll('.open-charge, .js-generate-link').forEach((button) => {
            button.addEventListener('click', function () {
                openChargeModal(this.dataset.chargeId);
            });
        });

        interestPercentInput?.addEventListener('input', syncInterestInputs);
        interestAmountInput?.addEventListener('input', syncInterestInputs);

        chargePixForm?.addEventListener('submit', syncInterestInputs);
        chargeEmailForm?.addEventListener('submit', syncInterestInputs);
        chargeConfirmForm?.addEventListener('submit', syncInterestInputs);

        chargeLinkButton?.addEventListener('click', async function () {
            if (! activeChargeId) {
                return;
            }

            syncInterestInputs();

            this.disabled = true;

            try {
                const response = await fetch(chargeActions[activeChargeId].link_url, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': window.SmartGym.csrfToken,
                    },
                    body: JSON.stringify({
                        interest_percent: interestPercentInput?.value || 0,
                        interest_amount: interestAmountInput?.value || 0,
                    }),
                });

                const data = await response.json();

                if (! response.ok) {
                    alertError('Link de pagamento', data.message || 'Não foi possível gerar o link.');
                    return;
                }

                document.getElementById('paymentLinkInput').value = data.url;
                document.getElementById('openPaymentLink').href = data.url;
                closeModal('chargeModal');
                openModal('linkModal');
            } catch (error) {
                alertError('Link de pagamento', 'Não foi possível gerar o link agora.');
            } finally {
                this.disabled = false;
            }
        });

        document.getElementById('copyPaymentLink')?.addEventListener('click', async function () {
            const input = document.getElementById('paymentLinkInput');

            try {
                await navigator.clipboard.writeText(input.value);
                alertSuccess('Link copiado', 'O link de pagamento está na área de transferência.');
            } catch (error) {
                input.select();
                document.execCommand('copy');
                alertSuccess('Link copiado', 'O link de pagamento está na área de transferência.');
            }
        });

        // Installments modal
        document.querySelectorAll('.open-installments').forEach((button) => {
            button.addEventListener('click', function () {
                const id = this.dataset.chargeId;
                const plan = installmentPlans[id];
                const content = document.getElementById('installmentsContent');
                const subtitle = document.getElementById('installmentsSubtitle');

                if (! plan) {
                    subtitle.textContent = 'Esta cobrança não possui parcelamento.';
                    content.innerHTML = `
                        <div class="cash-empty">
                            <p class="text-sm font-medium">Sem parcelas futuras</p>
                            <p class="mt-1 text-xs" style="color: var(--color-text-secondary);">O pagamento foi registrado em parcela única.</p>
                        </div>
                    `;
                    openModal('installmentsModal');
                    return;
                }

                subtitle.textContent = `${plan.count} parcela(s) · total de ${money(plan.total)}`;

                const rows = plan.items.map((item) => `
                    <tr>
                        <td class="font-semibold">${item.number}ª</td>
                        <td>${new Date(item.due_date + 'T12:00:00').toLocaleDateString('pt-BR')}</td>
                        <td style="color: var(--color-text-secondary);">${item.method}</td>
                        <td class="text-right font-semibold">${money(item.amount)}</td>
                    </tr>
                `).join('');

                content.innerHTML = `
                    <div class="overflow-x-auto">
                        <table class="cash-table w-full text-sm">
                            <thead>
                                <tr>
                                    <th>Parcela</th>
                                    <th>Vencimento</th>
                                    <th>Forma</th>
                                    <th class="text-right">Valor</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                    <p class="mt-3 text-xs" style="color: var(--color-text-secondary);">
                        As parcelas futuras são projetadas a partir do pagamento registrado e não geram cobranças automáticas nesta etapa.
                    </p>
                `;

                openModal('installmentsModal');
            });
        });
    })();
</script>
@endpush
