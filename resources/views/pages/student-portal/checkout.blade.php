@extends('layouts.student')

@section('title', 'Assinatura')

@section('content')
@php
    $isActive = $subscription->isActive();
    $isPending = $subscription->isPending();
@endphp

<div class="mx-auto w-full max-w-3xl space-y-6">
    <div class="card space-y-5 text-center">
        @if ($isActive)
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full"
                 style="background: var(--color-success-soft); color: var(--color-success);">
                <i class="fas fa-circle-check text-3xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Pagamento aprovado</p>
                <h1 class="mt-2 text-2xl font-bold">Seu plano está ativo!</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    A assinatura foi confirmada e já está disponível no portal.
                </p>
            </div>
        @elseif ($isPending)
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full"
                 style="background: var(--color-warning-soft); color: var(--color-warning);">
                <i class="fas fa-clock text-3xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Aguardando confirmação</p>
                <h1 class="mt-2 text-2xl font-bold">Estamos confirmando seu pagamento</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    Assim que o Mercado Pago confirmar, seu plano será ativado automaticamente.
                    Esta página é atualizada sozinha enquanto isso.
                </p>
            </div>
        @else
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full"
                 style="background: var(--color-danger-soft); color: var(--color-danger);">
                <i class="fas fa-circle-xmark text-3xl"></i>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Pagamento não concluído</p>
                <h1 class="mt-2 text-2xl font-bold">Não foi possível concluir a assinatura</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    Seu plano não foi ativado. Você pode tentar novamente escolhendo o plano.
                </p>
            </div>
        @endif
    </div>

    <div class="card">
        <h2 class="mb-4 flex items-center gap-2 font-semibold">
            <i class="fas fa-layer-group text-cyan"></i>
            Resumo da assinatura
        </h2>

        <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <dt class="text-xs text-text-secondary">Plano</dt>
                <dd class="mt-1 font-medium">{{ $subscription->plan_name }}</dd>
            </div>
            <div>
                <dt class="text-xs text-text-secondary">Valor</dt>
                <dd class="mt-1 font-medium">{{ $subscription->price_label }}</dd>
            </div>
            <div>
                <dt class="text-xs text-text-secondary">Periodicidade</dt>
                <dd class="mt-1 font-medium">{{ $subscription->period_label }}</dd>
            </div>
            <div>
                <dt class="text-xs text-text-secondary">Situação</dt>
                <dd class="mt-1">
                    <span class="badge {{ $isActive ? 'badge-success' : ($isPending ? 'badge-warning' : 'badge-secondary') }}">
                        {{ $subscription->status_label }}
                    </span>
                </dd>
            </div>
            @if ($subscription->starts_at)
                <div>
                    <dt class="text-xs text-text-secondary">Início</dt>
                    <dd class="mt-1 font-medium">{{ $subscription->starts_at->format('d/m/Y') }}</dd>
                </div>
            @endif
            @if ($subscription->ends_at)
                <div>
                    <dt class="text-xs text-text-secondary">Válido até</dt>
                    <dd class="mt-1 font-medium">{{ $subscription->ends_at->format('d/m/Y') }}</dd>
                </div>
            @endif
            @if ($subscription->unit)
                <div>
                    <dt class="text-xs text-text-secondary">Unidade</dt>
                    <dd class="mt-1 font-medium">{{ $subscription->unit->name }}</dd>
                </div>
            @endif
            @if ($paymentId)
                <div>
                    <dt class="text-xs text-text-secondary">Pagamento</dt>
                    <dd class="mt-1 font-mono text-sm">{{ $paymentId }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:justify-center">
        @if ($isActive)
            @php($assessmentPending = ! $account->student?->assessment?->isCompleted())

            @if ($assessmentPending)
                <a href="{{ route('student.portal.assessment.show', $network) }}" class="btn-primary">
                    <i class="fas fa-clipboard-check mr-2"></i>
                    Preencher ficha de avaliação
                </a>
            @endif

            <a href="{{ route('student.portal.home', $network) }}" class="{{ $assessmentPending ? 'btn-outline' : 'btn-primary' }}">
                <i class="fas fa-house mr-2"></i>
                Ir para a home
            </a>

            @unless ($assessmentPending)
                <a href="{{ route('student.portal.assessment.show', $network) }}" class="btn-outline">
                    <i class="fas fa-eye mr-2"></i>
                    Ver avaliação
                </a>
            @endunless
        @else
            <a href="{{ route('student.portal.plans', $network) }}" class="btn-outline">
                <i class="fas fa-arrow-left mr-2"></i>
                Voltar aos planos
            </a>
        @endif
    </div>
</div>

@if ($isPending && $paymentId)
    <script>
        setTimeout(function () {
            window.location.reload();
        }, 5000);
    </script>
@endif
@endsection
