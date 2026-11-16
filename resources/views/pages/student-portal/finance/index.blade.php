@extends('layouts.student')

@section('title', 'Financeiro')

@section('content')
<div class="space-y-6">
    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Financeiro</p>
                <h1 class="mt-1 text-2xl font-bold">Meus contratos e pagamentos</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    Acompanhe planos, aulas, valores pagos e cobranças pendentes.
                </p>
            </div>

            <a href="{{ route('student.portal.plans', $network) }}" class="btn-outline">
                <i class="fas fa-layer-group mr-2"></i>
                Ver planos
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card">
            <p class="text-sm text-text-secondary">Pendente</p>
            <p class="mt-1 text-2xl font-bold" style="color: var(--color-warning);">
                R$ {{ number_format($pendingTotal, 2, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-text-secondary">{{ $pendingCharges->count() }} cobrança(s)</p>
        </div>

        <div class="card">
            <p class="text-sm text-text-secondary">Contratos</p>
            <p class="mt-1 text-2xl font-bold">{{ $subscriptions->count() + $classBookings->count() }}</p>
            <p class="mt-1 text-xs text-text-secondary">planos e aulas</p>
        </div>

        <div class="card">
            <p class="text-sm text-text-secondary">Plano ativo</p>
            <p class="mt-1 truncate text-lg font-bold">{{ $student->activeSubscription()?->plan_name ?? 'Nenhum' }}</p>
        </div>

        <div class="card">
            <p class="text-sm text-text-secondary">Próxima cobrança</p>
            <p class="mt-1 text-lg font-bold">
                {{ $pendingCharges->sortBy('due_date')->first()?->due_date?->format('d/m/Y') ?? '—' }}
            </p>
        </div>
    </div>

    <section class="card space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-receipt text-cyan"></i>
                Cobranças pendentes
            </h2>
            <span class="text-xs text-text-secondary">Gere o Pix ou combine o pagamento na recepção</span>
        </div>

        @if ($pendingCharges->isEmpty())
            <p class="py-6 text-center text-sm text-text-secondary">Você não possui cobranças pendentes.</p>
        @else
            <div class="space-y-3">
                @foreach ($pendingCharges as $charge)
                    <div class="rounded-xl border border-custom bg-bg-secondary p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="badge badge-info">{{ $charge->type_label }}</span>
                                    <span class="badge badge-warning">{{ $charge->status_label }}</span>
                                    @if ($charge->due_date)
                                        <span class="text-xs text-text-secondary">
                                            Vencimento {{ $charge->due_date->format('d/m/Y') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-2 font-medium">{{ $charge->description }}</p>
                                <p class="mt-1 text-lg font-bold">
                                    R$ {{ number_format((float) $charge->amount, 2, ',', '.') }}
                                </p>
                            </div>

                            <form method="POST" action="{{ route('student.portal.finance.pix', [$network, $charge]) }}">
                                @csrf
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-qrcode mr-2"></i>
                                    Pagar com Pix
                                </button>
                            </form>
                        </div>

                        @if ($charge->pix_qr_code_base64)
                            <div class="mt-4 grid grid-cols-1 gap-3 border-t border-custom pt-4 sm:grid-cols-[180px_1fr]">
                                <img src="data:image/png;base64,{{ $charge->pix_qr_code_base64 }}"
                                     alt="QR Code Pix" class="h-44 w-44 rounded-xl border border-custom bg-white p-2">
                                <div class="space-y-2">
                                    <p class="text-xs font-medium text-text-secondary">Pix copia e cola</p>
                                    <textarea readonly rows="4" class="input-field font-mono text-xs">{{ $charge->pix_qr_code }}</textarea>
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

            @forelse ($subscriptions as $subscription)
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
                Minhas aulas
            </h2>

            @forelse ($classBookings as $booking)
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

                        @if ($booking->isCheckinOpen())
                            <form method="POST" action="{{ route('student.portal.classes.checkin', [$network, $booking]) }}">
                                @csrf
                                <button type="submit" class="btn-primary">
                                    <i class="fas fa-check mr-1"></i>
                                    Check-in
                                </button>
                            </form>
                        @elseif ($booking->isConfirmed() && ! $booking->hasCheckedIn())
                            <span class="text-xs text-text-secondary">Fora da janela</span>
                        @endif
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
            $payments = $subscriptions->flatMap->payments
                ->where('status', 'approved')
                ->sortByDesc('paid_at')
                ->values();
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
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
