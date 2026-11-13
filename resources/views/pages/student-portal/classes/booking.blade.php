@extends('layouts.student')

@section('title', 'Reserva de aula')

@section('content')
@php($confirmed = $booking->isConfirmed())

<div class="mx-auto w-full max-w-2xl space-y-6">
    <div class="card space-y-5 text-center">
        @if ($confirmed)
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full"
                 style="background: var(--color-success-soft); color: var(--color-success);">
                <i class="fas fa-circle-check text-3xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Pagamento aprovado</p>
                <h1 class="mt-2 text-2xl font-bold">Aula reservada!</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    Sua vaga está confirmada. Apresente-se na unidade no dia e horário da aula.
                </p>
            </div>
        @elseif ($booking->status === \App\Models\ClassBooking::STATUS_PENDING)
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full"
                 style="background: var(--color-warning-soft); color: var(--color-warning);">
                <i class="fas fa-clock text-3xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Aguardando confirmação</p>
                <h1 class="mt-2 text-2xl font-bold">Estamos confirmando seu pagamento</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    Assim que o Mercado Pago confirmar, sua vaga será reservada automaticamente.
                </p>
            </div>
        @else
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full"
                 style="background: var(--color-danger-soft); color: var(--color-danger);">
                <i class="fas fa-circle-xmark text-3xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Pagamento não concluído</p>
                <h1 class="mt-2 text-2xl font-bold">Não foi possível reservar a aula</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    A vaga não foi confirmada. Você pode tentar novamente pela agenda de turmas.
                </p>
            </div>
        @endif
    </div>

    <div class="card">
        <h2 class="mb-4 flex items-center gap-2 font-semibold">
            <i class="fas fa-calendar-check text-cyan"></i>
            Resumo da reserva
        </h2>

        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs text-text-secondary">Aula</dt>
                <dd class="mt-1 font-medium">{{ $booking->schoolClass?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-text-secondary">Data</dt>
                <dd class="mt-1 font-medium">{{ $booking->booked_for?->format('d/m/Y') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-text-secondary">Unidade</dt>
                <dd class="mt-1 font-medium">{{ $booking->unit?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-text-secondary">Valor</dt>
                <dd class="mt-1 font-medium">
                    {{ $booking->amount !== null ? 'R$ '.number_format((float) $booking->amount, 2, ',', '.') : '—' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs text-text-secondary">Situação</dt>
                <dd class="mt-1">
                    <span class="badge {{ $confirmed ? 'badge-success' : ($booking->status === 'pending' ? 'badge-warning' : 'badge-secondary') }}">
                        {{ $booking->status_label }}
                    </span>
                </dd>
            </div>
            @if ($paymentId)
                <div>
                    <dt class="text-xs text-text-secondary">Pagamento</dt>
                    <dd class="mt-1 font-mono text-sm">{{ $paymentId }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
        <a href="{{ route('student.portal.classes.index', $network) }}" class="btn-outline">
            <i class="fas fa-arrow-left mr-2"></i>
            Voltar para turmas
        </a>
        <a href="{{ route('student.portal.home', $network) }}" class="btn-primary">
            <i class="fas fa-house mr-2"></i>
            Ir para a home
        </a>
    </div>
</div>

@if (! $confirmed && $booking->status === 'pending' && $paymentId)
    <script>
        setTimeout(function () {
            window.location.reload();
        }, 5000);
    </script>
@endif
@endsection
