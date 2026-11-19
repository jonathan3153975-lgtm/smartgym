@extends('layouts.student')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-5">
    <div class="flex flex-col gap-1">
        <h1 class="text-2xl font-bold">Dashboard</h1>
        <p class="text-sm text-text-secondary">Frequência, grupos musculares e avaliação dos últimos 30 dias.</p>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:items-stretch">
        <section class="card flex min-h-[22rem] flex-col">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-lg text-white shadow-lg"
                     style="background: linear-gradient(135deg, var(--color-primary), var(--color-info));">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div>
                    <h2 class="font-semibold">Frequência</h2>
                    <p class="text-xs text-text-secondary">Últimos 30 dias</p>
                </div>
            </div>

            <div class="flex flex-1 flex-col items-center justify-center gap-4 py-4">
                <div class="relative h-44 w-44 rounded-full"
                     style="background: conic-gradient(var(--color-primary) {{ $frequencyPercent }}%, var(--color-border) 0);">
                    <div class="absolute inset-[14%] flex flex-col items-center justify-center rounded-full bg-surface">
                        <p class="text-3xl font-bold">{{ $frequencyPercent }}%</p>
                        <p class="text-[0.65rem] uppercase tracking-wider text-text-secondary">frequência</p>
                    </div>
                </div>

                <p class="text-center text-sm text-text-secondary">
                    {{ $actualDays }} de {{ $expectedDays }} dias previstos
                </p>
            </div>
        </section>

        <section class="card flex min-h-[22rem] flex-col lg:h-[22rem]">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-lg text-white shadow-lg"
                     style="background: linear-gradient(135deg, var(--color-success), var(--color-info));">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div>
                    <h2 class="font-semibold">Grupos musculares</h2>
                    <p class="text-xs text-text-secondary">Mais trabalhados nos últimos 30 dias</p>
                </div>
            </div>

            @if ($muscleGroups->isEmpty())
                <p class="mt-4 text-sm text-text-secondary">Nenhuma série registrada no período.</p>
            @else
                <div class="mt-4 min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                    @foreach ($muscleGroups as $group)
                        <button type="button" onclick="openMuscleGroupModal(@js($group['group']))"
                                class="interactive-item w-full cursor-pointer rounded-xl border border-custom bg-bg-secondary p-2.5 text-left transition-colors hover:border-cyan">
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span class="truncate font-medium">{{ $group['label'] }}</span>
                                <span class="shrink-0 text-text-secondary">{{ $group['percent'] }}%</span>
                            </div>
                            <div class="progress mt-1 h-2">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ max(4, $group['percent']) }}%;"
                                     aria-valuenow="{{ $group['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    @foreach ($muscleGroups as $group)
        <div id="muscleGroupModal-{{ $group['group'] }}" data-muscle-group-modal="{{ $group['group'] }}"
             class="modal-overlay hidden" role="dialog" aria-modal="true"
             aria-labelledby="muscleGroupModalTitle-{{ $group['group'] }}">
            <div class="modal-content flex max-h-[85vh] flex-col">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 id="muscleGroupModalTitle-{{ $group['group'] }}" class="text-xl font-bold">{{ $group['label'] }}</h2>
                        <p class="mt-1 text-xs text-text-secondary">{{ $group['percent'] }}% do total de repetições</p>
                    </div>
                    <button type="button" onclick="closeMuscleGroupModal(@js($group['group']))"
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
                        <p class="text-sm text-text-secondary">Nenhum exercício registrado para este grupo no período.</p>
                    @endforelse
                </div>

                <div class="mt-3 flex justify-end border-t border-custom pt-3">
                    <button type="button" onclick="closeMuscleGroupModal(@js($group['group']))" class="btn-outline">Fechar</button>
                </div>
            </div>
        </div>
    @endforeach

    @if ($classFrequency)
        <section class="card space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-lg text-white shadow-lg"
                     style="background: linear-gradient(135deg, var(--color-accent), var(--color-primary));">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <h2 class="font-semibold">Frequência de aulas</h2>
                    <p class="text-xs text-text-secondary">Últimos 30 dias &middot; {{ $classFrequency['attended'] }} de {{ $classFrequency['expected'] }} aulas previstas</p>
                </div>
                <span class="ml-auto text-lg font-bold text-cyan">{{ $classFrequency['percent'] }}%</span>
            </div>

            <div class="progress h-2.5">
                <div class="progress-bar" role="progressbar"
                     style="width: {{ max(4, $classFrequency['percent']) }}%;"
                     aria-valuenow="{{ $classFrequency['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
            </div>

            @if ($classModalities->isNotEmpty())
                <div class="space-y-2">
                    @foreach ($classModalities as $modality)
                        <div class="rounded-xl border border-custom bg-bg-secondary p-2.5">
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span class="truncate font-medium">{{ $modality['name'] }}</span>
                                <span class="shrink-0 text-text-secondary">{{ $modality['count'] }} aula(s) &middot; {{ $modality['percent'] }}%</span>
                            </div>
                            <div class="progress mt-1 h-1.5">
                                <div class="progress-bar" role="progressbar"
                                     style="width: {{ max(4, $modality['percent']) }}%;"
                                     aria-valuenow="{{ $modality['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <section class="card space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-lg text-white shadow-lg"
                     style="background: linear-gradient(135deg, var(--color-warning), var(--color-accent));">
                    <i class="fas fa-clipboard-check"></i>
                </div>
                <div>
                    <h2 class="font-semibold">Ficha de avaliação</h2>
                    @if ($currentAssessment?->submitted_at)
                        <p class="text-xs text-text-secondary">Última atualização em {{ $currentAssessment->submitted_at->format('d/m/Y') }}</p>
                    @else
                        <p class="text-xs text-text-secondary">Nenhuma avaliação registrada</p>
                    @endif
                </div>
            </div>

            <a href="{{ route('student.portal.assessment.show', $network) }}" class="btn-outline self-end sm:self-auto">
                <i class="fas fa-arrow-right mr-2"></i>
                Ver ficha
            </a>
        </div>

        @if ($currentAssessment)
            <div class="divide-y divide-custom rounded-xl border border-custom bg-bg-secondary">
                @foreach ($assessmentDeltas as $field)
                    <div class="flex items-center justify-between gap-3 px-3 py-2.5">
                        <span class="font-semibold text-[19pt] text-text-secondary">{{ $field['label'] }}</span>
                        <div class="min-w-0 text-right">
                            <span class="font-semibold">{{ $field['value'] }}</span>
                            @if ($field['percent_label'])
                                <span class="block text-xs text-text-secondary">
                                    @if ($field['direction'] === 'up')
                                        <i class="fas fa-arrow-up mr-1"></i>
                                    @elseif ($field['direction'] === 'down')
                                        <i class="fas fa-arrow-down mr-1"></i>
                                    @endif
                                    {{ $field['percent_label'] }}
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-text-secondary">
                Preencha sua ficha de avaliação para acompanhar a evolução das suas medidas.
            </p>
            <a href="{{ route('student.portal.assessment.create', $network) }}" class="btn-primary justify-center sm:w-auto">
                <i class="fas fa-plus mr-2"></i>
                Preencher ficha
            </a>
        @endif
    </section>
</div>

<script>
    (function () {
        window.openMuscleGroupModal = function (group) {
            const modal = document.getElementById('muscleGroupModal-' + group);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeMuscleGroupModal = function (group) {
            const modal = document.getElementById('muscleGroupModal-' + group);

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        document.querySelectorAll('[data-muscle-group-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeMuscleGroupModal(modal.dataset.muscleGroupModal);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-muscle-group-modal]:not(.hidden)').forEach(function (modal) {
                closeMuscleGroupModal(modal.dataset.muscleGroupModal);
            });
        });
    })();
</script>
@endsection
