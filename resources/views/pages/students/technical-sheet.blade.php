@extends('layouts.app')

@section('title', 'Ficha técnica - '.$student->name)

@section('content')
@php
    $currentAssessment = $bodySummary['current'];
    $previousAssessment = $bodySummary['previous'];
@endphp

<div class="container mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('students.show', $student) }}" class="text-sm text-text-secondary transition-colors hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para o aluno
            </a>
            <h1 class="mt-1 text-2xl font-bold">Ficha técnica</h1>
            <p class="mt-1 text-text-secondary">{{ $student->name }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('students.assessment', $student) }}" class="btn-outline">
                <i class="fas fa-clipboard-check mr-2"></i>
                Avaliação
            </a>
        </div>
    </div>

    @include('pages.students._tabs', ['student' => $student, 'activeTab' => 'technical'])

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="card space-y-4">
            <div class="flex flex-col gap-1">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-layer-group text-cyan"></i>
                    Grupos musculares treinados
                </h2>
                <p class="text-xs text-text-secondary">Clique em um grupo para ver os exercícios e a carga máxima.</p>
            </div>

            @if ($muscleGroups === [])
                <p class="text-sm text-text-secondary">Sem treinos concluídos ainda.</p>
            @else
                <div class="space-y-3">
                    @foreach ($muscleGroups as $item)
                        <button type="button" onclick="openTechnicalMuscleGroupModal(@js($item['group']))"
                                class="w-full rounded-xl border border-custom bg-bg-secondary p-2.5 text-left transition-colors hover:border-cyan">
                            <div class="flex items-center justify-between gap-2 text-sm">
                                <span class="font-medium">{{ $item['label'] }}</span>
                                <span class="text-text-secondary">{{ $item['percent'] }}%</span>
                            </div>
                            <div class="progress mt-1 h-2">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ max(4, $item['percent']) }}%;"
                                     aria-valuenow="{{ $item['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <p class="mt-1 text-xs text-text-secondary">
                                {{ $item['sets'] }} série(s) &middot; {{ number_format($item['volume'], 0, ',', '.') }} kg
                            </p>
                        </button>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card space-y-4">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-calendar-week text-cyan"></i>
                Frequência nas últimas 8 semanas
            </h2>

            <div class="space-y-3">
                @foreach ($weeklyFrequency as $week)
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-text-secondary">Semana de {{ $week['label'] }}</span>
                            <span class="font-medium">{{ $week['count'] }} treino(s)</span>
                        </div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-surface-elevated">
                            <div class="h-full rounded-full" style="width: {{ $week['percent'] }}%; background: var(--color-success);"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="card space-y-4">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-weight-hanging text-cyan"></i>
                Exercícios mais executados
            </h2>

            @if ($topExercises === [])
                <p class="text-sm text-text-secondary">Sem dados de execução ainda.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Exercício</th>
                                <th>Séries</th>
                                <th>Carga máx.</th>
                                <th>Volume</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topExercises as $exercise)
                                <tr>
                                    <td class="font-medium">{{ $exercise['name'] }}</td>
                                    <td>{{ $exercise['sets'] }}</td>
                                    <td>{{ $exercise['max_weight'] > 0 ? number_format($exercise['max_weight'], 2, ',', '.').' kg' : '—' }}</td>
                                    <td>{{ number_format($exercise['volume'], 0, ',', '.') }} kg</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="card space-y-4">
            <div class="flex flex-col gap-1">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-weight-scale text-cyan"></i>
                    Acompanhamento de medidas
                </h2>
                <p class="text-xs text-text-secondary">
                    @if ($currentAssessment?->submitted_at)
                        Última avaliação em {{ $currentAssessment->submitted_at->format('d/m/Y') }}.
                    @else
                        Nenhuma avaliação registrada.
                    @endif
                    @if ($previousAssessment)
                        Comparativos em relação à avaliação anterior.
                    @endif
                </p>
            </div>

            @if ($assessmentMetrics !== [])
                <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                    <div class="divide-y divide-custom">
                        @foreach ($assessmentMetrics as $metric)
                            <div class="flex items-center justify-between gap-3 py-2">
                                <span class="text-sm text-text-secondary">{{ $metric['label'] }}</span>
                                <div class="min-w-0 text-right">
                                    <p class="font-semibold">{{ $metric['value'] }}</p>
                                    @if ($metric['percent_label'])
                                        <p class="text-xs text-text-secondary">
                                            @if ($metric['direction'] === 'up')
                                                <i class="fas fa-arrow-up mr-1"></i>
                                            @elseif ($metric['direction'] === 'down')
                                                <i class="fas fa-arrow-down mr-1"></i>
                                            @endif
                                            {{ $metric['percent_label'] }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($measurements->isEmpty())
                <p class="text-sm text-text-secondary">Nenhuma medida corporal registrada.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Peso</th>
                                <th>Gordura</th>
                                <th>Cintura</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($measurements as $measurement)
                                <tr>
                                    <td class="font-medium">{{ $measurement->measured_at->format('d/m/Y') }}</td>
                                    <td>{{ $measurement->weight_label }}</td>
                                    <td>{{ $measurement->body_fat_percent ? $measurement->body_fat_percent.'%' : '—' }}</td>
                                    <td>{{ $measurement->waist_cm ? $measurement->waist_cm.' cm' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($currentAssessment?->isCompleted())
                <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                    <p class="text-xs text-text-secondary">Objetivo da avaliação</p>
                    <p class="mt-1 font-medium">{{ $currentAssessment->goal_label }}</p>
                    <p class="text-xs text-text-secondary">
                        {{ $currentAssessment->experience_level_label }}
                        &middot; {{ $currentAssessment->training_frequency }}x por semana
                        @if ($currentAssessment->parq_positive)
                            &middot; <span class="text-yellow">PAR-Q com alerta</span>
                        @endif
                    </p>
                </div>
            @else
                <p class="text-sm text-text-secondary">O aluno ainda não preencheu a avaliação.</p>
            @endif
        </section>
    </div>
</div>

@foreach ($muscleGroups as $group)
    <div id="technicalMuscleGroupModal-{{ $group['group'] }}" data-technical-muscle-group-modal="{{ $group['group'] }}"
         class="modal-overlay hidden" role="dialog" aria-modal="true"
         aria-labelledby="technicalMuscleGroupTitle-{{ $group['group'] }}">
        <div class="modal-content flex max-h-[85vh] flex-col">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="technicalMuscleGroupTitle-{{ $group['group'] }}" class="text-xl font-bold">{{ $group['label'] }}</h2>
                    <p class="mt-1 text-xs text-text-secondary">{{ $group['percent'] }}% das repetições registradas</p>
                </div>
                <button type="button" onclick="closeTechnicalMuscleGroupModal(@js($group['group']))"
                        class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                @forelse ($muscleGroupExercises[$group['group']] ?? [] as $exercise)
                    <div class="rounded-xl border border-custom bg-bg-secondary p-2.5">
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="truncate font-medium">{{ $exercise['name'] }}</span>
                            <span class="shrink-0 text-text-secondary">{{ $exercise['max_weight_label'] }} &middot; {{ $exercise['percent'] }}%</span>
                        </div>
                        <div class="progress mt-1 h-2">
                            <div class="progress-bar" role="progressbar"
                                 style="width: {{ max(4, $exercise['percent']) }}%;"
                                 aria-valuenow="{{ $exercise['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-text-secondary">Nenhum exercício registrado para este grupo.</p>
                @endforelse
            </div>

            <div class="mt-3 flex justify-end border-t border-custom pt-3">
                <button type="button" onclick="closeTechnicalMuscleGroupModal(@js($group['group']))" class="btn-outline">Fechar</button>
            </div>
        </div>
    </div>
@endforeach

<script>
    (function () {
        window.openTechnicalMuscleGroupModal = function (group) {
            const modal = document.getElementById('technicalMuscleGroupModal-' + group);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeTechnicalMuscleGroupModal = function (group) {
            const modal = document.getElementById('technicalMuscleGroupModal-' + group);

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        document.querySelectorAll('[data-technical-muscle-group-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeTechnicalMuscleGroupModal(modal.dataset.technicalMuscleGroupModal);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-technical-muscle-group-modal]:not(.hidden)').forEach(function (modal) {
                closeTechnicalMuscleGroupModal(modal.dataset.technicalMuscleGroupModal);
            });
        });
    })();
</script>
@endsection
