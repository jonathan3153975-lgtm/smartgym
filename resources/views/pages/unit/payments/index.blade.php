@extends('layouts.app')

@section('title', 'Pagamentos da unidade')

@section('content')
<div class="container mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    @include('pages.unit._nav', ['unit' => $unit, 'units' => $units])

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card">
            <p class="text-sm text-text-secondary">Assinaturas ativas</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['active'] }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Aguardando pagamento</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['pending'] }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Vencendo em 7 dias</p>
            <p class="mt-1 text-2xl font-bold">{{ $metrics['expiring'] }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Receita no mês</p>
            <p class="mt-1 text-2xl font-bold">
                R$ {{ number_format($metrics['revenue'] + $metrics['class_revenue'], 2, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-text-secondary">
                planos R$ {{ number_format($metrics['revenue'], 2, ',', '.') }}
                &middot; aulas R$ {{ number_format($metrics['class_revenue'], 2, ',', '.') }}
            </p>
        </div>
    </div>

    <div class="card space-y-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold">Assinaturas e pagamentos</h2>
                <p class="mt-1 text-sm text-text-secondary">Acompanhe planos, pendências e confirmações manuais da unidade.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('unit.payments.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label class="label-field" for="payment_search">Buscar aluno</label>
                <input type="search" id="payment_search" name="search" value="{{ request('search') }}" class="input-field"
                       placeholder="Nome do aluno">
            </div>
            <div>
                <label class="label-field" for="payment_status">Situação</label>
                <select id="payment_status" name="status" class="input-field" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <option value="active" @selected($status === 'active')>Ativas</option>
                    <option value="pending" @selected($status === 'pending')>Aguardando pagamento</option>
                    <option value="expiring" @selected($status === 'expiring')>Vencendo em 7 dias</option>
                    <option value="expired" @selected($status === 'expired')>Vencidas</option>
                </select>
            </div>
            <div class="sm:col-span-3 flex items-end gap-2">
                <button type="submit" class="btn-primary"><i class="fas fa-filter mr-2"></i>Filtrar</button>
                <a href="{{ route('unit.payments.index') }}" class="btn-outline">Limpar</a>
            </div>
        </form>
    </div>

    <div class="card">
        @if ($subscriptions->isEmpty())
            <div class="py-10 text-center">
                <i class="fas fa-credit-card mb-3 text-3xl text-text-secondary"></i>
                <p class="font-medium">Nenhuma assinatura encontrada</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Aluno</th>
                            <th>Plano</th>
                            <th>Situação</th>
                            <th>Valor</th>
                            <th>Vigência</th>
                            <th>Pagamento</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($subscriptions as $subscription)
                            @php($lastPayment = $subscription->payments->sortByDesc('created_at')->first())
                            <tr>
                                <td>
                                    <a href="{{ route('students.show', $subscription->student) }}" class="font-medium text-cyan hover:underline">
                                        {{ $subscription->student?->name ?? 'Aluno' }}
                                    </a>
                                    <p class="text-xs text-text-secondary">{{ $subscription->student?->email ?? '—' }}</p>
                                </td>
                                <td class="text-sm">{{ $subscription->plan_name }}</td>
                                <td>
                                    <span class="badge {{ $subscription->isActive() ? 'badge-success' : ($subscription->isPending() ? 'badge-warning' : 'badge-secondary') }}">
                                        {{ $subscription->status_label }}
                                    </span>
                                </td>
                                <td class="text-sm">{{ $subscription->price_label }}</td>
                                <td class="text-sm">
                                    {{ $subscription->starts_at?->format('d/m/Y') ?? '—' }}
                                    @if ($subscription->ends_at)
                                        <span class="block text-xs text-text-secondary">até {{ $subscription->ends_at->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="text-sm">
                                    @if ($lastPayment)
                                        <span class="badge {{ $lastPayment->status === 'approved' ? 'badge-success' : 'badge-warning' }}">
                                            {{ $lastPayment->status_label }}
                                        </span>
                                        @if ($lastPayment->paid_at)
                                            <span class="block text-xs text-text-secondary">{{ $lastPayment->paid_at->format('d/m/Y H:i') }}</span>
                                        @endif
                                    @else
                                        <span class="badge badge-secondary">Sem pagamento</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if (! $subscription->isActive())
                                        @can('students.edit')
                                            <form method="POST" action="{{ route('unit.subscriptions.confirm', $subscription) }}"
                                                  onsubmit="return confirm('Confirmar manualmente o pagamento e ativar esta assinatura?');">
                                                @csrf
                                                <button type="submit" class="btn-outline" title="Confirmar pagamento manual">
                                                    <i class="fas fa-check mr-1"></i>
                                                    Confirmar
                                                </button>
                                            </form>
                                        @endcan
                                    @else
                                        <a href="{{ route('students.show', $subscription->student) }}" class="btn-outline">
                                            Ver aluno
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 border-t border-custom pt-4">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="card space-y-3">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-receipt text-cyan"></i>
                Últimos pagamentos
            </h2>

            @forelse ($recentPayments as $payment)
                <div class="flex items-center justify-between gap-3 rounded-xl border border-custom bg-bg-secondary p-3">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $payment->subscription?->student?->name ?? 'Aluno' }}</p>
                        <p class="truncate text-xs text-text-secondary">
                            {{ $payment->provider }}
                            @if ($payment->paid_at)
                                &middot; {{ $payment->paid_at->format('d/m/Y H:i') }}
                            @endif
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-medium">
                            {{ $payment->amount !== null ? 'R$ '.number_format((float) $payment->amount, 2, ',', '.') : '—' }}
                        </p>
                        <span class="badge {{ $payment->status === 'approved' ? 'badge-success' : 'badge-warning' }}">
                            {{ $payment->status_label }}
                        </span>
                    </div>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-text-secondary">Nenhum pagamento registrado.</p>
            @endforelse
        </section>

        <section class="card space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-ticket text-cyan"></i>
                    Aulas avulsas pendentes
                </h2>
                <a href="{{ route('unit.classes.index') }}" class="text-sm font-semibold text-cyan hover:underline">Ver turmas</a>
            </div>

            @forelse ($pendingClassBookings as $booking)
                <div class="flex items-center justify-between gap-3 rounded-xl border border-custom bg-bg-secondary p-3">
                    <div class="min-w-0">
                        <p class="truncate font-medium">{{ $booking->student?->name ?? 'Aluno' }}</p>
                        <p class="truncate text-xs text-text-secondary">
                            {{ $booking->schoolClass?->name ?? 'Aula' }}
                            &middot; {{ $booking->booked_for?->format('d/m/Y') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="font-medium">R$ {{ number_format((float) $booking->amount, 2, ',', '.') }}</p>
                        <span class="badge badge-warning">{{ $booking->status_label }}</span>
                    </div>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-text-secondary">Nenhuma aula avulsa pendente.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
