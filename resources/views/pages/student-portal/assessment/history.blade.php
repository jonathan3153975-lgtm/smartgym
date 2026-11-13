@extends('layouts.student')

@section('title', 'Histórico de avaliações')

@section('content')
<div class="mx-auto w-full max-w-4xl space-y-6">
    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('student.portal.assessment.show', $network) }}" class="text-sm text-text-secondary hover:text-cyan">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Voltar para a avaliação atual
                </a>
                <h1 class="mt-1 text-2xl font-bold">Histórico de avaliações</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    Compare sua evolução ao longo do tempo.
                </p>
            </div>

            <a href="{{ route('student.portal.assessment.create', $network) }}" class="btn-primary self-start sm:self-auto">
                <i class="fas fa-plus mr-2"></i>
                Nova reavaliação
            </a>
        </div>
    </div>

    @if ($assessments->isEmpty())
        <div class="card py-12 text-center">
            <i class="fas fa-clipboard-check mb-4 text-4xl text-text-secondary"></i>
            <p class="text-lg font-medium">Nenhuma avaliação registrada</p>
            <p class="mt-1 text-sm text-text-secondary">Preencha sua primeira ficha de avaliação.</p>
            <a href="{{ route('student.portal.assessment.show', $network) }}" class="btn-primary mt-4">
                Preencher ficha
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($assessments as $historyAssessment)
                @php
                    $previousAssessment = $assessments->get($loop->index + 1);
                    $historyDelta = $previousAssessment && $historyAssessment->weight_kg !== null && $previousAssessment->weight_kg !== null
                        ? (float) $historyAssessment->weight_kg - (float) $previousAssessment->weight_kg
                        : null;
                @endphp
                <a href="{{ route('student.portal.assessment.show-one', [$network, $historyAssessment]) }}"
                   class="card block transition-colors hover:border-cyan">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-xs text-text-secondary">
                                    {{ $historyAssessment->submitted_at?->format('d/m/Y H:i') ?? '—' }}
                                </p>
                                @if ($loop->first)
                                    <span class="badge badge-success">Atual</span>
                                @endif
                                @if ($historyAssessment->parq_positive)
                                    <span class="badge badge-warning">PAR-Q com alerta</span>
                                @endif
                            </div>
                            <h2 class="mt-1 text-lg font-semibold">{{ $historyAssessment->goal_label }}</h2>
                            <p class="mt-1 text-sm text-text-secondary">
                                {{ $historyAssessment->experience_level_label }}
                                &middot; {{ $historyAssessment->training_frequency }}x por semana
                                @if ($historyAssessment->weight_kg !== null)
                                    &middot; {{ $historyAssessment->weight_kg }} kg
                                @endif
                                @if ($historyAssessment->bmi !== null)
                                    &middot; IMC {{ number_format($historyAssessment->bmi, 1, ',', '.') }}
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            @if ($historyDelta !== null)
                                <div class="text-right">
                                    <p class="text-xs text-text-secondary">Variação</p>
                                    <p class="font-semibold {{ $historyDelta > 0 ? 'text-red' : 'text-green' }}">
                                        {{ $historyDelta > 0 ? '+' : '' }}{{ number_format($historyDelta, 2, ',', '.') }} kg
                                    </p>
                                </div>
                            @endif
                            <i class="fas fa-chevron-right text-text-secondary"></i>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection
