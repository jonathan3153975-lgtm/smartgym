@extends('layouts.app')

@section('title', 'Treinos - '.$student->name)

@section('content')
<div class="container mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('students.show', $student) }}" class="text-sm text-text-secondary transition-colors hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para o aluno
            </a>
            <h1 class="mt-1 text-2xl font-bold">Treinos</h1>
            <p class="mt-1 text-text-secondary">{{ $student->name }}</p>
        </div>

        <div class="flex flex-wrap gap-2">
            @can('workouts.manage')
                <button type="button" onclick="openApplyWorkoutModal()" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>
                    Aplicar treino
                </button>
            @endcan
            @if ($activePlan)
                @can('workouts.manage')
                    <a href="{{ route('admin.workout-plans.builder', $activePlan) }}" class="btn-outline">
                        <i class="fas fa-list-check mr-2"></i>
                        Editar treino atual
                    </a>
                @endcan
            @endif
        </div>
    </div>

    @include('pages.students._tabs', ['student' => $student, 'activeTab' => 'workouts'])

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="card">
            <p class="text-sm text-text-secondary">Treinos concluídos</p>
            <p class="mt-1 text-2xl font-bold">{{ $summary['workouts'] }}</p>
            <p class="mt-1 text-xs text-text-secondary">{{ $summary['last_30'] }} nos últimos 30 dias</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Séries</p>
            <p class="mt-1 text-2xl font-bold">{{ $summary['sets'] }}</p>
            <p class="mt-1 text-xs text-text-secondary">{{ number_format($summary['reps'], 0, ',', '.') }} repetições</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Volume total</p>
            <p class="mt-1 text-2xl font-bold">{{ number_format($summary['volume'], 0, ',', '.') }} kg</p>
            <p class="mt-1 text-xs text-text-secondary">reps × carga</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Último treino</p>
            <p class="mt-1 text-2xl font-bold">{{ $summary['last_workout']?->format('d/m') ?? '—' }}</p>
            <p class="mt-1 text-xs text-text-secondary">{{ $summary['last_workout']?->format('H:i') ?? 'Sem registro' }}</p>
        </div>
        <div class="card">
            <p class="text-sm text-text-secondary">Plano atual</p>
            <p class="mt-1 truncate text-lg font-bold">{{ $activePlan?->name ?? 'Nenhum' }}</p>
            <p class="mt-1 text-xs text-text-secondary">{{ $activePlan?->sessions_count ?? 0 }} sessão(ões)</p>
        </div>
    </div>

    <section class="card space-y-4">
        <div class="flex flex-col gap-1">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-clipboard-list text-cyan"></i>
                Treinos aplicados
            </h2>
            <p class="text-xs text-text-secondary">Histórico de treinos vinculados a este aluno.</p>
        </div>

        @if ($appliedPlans->isEmpty())
            <p class="text-sm text-text-secondary">Nenhum treino aplicado ainda.</p>
        @else
            <div class="space-y-2">
                @foreach ($appliedPlans as $plan)
                    <div class="flex flex-col gap-2 rounded-xl border border-custom bg-bg-secondary p-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($plan->isActive())
                                    <span class="badge badge-success">Ativo</span>
                                @else
                                    <span class="badge badge-secondary">Inativo</span>
                                @endif
                                <p class="truncate font-medium">{{ $plan->name }}</p>
                            </div>
                            <p class="mt-1 text-xs text-text-secondary">
                                {{ $plan->objective ?: 'Sem objetivo informado' }}
                                &middot; {{ $plan->sessions_count }} sessão(ões)
                                &middot; aplicado em {{ $plan->created_at?->format('d/m/Y') ?? '—' }}
                            </p>
                            @if ($plan->goal_labels !== [])
                                <div class="mt-1 flex flex-wrap gap-1">
                                    @foreach ($plan->goal_labels as $goal)
                                        <span class="badge badge-info">{{ $goal }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @can('workouts.manage')
                            <a href="{{ route('admin.workout-plans.builder', $plan) }}" class="btn-outline self-start sm:self-auto">
                                <i class="fas fa-pen mr-2"></i>
                                Editar
                            </a>
                        @endcan
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card space-y-4">
        <div class="flex flex-col gap-1">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-clock-rotate-left text-cyan"></i>
                Treinos concluídos
            </h2>
            <p class="text-xs text-text-secondary">Últimos treinos executados pelo aluno.</p>
        </div>

        @if ($recentLogs->isEmpty())
            <p class="text-sm text-text-secondary">Nenhum treino concluído ainda.</p>
        @else
            <div class="max-h-[32rem] overflow-auto">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Treino</th>
                            <th>Duração</th>
                            <th class="text-right">Séries</th>
                            <th class="text-right">Volume</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentLogs as $log)
                            <tr>
                                <td class="font-medium">{{ $log->started_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td>{{ $log->session_name }}</td>
                                <td>{{ $log->duration_label }}</td>
                                <td class="text-right">{{ $log->sets->count() }}</td>
                                <td class="text-right">{{ number_format($log->total_volume, 0, ',', '.') }} kg</td>
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
                <i class="fas fa-layer-group text-cyan"></i>
                Grupos musculares
            </h2>
            <p class="text-xs text-text-secondary">Clique em um grupo para ver os exercícios, a carga máxima e a importância no treino.</p>
        </div>

        @if ($muscleGroups === [])
            <p class="text-sm text-text-secondary">Sem séries registradas ainda.</p>
        @else
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($muscleGroups as $group)
                    <button type="button" onclick="openWorkoutMuscleGroupModal(@js($group['group']))"
                            class="w-full rounded-xl border border-custom bg-bg-secondary p-2.5 text-left transition-colors hover:border-cyan">
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="truncate font-medium">{{ $group['label'] }}</span>
                            <span class="shrink-0 text-text-secondary">{{ $group['percent'] }}%</span>
                        </div>
                        <div class="progress mt-1 h-2">
                            <div class="progress-bar" role="progressbar"
                                 style="width: {{ max(4, $group['percent']) }}%;"
                                 aria-valuenow="{{ $group['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <p class="mt-1 text-xs text-text-secondary">{{ $group['sets'] }} série(s)</p>
                    </button>
                @endforeach
            </div>
        @endif
    </section>
</div>

@can('workouts.manage')
    <div id="applyWorkoutModal" class="modal-overlay hidden" role="dialog" aria-modal="true"
         aria-labelledby="applyWorkoutTitle">
        <div class="modal-content modal-content-lg flex max-h-[88vh] flex-col">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="applyWorkoutTitle" class="text-xl font-bold">Aplicar treino</h2>
                    <p class="mt-1 text-xs text-text-secondary">Escolha um treino cadastrado ou crie um personalizado para o aluno.</p>
                </div>
                <button type="button" onclick="closeApplyWorkoutModal()"
                        class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto pr-1">
                <section class="space-y-3 rounded-xl border border-custom p-3">
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <i class="fas fa-copy text-cyan"></i>
                        Usar treino cadastrado
                    </h3>

                    @if ($publicPlans->isEmpty())
                        <p class="text-sm text-text-secondary">Nenhum treino público disponível. Crie um em Treinos.</p>
                    @else
                        <form method="POST" action="{{ route('students.workouts.apply', $student) }}" class="space-y-3">
                            @csrf
                            <select name="workout_plan_id" required class="input-field">
                                <option value="">Selecione o treino</option>
                                @foreach ($publicPlans as $publicPlan)
                                    <option value="{{ $publicPlan->id }}">
                                        {{ $publicPlan->name }} ({{ $publicPlan->sessions_count }} sessões &middot; {{ $publicPlan->copies_label }})
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn-primary w-full">
                                <i class="fas fa-copy mr-2"></i>
                                Aplicar ao aluno
                            </button>
                        </form>
                    @endif
                </section>

                <section class="space-y-3 rounded-xl border border-custom p-3">
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <i class="fas fa-plus text-cyan"></i>
                        Criar treino personalizado
                    </h3>
                    <p class="text-xs text-text-secondary">
                        Crie um treino particular para este aluno e monte as sessões em seguida.
                    </p>

                    <form method="POST" action="{{ route('students.workouts.plan', $student) }}" class="space-y-3">
                        @csrf
                        <input type="text" name="name" required maxlength="255" class="input-field"
                               placeholder="Nome do treino" value="Treino personalizado - {{ $student->name }}">
                        <input type="text" name="objective" maxlength="255" class="input-field" placeholder="Objetivo (opcional)">
                        <textarea name="notes" rows="2" maxlength="2000" class="input-field" placeholder="Observações (opcional)"></textarea>
                        <button type="submit" class="btn-primary w-full">
                            <i class="fas fa-plus mr-2"></i>
                            Criar e montar
                        </button>
                    </form>
                </section>
            </div>

            <div class="mt-3 flex justify-end border-t border-custom pt-3">
                <button type="button" onclick="closeApplyWorkoutModal()" class="btn-outline">Fechar</button>
            </div>
        </div>
    </div>
@endcan

@foreach ($muscleGroups as $group)
    <div id="workoutMuscleGroupModal-{{ $group['group'] }}" data-workout-muscle-group-modal="{{ $group['group'] }}"
         class="modal-overlay hidden" role="dialog" aria-modal="true"
         aria-labelledby="workoutMuscleGroupTitle-{{ $group['group'] }}">
        <div class="modal-content flex max-h-[85vh] flex-col">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="workoutMuscleGroupTitle-{{ $group['group'] }}" class="text-xl font-bold">{{ $group['label'] }}</h2>
                    <p class="mt-1 text-xs text-text-secondary">{{ $group['percent'] }}% das repetições registradas</p>
                </div>
                <button type="button" onclick="closeWorkoutMuscleGroupModal(@js($group['group']))"
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
                <button type="button" onclick="closeWorkoutMuscleGroupModal(@js($group['group']))" class="btn-outline">Fechar</button>
            </div>
        </div>
    </div>
@endforeach

<script>
    (function () {
        window.openApplyWorkoutModal = function () {
            const modal = document.getElementById('applyWorkoutModal');

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeApplyWorkoutModal = function () {
            const modal = document.getElementById('applyWorkoutModal');

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        window.openWorkoutMuscleGroupModal = function (group) {
            const modal = document.getElementById('workoutMuscleGroupModal-' + group);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeWorkoutMuscleGroupModal = function (group) {
            const modal = document.getElementById('workoutMuscleGroupModal-' + group);

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        document.querySelectorAll('[data-workout-muscle-group-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeWorkoutMuscleGroupModal(modal.dataset.workoutMuscleGroupModal);
                }
            });
        });

        document.querySelectorAll('#applyWorkoutModal').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeApplyWorkoutModal();
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-workout-muscle-group-modal]:not(.hidden)').forEach(function (modal) {
                closeWorkoutMuscleGroupModal(modal.dataset.workoutMuscleGroupModal);
            });

            const applyModal = document.getElementById('applyWorkoutModal');

            if (applyModal && !applyModal.classList.contains('hidden')) {
                closeApplyWorkoutModal();
            }
        });
    })();
</script>
@endsection
