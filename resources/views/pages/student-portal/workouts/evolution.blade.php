@extends('layouts.student')

@section('title', 'Evolução dos treinos')

@section('content')
<div class="space-y-6">
    @include('pages.student-portal.workouts._nav')

    <div class="-mx-2 grid grid-cols-1 gap-4 sm:mx-0 sm:grid-cols-2 lg:grid-cols-4">
        <div class="card">
            <p class="text-sm text-text-secondary">Treinos concluídos</p>
            <p class="mt-1 text-2xl font-bold">{{ $summary['workouts'] }}</p>
            <p class="mt-1 text-xs text-text-secondary">{{ $summary['workouts_last_30'] }} nos últimos 30 dias</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Séries registradas</p>
            <p class="mt-1 text-2xl font-bold">{{ $summary['sets'] }}</p>
            <p class="mt-1 text-xs text-text-secondary">{{ number_format($summary['reps'], 0, ',', '.') }} repetições</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Volume total</p>
            <p class="mt-1 text-2xl font-bold">{{ number_format($summary['volume'], 0, ',', '.') }} kg</p>
            <p class="mt-1 text-xs text-text-secondary">{{ number_format($summary['volume_last_30'], 0, ',', '.') }} kg em 30 dias</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Média por treino</p>
            <p class="mt-1 text-2xl font-bold">
                {{ $summary['workouts'] > 0 ? number_format($summary['volume'] / $summary['workouts'], 0, ',', '.') : 0 }} kg
            </p>
            <p class="mt-1 text-xs text-text-secondary">volume médio</p>
        </div>
    </div>

    @if ($summary['sets'] === 0)
        <div class="card py-12 text-center">
            <i class="fas fa-chart-line mb-4 text-4xl text-text-secondary"></i>
            <p class="text-lg font-medium">Ainda não há dados suficientes</p>
            <p class="mt-1 text-sm text-text-secondary">Conclua um treino registrando as séries para acompanhar sua evolução.</p>
            <a href="{{ route('student.portal.workouts.index', $network) }}" class="btn-primary mt-4">
                <i class="fas fa-dumbbell mr-2"></i>
                Ver meus treinos
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <section class="card card-mobile-tight space-y-4">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-layer-group text-cyan"></i>
                    Exercícios por grupo muscular
                </h2>
                <p class="text-xs text-text-secondary">
                    Percentual de séries × repetições do grupo em relação a todos os treinos. Toque em um grupo para ver a evolução de carga.
                </p>

                @if ($exercisesByMuscle->isEmpty())
                    <p class="text-sm text-text-secondary">Nenhuma série registrada ainda.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($exercisesByMuscle as $item)
                            <button type="button" onclick="openMuscleModal(@js($item['group']))"
                                    class="w-full rounded-xl border border-custom bg-bg-secondary p-2.5 text-left sm:p-3 transition-colors hover:border-cyan">
                                <div class="flex items-center justify-between gap-2 text-sm">
                                    <span class="truncate font-medium">{{ $item['label'] }}</span>
                                    <span class="shrink-0 text-xs text-text-secondary">
                                        {{ $item['percent'] }}%
                                        &middot; {{ $item['sets'] }} séries
                                        &middot; {{ $item['exercises'] }} exercício(s)
                                    </span>
                                </div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-surface-elevated">
                                    <div class="h-full rounded-full"
                                         style="width: {{ max(4, $item['percent']) }}%; background: var(--color-primary);"></div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="card card-mobile-tight space-y-4">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-calendar-week text-cyan"></i>
                    Frequência nas últimas 8 semanas
                </h2>

                <div class="space-y-3">
                    @foreach ($weeklyFrequency as $week)
                        <div>
                            <div class="flex items-center justify-between gap-2 text-sm">
                                <span class="text-text-secondary">Semana de {{ $week['label'] }}</span>
                                <span class="shrink-0 font-medium">
                                    {{ $week['count'] }} treino(s)
                                    &middot; {{ $week['hours_label'] }}
                                </span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-surface-elevated">
                                <div class="h-full rounded-full"
                                     style="width: {{ $week['percent'] }}%; background: var(--color-success);"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="card card-mobile-tight space-y-4">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-weight-hanging text-cyan"></i>
                Principais exercícios
            </h2>

            @if ($topExercises->isEmpty())
                <p class="text-sm text-text-secondary">Nenhum exercício registrado ainda.</p>
            @else
                <div class="space-y-2">
                    @foreach ($topExercises as $exercise)
                        <div class="rounded-xl border border-custom bg-bg-secondary p-2.5 sm:p-3">
                            <div class="flex items-start justify-between gap-2">
                                <p class="min-w-0 truncate text-sm font-semibold">{{ $exercise['name'] }}</p>
                                <span class="badge badge-info whitespace-nowrap">{{ $exercise['sets'] }} série(s)</span>
                            </div>
                            <div class="mt-2 grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-xs text-text-secondary">Carga máxima</p>
                                    <p class="mt-0.5 font-medium">
                                        {{ $exercise['max_weight'] > 0 ? number_format($exercise['max_weight'], 2, ',', '.').' kg' : '—' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-text-secondary">Volume</p>
                                    <p class="mt-0.5 font-medium">{{ number_format($exercise['volume'], 0, ',', '.') }} kg</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div>

@foreach ($exercisesByMuscle as $item)
    @php($detail = $muscleDetails[$item['group']] ?? null)
    @if ($detail)
        <div id="muscleModal-{{ $item['group'] }}" data-muscle-modal="{{ $item['group'] }}"
             class="modal-overlay hidden" role="dialog" aria-modal="true"
             aria-labelledby="muscleModalTitle-{{ $item['group'] }}">
            <div class="modal-content modal-content-lg flex max-h-[85vh] flex-col">
                <div class="mb-4 flex items-start justify-between gap-3">
                    <div>
                        <h2 id="muscleModalTitle-{{ $item['group'] }}" class="text-xl font-bold">{{ $item['label'] }}</h2>
                        <p class="mt-1 text-xs text-text-secondary">
                            {{ $item['percent'] }}% &middot; {{ $item['sets'] }} séries &middot; {{ $item['exercises'] }} exercício(s)
                        </p>
                    </div>
                    <button type="button" onclick="closeMuscleModal(@js($item['group']))"
                            class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                    @foreach ($detail['exercises'] as $exercise)
                        <div class="rounded-xl border border-custom bg-bg-secondary p-2.5 sm:p-3">
                            <div class="flex items-center justify-between gap-2">
                                <p class="min-w-0 truncate font-semibold">{{ $exercise['name'] }}</p>
                                <span class="badge badge-info shrink-0">{{ $exercise['sets'] }} série(s)</span>
                            </div>

                            <div class="mt-2 space-y-1">
                                @foreach ($exercise['evolution'] as $point)
                                    <div class="flex items-center justify-between gap-2 text-xs">
                                        <span class="text-text-secondary">{{ $point['date'] }}</span>
                                        <span class="font-medium">
                                            {{ $point['weight_label'] ?? $point['time_label'] ?? $point['distance_label'] ?? $point['reps_label'] ?? '—' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 flex justify-end border-t border-custom pt-3">
                    <button type="button" onclick="closeMuscleModal(@js($item['group']))" class="btn-outline">Fechar</button>
                </div>
            </div>
        </div>
    @endif
@endforeach

<script>
    (function () {
        window.openMuscleModal = function (group) {
            const modal = document.getElementById('muscleModal-' + group);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeMuscleModal = function (group) {
            const modal = document.getElementById('muscleModal-' + group);

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        document.querySelectorAll('[data-muscle-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeMuscleModal(modal.dataset.muscleModal);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-muscle-modal]:not(.hidden)').forEach(function (modal) {
                closeMuscleModal(modal.dataset.muscleModal);
            });
        });
    })();
</script>
@endsection
