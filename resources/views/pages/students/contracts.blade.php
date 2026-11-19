@extends('layouts.app')

@section('title', 'Contratos - '.$student->name)

@section('content')
@php
    $phoneDigits = preg_replace('/\D/', '', $student->phone ?: $student->whatsapp ?: '');
    $whatsappPhone = $phoneDigits !== '' && strlen($phoneDigits) <= 11 ? '55'.$phoneDigits : $phoneDigits;
@endphp

<div class="space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('students.show', $student) }}" class="text-sm text-text-secondary transition-colors hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para o aluno
            </a>
            <h1 class="mt-1 text-2xl font-bold">Contratos e financeiro</h1>
            <p class="mt-1 text-text-secondary">{{ $student->name }} &middot; {{ $student->email ?? 'Sem e-mail' }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('students.technical-sheet', $student) }}" class="btn-outline">
                <i class="fas fa-chart-line mr-2"></i>
                Ficha técnica
            </a>
            @can('students.edit')
                <a href="{{ route('students.sales.create', $student) }}" class="btn-primary">
                    <i class="fas fa-cash-register mr-2"></i>
                    Nova venda
                </a>
            @endcan
        </div>
    </div>

    @include('pages.students._tabs', ['student' => $student, 'activeTab' => 'financial'])

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card">
            <p class="text-sm text-text-secondary">Pendente</p>
            <p class="mt-1 text-2xl font-bold" style="color: var(--color-warning);">
                R$ {{ number_format($metrics['pending_total'], 2, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-text-secondary">{{ $metrics['pending_count'] }} cobrança(s)</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Total pago</p>
            <p class="mt-1 text-2xl font-bold" style="color: var(--color-success);">
                R$ {{ number_format($metrics['paid_total'], 2, ',', '.') }}
            </p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Contratos</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['contracts'] }}</p>
            <p class="mt-1 text-xs text-text-secondary">planos e aulas</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Unidade</p>
            <p class="mt-1 truncate text-lg font-bold">{{ $unit?->name ?? 'Não definida' }}</p>
        </div>
    </div>

    <section class="card space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-file-signature text-cyan"></i>
                Vendas e contratos
            </h2>
            <span class="text-xs text-text-secondary">Datas de lançamento, início e fim por item</span>
        </div>

        @if ($sales->isEmpty())
            <p class="py-6 text-center text-sm text-text-secondary">Nenhuma venda registrada.</p>
        @else
            <div class="space-y-4">
                @foreach ($sales as $sale)
                    <div class="rounded-xl border border-custom bg-bg-secondary p-4">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold">{{ $sale->code ?? 'Venda #'.$sale->id }}</p>
                                <p class="text-xs text-text-secondary">
                                    Lançada em {{ $sale->launched_at?->format('d/m/Y') ?? $sale->created_at?->format('d/m/Y') }}
                                    &middot; {{ $sale->unit?->name ?? 'Unidade' }}
                                    &middot; {{ $sale->items->count() }} item(ns)
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="badge {{ $sale->status === 'paid' ? 'badge-success' : ($sale->status === 'canceled' ? 'badge-secondary' : 'badge-warning') }}">
                                    {{ $sale->status_label }}
                                </span>
                                <span class="text-lg font-bold">R$ {{ number_format((float) $sale->total, 2, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="mt-3 space-y-2">
                            @foreach ($sale->items as $saleItem)
                                <div class="rounded-lg border border-custom bg-surface px-3 py-2">
                                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="badge badge-info">{{ $saleItem->type_label }}</span>
                                                <span class="badge {{ $saleItem->charge?->status === 'paid' ? 'badge-success' : ($saleItem->charge?->status === 'canceled' ? 'badge-secondary' : 'badge-warning') }}">
                                                    {{ $saleItem->charge?->status_label ?? $saleItem->status_label }}
                                                </span>
                                                @if ($saleItem->plan)
                                                    <span class="text-xs text-text-secondary">{{ $saleItem->plan->type_label }}</span>
                                                @endif
                                            </div>
                                            <p class="mt-1 text-sm font-medium">{{ $saleItem->description }}</p>
                                            <p class="text-xs text-text-secondary">
                                                Lançamento {{ $saleItem->launched_at?->format('d/m/Y') ?? '—' }}
                                                &middot; Início {{ $saleItem->starts_at?->format('d/m/Y') ?? '—' }}
                                                &middot; Fim {{ $saleItem->ends_at?->format('d/m/Y') ?? '—' }}
                                                @if ($saleItem->weekly_accesses)
                                                    &middot; {{ $saleItem->weekly_accesses }}x/semana
                                                @endif
                                                @if ($saleItem->daily_checkins)
                                                    &middot; {{ $saleItem->daily_checkins }} check-in(s)/dia
                                                @endif
                                            </p>

                                            @if ($saleItem->slots->isNotEmpty())
                                                <div class="mt-2 flex flex-wrap gap-1">
                                                    <span class="text-xs text-text-secondary">Horários preferenciais:</span>
                                                    @foreach ($saleItem->slots as $preferredSlot)
                                                        <span class="badge badge-secondary">
                                                            {{ $preferredSlot->weekday_label }} {{ substr((string) $preferredSlot->start_time, 0, 5) }}
                                                            &middot; {{ $preferredSlot->schedule?->schoolClass?->name }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        <span class="whitespace-nowrap text-sm font-semibold">
                                            R$ {{ number_format((float) $saleItem->amount, 2, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-receipt text-cyan"></i>
                Cobranças
            </h2>
            <span class="text-xs text-text-secondary">Pendências, Pix, WhatsApp e e-mail</span>
        </div>

        @if ($student->charges->isEmpty())
            <p class="py-6 text-center text-sm text-text-secondary">Nenhuma cobrança registrada.</p>
        @else
            <div class="space-y-3">
                @foreach ($student->charges as $charge)
                    <div class="rounded-xl border border-custom bg-bg-secondary p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="badge badge-info">{{ $charge->type_label }}</span>
                                    <span class="badge {{ $charge->status === 'paid' ? 'badge-success' : ($charge->status === 'pending' ? 'badge-warning' : 'badge-secondary') }}">
                                        {{ $charge->status_label }}
                                    </span>
                                    @if ($charge->due_date)
                                        <span class="text-xs text-text-secondary">Vencimento {{ $charge->due_date->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                                <p class="mt-2 font-medium">{{ $charge->description }}</p>
                                <p class="mt-1 text-lg font-bold">
                                    R$ {{ number_format((float) $charge->amount, 2, ',', '.') }}
                                </p>
                                @if ($charge->paid_at)
                                    <p class="mt-1 text-xs text-text-secondary">
                                        Pago em {{ $charge->paid_at->format('d/m/Y H:i') }}
                                        @if ($charge->payment_method)
                                            &middot; {{ ucfirst($charge->payment_method) }}
                                        @endif
                                    </p>
                                @endif
                            </div>

                            @if ($charge->isPending())
                                @can('students.edit')
                                    <div class="flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('students.contracts.pay', [$student, $charge]) }}"
                                              onsubmit="return confirm('Registrar o pagamento desta cobrança?');">
                                            @csrf
                                            <button type="submit" class="btn-primary">
                                                <i class="fas fa-check mr-1"></i>
                                                Receber agora
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('students.contracts.pix', [$student, $charge]) }}">
                                            @csrf
                                            <button type="submit" class="btn-outline">
                                                <i class="fas fa-qrcode mr-1"></i>
                                                Gerar Pix
                                            </button>
                                        </form>

                                        @if ($whatsappPhone !== '')
                                            <a href="https://wa.me/{{ $whatsappPhone }}?text={{ rawurlencode('Olá '.$student->name.', você possui uma cobrança pendente de R$ '.number_format((float) $charge->amount, 2, ',', '.').' referente a '.$charge->description.'. Acesse o portal para pagar.') }}"
                                               target="_blank" rel="noopener" class="btn-outline">
                                                <i class="fab fa-whatsapp mr-1"></i>
                                                WhatsApp
                                            </a>
                                        @endif

                                        @if ($student->email)
                                            <form method="POST" action="{{ route('students.contracts.email', [$student, $charge]) }}">
                                                @csrf
                                                <button type="submit" class="btn-outline">
                                                    <i class="fas fa-envelope mr-1"></i>
                                                    E-mail
                                                </button>
                                            </form>
                                        @endif

                                        <form method="POST" action="{{ route('students.contracts.cancel', [$student, $charge]) }}"
                                              onsubmit="return confirm('Cancelar esta cobrança?');">
                                            @csrf
                                            <button type="submit" class="btn-outline" style="color: var(--color-danger);">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endcan
                            @endif
                        </div>

                        @if ($charge->pix_qr_code_base64)
                            <div class="mt-4 grid grid-cols-1 gap-3 border-t border-custom pt-4 sm:grid-cols-[180px_1fr]">
                                <img src="data:image/png;base64,{{ $charge->pix_qr_code_base64 }}"
                                     alt="QR Code Pix" class="h-44 w-44 rounded-xl border border-custom bg-white p-2">
                                <div class="space-y-2">
                                    <p class="text-xs font-medium text-text-secondary">Pix copia e cola</p>
                                    <textarea readonly rows="4" class="input-field font-mono text-xs">{{ $charge->pix_qr_code }}</textarea>
                                    <button type="button" class="btn-outline" onclick="copyPixCode(this)">
                                        <i class="fas fa-copy mr-2"></i>
                                        Copiar código Pix
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="card space-y-3">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-layer-group text-cyan"></i>
                Planos e diárias
            </h2>

            @forelse ($student->subscriptions as $subscription)
                <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-medium">{{ $subscription->plan_name }}</p>
                            <p class="text-xs text-text-secondary">
                                {{ $subscription->period_label }}
                                &middot; {{ $subscription->price_label }}
                                &middot; {{ $subscription->plan_type_label }}
                                @if ($subscription->daily_checkins)
                                    &middot; {{ $subscription->daily_checkins }} {{ $subscription->daily_checkin_label }}
                                @endif
                            </p>
                        </div>
                        <span class="badge {{ $subscription->isActive() ? 'badge-success' : 'badge-warning' }}">
                            {{ $subscription->status_label }}
                        </span>
                    </div>
                    <p class="mt-2 text-xs text-text-secondary">
                        @if ($subscription->starts_at || $subscription->ends_at)
                            {{ $subscription->starts_at?->format('d/m/Y') ?? '—' }}
                            até {{ $subscription->ends_at?->format('d/m/Y') ?? '—' }}
                        @else
                            Aguardando pagamento
                        @endif
                    </p>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-text-secondary">Nenhum plano contratado.</p>
            @endforelse
        </section>

        <section class="card space-y-3">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-calendar-check text-cyan"></i>
                Aulas
            </h2>

            @forelse ($student->classBookings as $booking)
                <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="font-medium">{{ $booking->schoolClass?->name ?? 'Aula' }}</p>
                            <p class="text-xs text-text-secondary">
                                {{ $booking->booked_for?->format('d/m/Y') ?? '—' }}
                                &middot; {{ $booking->type_label }}
                                &middot; {{ $booking->status_label }}
                            </p>
                            @if ($booking->credit_available)
                                <p class="mt-1">
                                    <span class="badge badge-success">Crédito disponível para outra reserva</span>
                                </p>
                            @endif
                            @if ($booking->checkin_opens_at)
                                <p class="mt-1 text-xs text-text-secondary">
                                    Check-in: {{ $booking->checkin_window_label }}
                                    @if ($booking->hasCheckedIn())
                                        &middot; <span class="text-green">realizado</span>
                                    @endif
                                </p>
                            @endif
                        </div>
                        <p class="text-sm font-medium">R$ {{ number_format((float) $booking->amount, 2, ',', '.') }}</p>
                    </div>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-text-secondary">Nenhuma aula contratada.</p>
            @endforelse
        </section>
    </div>

    <section class="card space-y-3">
        <h2 class="flex items-center gap-2 font-semibold">
            <i class="fas fa-clock-rotate-left text-cyan"></i>
            Histórico de pagamentos
        </h2>

        @php
            $payments = $student->subscriptions->flatMap->payments->sortByDesc('paid_at')->values();
        @endphp

        @if ($payments->isEmpty())
            <p class="py-4 text-center text-sm text-text-secondary">Nenhum pagamento registrado.</p>
        @else
            <div class="overflow-x-auto">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Origem</th>
                            <th>Valor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($payments as $payment)
                            <tr>
                                <td class="text-sm">{{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-sm">{{ $payment->provider }}</td>
                                <td class="text-sm">
                                    {{ $payment->amount !== null ? 'R$ '.number_format((float) $payment->amount, 2, ',', '.') : '—' }}
                                </td>
                                <td>
                                    <span class="badge {{ $payment->status === 'approved' ? 'badge-success' : 'badge-warning' }}">
                                        {{ $payment->status_label }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>

<script>
    window.copyPixCode = function (button) {
        const textarea = button.parentElement.querySelector('textarea');

        if (!textarea) {
            return;
        }

        navigator.clipboard.writeText(textarea.value).then(function () {
            button.innerHTML = '<i class="fas fa-check mr-2"></i>Código copiado';

            setTimeout(function () {
                button.innerHTML = '<i class="fas fa-copy mr-2"></i>Copiar código Pix';
            }, 2500);
        });
    };
</script>

@endsection
