@extends('layouts.student')

@section('title', 'Histórico de avaliações')

@section('content')
<div class="mx-auto w-full max-w-4xl space-y-5">
    <div class="flex items-center justify-between gap-2">
        <h1 class="text-2xl font-bold">Histórico de avaliações</h1>

        <div class="flex items-center gap-2">
            <a href="{{ route('student.portal.assessment.show', $network) }}" class="btn-outline p-2"
               title="Avaliação atual" aria-label="Avaliação atual">
                <i class="fas fa-eye"></i>
            </a>
            <a href="{{ route('student.portal.assessment.create', $network) }}" class="btn-primary p-2"
               title="Nova avaliação" aria-label="Nova avaliação">
                <i class="fas fa-plus"></i>
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
        <div class="-mx-2 space-y-3 sm:mx-0">
            @foreach ($assessments as $historyAssessment)
                @php
                    $previousAssessment = $assessments->get($loop->index + 1);
                    $historyDelta = $previousAssessment && $historyAssessment->weight_kg !== null && $previousAssessment->weight_kg !== null
                        ? (float) $historyAssessment->weight_kg - (float) $previousAssessment->weight_kg
                        : null;
                @endphp

                <div class="card card-mobile-tight">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
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

                        <div class="ml-auto flex shrink-0 items-center gap-2">
                            @if ($historyDelta !== null && $previousAssessment)
                                <button type="button"
                                        onclick="openAssessmentComparison({{ $historyAssessment->id }})"
                                        class="rounded-xl border border-custom bg-bg-secondary px-3 py-2 text-right transition-colors hover:border-cyan">
                                    <span class="block text-xs text-text-secondary">Variação</span>
                                    <span class="block font-semibold {{ $historyDelta > 0 ? 'text-red' : 'text-green' }}">
                                        {{ $historyDelta > 0 ? '+' : '' }}{{ number_format($historyDelta, 2, ',', '.') }} kg
                                    </span>
                                </button>
                            @endif

                            <a href="{{ route('student.portal.assessment.show-one', [$network, $historyAssessment]) }}"
                               class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-cyan"
                               title="Ver ficha completa" aria-label="Ver ficha completa">
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                @if ($previousAssessment)
                    <div id="assessmentComparisonModal-{{ $historyAssessment->id }}"
                         data-assessment-modal="{{ $historyAssessment->id }}"
                         class="modal-overlay hidden" role="dialog" aria-modal="true"
                         aria-labelledby="assessmentComparisonTitle-{{ $historyAssessment->id }}">
                        <div class="modal-content modal-content-lg flex max-h-[85vh] flex-col">
                            <div class="mb-4 flex items-start justify-between gap-3">
                                <div>
                                    <h2 id="assessmentComparisonTitle-{{ $historyAssessment->id }}" class="text-xl font-bold">
                                        Comparativo de avaliações
                                    </h2>
                                    <p class="mt-1 text-xs text-text-secondary">
                                        {{ $previousAssessment->submitted_at?->format('d/m/Y') ?? '—' }}
                                        &rarr; {{ $historyAssessment->submitted_at?->format('d/m/Y') ?? '—' }}
                                    </p>
                                </div>
                                <button type="button" onclick="closeAssessmentComparison({{ $historyAssessment->id }})"
                                        class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="min-h-0 flex-1 overflow-y-auto">
                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    @foreach (\App\Support\AssessmentMetrics::for($historyAssessment, $previousAssessment) as $metric)
                                        <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                                            <p class="text-xs text-text-secondary">{{ $metric['label'] }}</p>
                                            <p class="mt-1 font-semibold">{{ $metric['value'] }}</p>
                                            @if ($metric['percent_label'])
                                                <p class="mt-0.5 text-xs text-text-secondary">
                                                    @if ($metric['direction'] === 'up')
                                                        <i class="fas fa-arrow-up mr-1"></i>
                                                    @elseif ($metric['direction'] === 'down')
                                                        <i class="fas fa-arrow-down mr-1"></i>
                                                    @endif
                                                    {{ $metric['percent_label'] }}
                                                </p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-3 flex justify-end border-t border-custom pt-3">
                                <button type="button" onclick="closeAssessmentComparison({{ $historyAssessment->id }})"
                                        class="btn-outline">Fechar</button>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>

<script>
    (function () {
        window.openAssessmentComparison = function (id) {
            const modal = document.getElementById('assessmentComparisonModal-' + id);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeAssessmentComparison = function (id) {
            const modal = document.getElementById('assessmentComparisonModal-' + id);

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        document.querySelectorAll('[data-assessment-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeAssessmentComparison(modal.dataset.assessmentModal);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-assessment-modal]:not(.hidden)').forEach(function (modal) {
                closeAssessmentComparison(modal.dataset.assessmentModal);
            });
        });
    })();
</script>
@endsection
