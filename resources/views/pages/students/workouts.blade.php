@extends('layouts.app')

@section('title', 'Treinos - '.$student->name)

@section('content')
@php
    $palette = [
        'var(--color-primary)',
        'var(--color-info)',
        'var(--color-success)',
        'var(--color-warning)',
        'var(--color-danger)',
        'var(--color-accent)',
        'var(--color-purple-neon)',
    ];
    $circumference = 2 * M_PI * 42;
    $totalPlanPercent = max(1, array_sum(array_column($planMuscleGroups, 'percent')));
    $donutOffset = 0;
    $donutSegments = [];

    foreach ($planMuscleGroups as $index => $group) {
        $length = ($group['percent'] / $totalPlanPercent) * $circumference;
        $donutSegments[] = [
            'group' => $group,
            'color' => $palette[$index % count($palette)],
            'length' => $length,
            'offset' => $donutOffset,
        ];
        $donutOffset += $length;
    }
@endphp

<div class="space-y-6 p-4 sm:p-6">
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
                    <a href="{{ route('admin.workout-plans.builder', ['workoutPlan' => $activePlan, 'from' => 'student']) }}" class="btn-outline">
                        <i class="fas fa-list-check mr-2"></i>
                        Editar treino atual
                    </a>
                @endcan
            @endif
        </div>
    </div>

    @include('pages.students._tabs', ['student' => $student, 'activeTab' => 'workouts'])

    <div class="grid grid-cols-1 gap-6 md:grid-cols-12">
        <section class="card space-y-4 md:col-span-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-cyan">Plano de treino atual</p>
                    <h2 class="mt-1 text-xl font-bold">{{ $activePlan?->name ?? 'Nenhum plano ativo' }}</h2>
                    @if ($activePlan)
                        <p class="mt-1 text-xs text-text-secondary">
                            {{ $activePlan->objective ?: 'Sem objetivo informado' }}
                            &middot; {{ $activePlan->sessions_count }} sessão(ões)
                            &middot; aplicado em {{ $activePlan->created_at?->format('d/m/Y') ?? '—' }}
                        </p>
                        @if ($activePlan->goal_labels !== [])
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach ($activePlan->goal_labels as $goal)
                                    <span class="badge badge-info">{{ $goal }}</span>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
                @if ($activePlan)
                    @can('workouts.manage')
                        <a href="{{ route('admin.workout-plans.builder', ['workoutPlan' => $activePlan, 'from' => 'student']) }}" class="btn-outline self-start">
                            <i class="fas fa-pen mr-2"></i>
                            Editar plano
                        </a>
                    @endcan
                @endif
            </div>

            @if ($planSessions === [])
                <p class="text-sm text-text-secondary">Este plano ainda não possui sessões ou não há plano ativo.</p>
            @else
                <div class="space-y-2">
                    @foreach ($planSessions as $session)
                        <div class="flex flex-col gap-2 rounded-xl border border-custom bg-bg-secondary p-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate font-medium">{{ $session['name'] }}</p>
                                    @if ($session['day_label'])
                                        <span class="badge badge-secondary">{{ $session['day_label'] }}</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-text-secondary">
                                    {{ $session['exercise_count'] }} exercício(s)
                                    &middot; concluído {{ $session['completed_count'] }} vez(es) pelo aluno
                                </p>
                            </div>
                            <button type="button" onclick="openSessionDetailsModal({{ $session['id'] }})"
                                    class="btn-outline self-start sm:self-auto">
                                <i class="fas fa-eye mr-2"></i>
                                Detalhes
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card space-y-4 md:col-span-4">
            <div class="flex flex-col gap-1">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-chart-pie text-cyan"></i>
                    Grupos musculares do plano
                </h2>
                <p class="text-xs text-text-secondary">Proporção dos exercícios do plano atual.</p>
            </div>

            @if ($planMuscleGroups === [])
                <p class="text-sm text-text-secondary">Sem exercícios no plano atual.</p>
            @else
                <div class="relative mx-auto h-48 w-48">
                    <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90" role="img" aria-label="Distribuição por grupos musculares do plano">
                        <circle cx="50" cy="50" r="42" fill="none" stroke="var(--color-border)" stroke-width="16"></circle>
                        @foreach ($donutSegments as $segment)
                            <circle cx="50" cy="50" r="42" fill="none"
                                    stroke="{{ $segment['color'] }}"
                                    stroke-width="16"
                                    stroke-dasharray="0 {{ $circumference }}"
                                    data-dasharray="{{ $segment['length'] }} {{ $circumference - $segment['length'] }}"
                                    stroke-dashoffset="{{ -$segment['offset'] }}"
                                    class="donut-segment cursor-pointer transition-opacity hover:opacity-75"
                                    onclick="openPlanMuscleGroupModal(@js($segment['group']['group']))">
                                <title>{{ $segment['group']['label'] }}: {{ $segment['group']['percent'] }}%</title>
                            </circle>
                        @endforeach
                    </svg>
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                        <p class="text-2xl font-bold">{{ count($planMuscleGroups) }}</p>
                        <p class="text-[0.62rem] uppercase tracking-wider text-text-secondary">grupos</p>
                    </div>
                </div>

                <div class="space-y-2">
                    @foreach ($donutSegments as $segment)
                        <button type="button" onclick="openPlanMuscleGroupModal(@js($segment['group']['group']))"
                                class="interactive-item flex w-full cursor-pointer items-center justify-between gap-2 rounded-lg border border-custom bg-bg-secondary px-3 py-2 text-left text-sm transition-colors hover:border-cyan">
                            <span class="flex min-w-0 items-center gap-2">
                                <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full" style="background: {{ $segment['color'] }};"></span>
                                <span class="truncate">{{ $segment['group']['label'] }}</span>
                            </span>
                            <span class="flex-shrink-0 text-text-secondary">{{ $segment['group']['percent'] }}%</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <section class="card flex max-h-[60vh] flex-col">
            <div class="flex flex-col gap-1 pb-3">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-ranking-star text-cyan"></i>
                    Exercícios mais praticados
                </h2>
                <p class="text-xs text-text-secondary">Selecione um exercício para ver a evolução de carga ao lado.</p>
            </div>

            @if ($exerciseProgress === [])
                <p class="text-sm text-text-secondary">Nenhum exercício executado ainda.</p>
            @else
                <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
                    @foreach ($exerciseProgress as $index => $exercise)
                        <button type="button" data-exercise-progress="{{ $index }}"
                                onclick="selectExerciseProgress({{ $index }})"
                                class="exercise-progress-item interactive-item w-full cursor-pointer rounded-xl border border-custom bg-bg-secondary p-2.5 text-left transition-colors hover:border-cyan">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{{ $exercise['name'] }}</p>
                                    <p class="text-xs text-text-secondary">{{ $exercise['muscle_group'] }}</p>
                                </div>
                                <span class="flex-shrink-0 text-xs text-text-secondary">{{ $exercise['sets'] }} série(s)</span>
                            </div>
                            <div class="mt-1 flex items-center justify-between text-xs text-text-secondary">
                                <span>{{ $exercise['reps'] }} repetições</span>
                                <span>máx. {{ $exercise['max_weight_label'] }}</span>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="card flex max-h-[60vh] flex-col">
            <div class="flex flex-col gap-1 pb-3">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-chart-column text-cyan"></i>
                    Evolução de carga
                </h2>
                <p id="loadEvolutionSubtitle" class="text-xs text-text-secondary">Todos os exercícios &middot; volume total por data.</p>
            </div>

            <div id="loadEvolutionPanel" class="min-h-0 flex-1 overflow-y-auto pr-1"></div>
        </section>
    </div>

    <section class="card space-y-4">
        <div class="flex flex-col gap-1">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-clipboard-list text-cyan"></i>
                Treinos aplicados
            </h2>
            <p class="text-xs text-text-secondary">Histórico de treinos vinculados a este aluno. Apenas o treino ativo pode ser editado.</p>
        </div>

        @if ($appliedPlans->isEmpty())
            <p class="text-sm text-text-secondary">Nenhum treino aplicado ainda.</p>
        @else
            <div class="space-y-2">
                @foreach ($appliedPlans as $plan)
                    <div class="flex flex-col gap-3 rounded-xl border border-custom bg-bg-secondary p-3 lg:flex-row lg:items-center lg:justify-between">
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

                        <div class="flex flex-wrap gap-2 self-start lg:self-auto">
                            <button type="button" onclick="openAppliedPlanExtractModal({{ $plan->id }})" class="btn-outline">
                                <i class="fas fa-eye mr-2"></i>
                                Extrato
                            </button>
                            @if ($plan->isActive())
                                @can('workouts.manage')
                                    <a href="{{ route('admin.workout-plans.builder', ['workoutPlan' => $plan, 'from' => 'student']) }}" class="btn-outline">
                                        <i class="fas fa-pen mr-2"></i>
                                        Editar
                                    </a>
                                @endcan
                            @endif
                        </div>
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
            <p class="text-xs text-text-secondary">Histórico de treinos executados pelo aluno.</p>
        </div>

        @if ($recentLogs->isEmpty())
            <p class="text-sm text-text-secondary">Nenhum treino concluído ainda.</p>
        @else
            <div class="max-h-[60vh] overflow-auto">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Treino</th>
                            <th>Duração</th>
                            <th class="text-right">Séries</th>
                            <th class="text-right">Volume</th>
                            <th class="text-right">Ações</th>
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
                                <td class="text-right">
                                    <button type="button" onclick="openWorkoutExtractModal({{ $log->id }})" class="btn-outline">
                                        <i class="fas fa-eye mr-2"></i>
                                        Extrato
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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

@foreach ($planSessions as $session)
    <div id="sessionDetailsModal-{{ $session['id'] }}" data-session-details-modal="{{ $session['id'] }}"
         class="modal-overlay hidden" role="dialog" aria-modal="true"
         aria-labelledby="sessionDetailsTitle-{{ $session['id'] }}">
        <div class="modal-content modal-content-lg flex max-h-[88vh] flex-col">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="sessionDetailsTitle-{{ $session['id'] }}" class="text-xl font-bold">{{ $session['name'] }}</h2>
                    <p class="mt-1 text-xs text-text-secondary">
                        {{ $session['exercise_count'] }} exercício(s)
                        &middot; concluído {{ $session['completed_count'] }} vez(es) pelo aluno
                    </p>
                </div>
                <button type="button" onclick="closeSessionDetailsModal({{ $session['id'] }})"
                        class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                <section class="space-y-2">
                    <h3 class="text-sm font-semibold">Exercícios da sessão</h3>
                    @forelse ($session['exercises'] as $exercise)
                        <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                            <div class="flex items-start gap-3">
                                @if ($exercise['image_url'])
                                    <img src="{{ $exercise['image_url'] }}" alt="{{ $exercise['name'] }}"
                                         class="h-12 w-12 flex-shrink-0 rounded-lg border border-custom object-cover">
                                @endif
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium">{{ $exercise['name'] }}</p>
                                    <p class="text-xs text-text-secondary">{{ $exercise['muscle_group_label'] }}</p>
                                    <p class="mt-1 text-xs text-text-secondary">
                                        {{ $exercise['sets'] }} séries × {{ $exercise['reps'] }} reps
                                        @if ($exercise['suggested_load'])
                                            &middot; carga {{ $exercise['suggested_load'] }}
                                        @endif
                                        @if ($exercise['rest_seconds'])
                                            &middot; descanso {{ $exercise['rest_seconds'] }}s
                                        @endif
                                    </p>
                                    @if ($exercise['notes'])
                                        <p class="mt-1 text-xs text-text-secondary">{{ $exercise['notes'] }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-text-secondary">Nenhum exercício cadastrado nesta sessão.</p>
                    @endforelse
                </section>

                <section class="space-y-2">
                    <h3 class="text-sm font-semibold">Grupos musculares da sessão</h3>
                    @if ($session['muscle_groups'] === [])
                        <p class="text-sm text-text-secondary">Sem grupos musculares cadastrados.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="table-custom">
                                <thead>
                                    <tr>
                                        <th>Grupo</th>
                                        <th class="text-right">Exercícios</th>
                                        <th class="text-right">Percentual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($session['muscle_groups'] as $group)
                                        <tr>
                                            <td class="font-medium">{{ $group['label'] }}</td>
                                            <td class="text-right">{{ $group['exercises'] }}</td>
                                            <td class="text-right">
                                                <div class="flex items-center justify-end gap-2">
                                                    <div class="progress h-1.5 w-20">
                                                        <div class="progress-bar animate-progress" style="width: {{ max(4, $group['percent']) }}%;"></div>
                                                    </div>
                                                    <span>{{ $group['percent'] }}%</span>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>

            <div class="mt-3 flex justify-end border-t border-custom pt-3">
                <button type="button" onclick="closeSessionDetailsModal({{ $session['id'] }})" class="btn-outline">Fechar</button>
            </div>
        </div>
    </div>
@endforeach

@foreach ($planMuscleGroups as $group)
    <div id="planMuscleGroupModal-{{ $group['group'] }}" data-plan-muscle-group-modal="{{ $group['group'] }}"
         class="modal-overlay hidden" role="dialog" aria-modal="true"
         aria-labelledby="planMuscleGroupTitle-{{ $group['group'] }}">
        <div class="modal-content flex max-h-[85vh] flex-col">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="planMuscleGroupTitle-{{ $group['group'] }}" class="text-xl font-bold">{{ $group['label'] }}</h2>
                    <p class="mt-1 text-xs text-text-secondary">{{ $group['percent'] }}% dos exercícios do plano</p>
                </div>
                <button type="button" onclick="closePlanMuscleGroupModal(@js($group['group']))"
                        class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-2 overflow-y-auto pr-1">
                @forelse ($planMuscleGroupExercises[$group['group']] ?? [] as $exercise)
                    <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate font-medium">{{ $exercise['name'] }}</p>
                                <p class="text-xs text-text-secondary">{{ $exercise['session'] }}</p>
                            </div>
                            <span class="flex-shrink-0 text-xs text-text-secondary">
                                {{ $exercise['sets'] }} × {{ $exercise['reps'] }}
                            </span>
                        </div>
                        @if ($exercise['suggested_load'])
                            <p class="mt-1 text-xs text-text-secondary">Carga sugerida: {{ $exercise['suggested_load'] }}</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-text-secondary">Nenhum exercício cadastrado para este grupo.</p>
                @endforelse
            </div>

            <div class="mt-3 flex justify-end border-t border-custom pt-3">
                <button type="button" onclick="closePlanMuscleGroupModal(@js($group['group']))" class="btn-outline">Fechar</button>
            </div>
        </div>
    </div>
@endforeach

@foreach ($appliedPlans as $plan)
    <div id="appliedPlanExtractModal-{{ $plan->id }}" data-applied-plan-extract-modal="{{ $plan->id }}"
         class="modal-overlay hidden" role="dialog" aria-modal="true"
         aria-labelledby="appliedPlanExtractTitle-{{ $plan->id }}">
        <div class="modal-content modal-content-lg flex max-h-[88vh] flex-col">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="appliedPlanExtractTitle-{{ $plan->id }}" class="text-xl font-bold">{{ $plan->name }}</h2>
                    <p class="mt-1 text-xs text-text-secondary">
                        {{ $plan->isActive() ? 'Plano ativo' : 'Plano inativo' }}
                        &middot; {{ $plan->sessions_count }} sessão(ões)
                        &middot; aplicado em {{ $plan->created_at?->format('d/m/Y') ?? '—' }}
                    </p>
                </div>
                <button type="button" onclick="closeAppliedPlanExtractModal({{ $plan->id }})"
                        class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                @forelse ($plan->sessions as $session)
                    <section class="rounded-xl border border-custom p-3">
                        <h3 class="text-sm font-semibold">{{ $session->name }}</h3>
                        @if ($session->day_label)
                            <p class="text-xs text-text-secondary">{{ $session->day_label }}</p>
                        @endif

                        <div class="mt-2 space-y-2">
                            @forelse ($session->exercises as $exercise)
                                <div class="rounded-lg border border-custom bg-bg-secondary p-2.5">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium">{{ $exercise->exercise_name }}</p>
                                            <p class="text-xs text-text-secondary">
                                                {{ $exercise->muscle_group_label }}
                                                &middot; {{ $exercise->sets }} séries × {{ $exercise->reps }} reps
                                            </p>
                                        </div>
                                        <span class="flex-shrink-0 text-xs text-text-secondary">
                                            {{ $exercise->suggested_load ?: 'Sem carga' }}
                                        </span>
                                    </div>
                                    @if ($exercise->rest_seconds)
                                        <p class="mt-1 text-xs text-text-secondary">Descanso: {{ $exercise->rest_seconds }}s</p>
                                    @endif
                                    @if ($exercise->notes)
                                        <p class="mt-1 text-xs text-text-secondary">{{ $exercise->notes }}</p>
                                    @endif
                                </div>
                            @empty
                                <p class="text-xs text-text-secondary">Nenhum exercício nesta sessão.</p>
                            @endforelse
                        </div>
                    </section>
                @empty
                    <p class="text-sm text-text-secondary">Este plano não possui sessões.</p>
                @endforelse
            </div>

            <div class="mt-3 flex justify-end border-t border-custom pt-3">
                <button type="button" onclick="closeAppliedPlanExtractModal({{ $plan->id }})" class="btn-outline">Fechar</button>
            </div>
        </div>
    </div>
@endforeach

@foreach ($recentLogs as $log)
    @php($extract = $workoutExtracts[$log->id] ?? null)
    <div id="workoutExtractModal-{{ $log->id }}" data-workout-extract-modal="{{ $log->id }}"
         class="modal-overlay hidden" role="dialog" aria-modal="true"
         aria-labelledby="workoutExtractTitle-{{ $log->id }}">
        <div class="modal-content modal-content-lg flex max-h-[88vh] flex-col">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="workoutExtractTitle-{{ $log->id }}" class="text-xl font-bold">{{ $extract['name'] ?? $log->session_name }}</h2>
                    <p class="mt-1 text-xs text-text-secondary">
                        {{ $extract['date'] ?? '—' }}
                        &middot; {{ $extract['duration'] ?? $log->duration_label }}
                        &middot; {{ $extract['volume'] ?? number_format($log->total_volume, 2, ',', '.').' kg' }}
                    </p>
                </div>
                <button type="button" onclick="closeWorkoutExtractModal({{ $log->id }})"
                        class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                @forelse ($extract['exercises'] ?? [] as $exercise)
                    <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                        <div class="flex items-start gap-3">
                            @if ($exercise['image_url'])
                                <img src="{{ $exercise['image_url'] }}" alt="{{ $exercise['name'] }}"
                                     class="h-12 w-12 flex-shrink-0 rounded-lg border border-custom object-cover">
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium">{{ $exercise['name'] }}</p>
                                    @unless ($exercise['executed'])
                                        <span class="badge badge-warning">Não executado</span>
                                    @endunless
                                </div>
                                <p class="text-xs text-text-secondary">{{ $exercise['muscle_group'] }}</p>
                                @if ($exercise['planned_sets'])
                                    <p class="mt-1 text-xs text-text-secondary">
                                        Planejado: {{ $exercise['planned_sets'] }} séries × {{ $exercise['planned_reps'] }} reps
                                        @if ($exercise['planned_load'])
                                            &middot; carga {{ $exercise['planned_load'] }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>

                        @if ($exercise['sets'] !== [])
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($exercise['sets'] as $set)
                                    <span class="badge badge-secondary">
                                        {{ $set['number'] }}ª ·
                                        @if ($set['cardio_label'])
                                            {{ $set['cardio_label'] }}
                                            @if ($set['distance_label'])
                                                &middot; {{ $set['distance_label'] }}
                                            @endif
                                        @else
                                            {{ $set['reps'] ?? '—' }} reps
                                            @if ($set['weight_label'])
                                                &middot; {{ $set['weight_label'] }}
                                            @endif
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-2 text-sm text-text-secondary">Nenhuma série registrada para este exercício.</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-text-secondary">Nenhum exercício planejado para este treino.</p>
                @endforelse
            </div>

            <div class="mt-3 flex justify-end border-t border-custom pt-3">
                <button type="button" onclick="closeWorkoutExtractModal({{ $log->id }})" class="btn-outline">Fechar</button>
            </div>
        </div>
    </div>
@endforeach

<script>
    (function () {
        const exerciseProgress = @json($exerciseProgress);
        const overallEvolution = @json($overallEvolution);
        const evolutionPanel = document.getElementById('loadEvolutionPanel');
        const evolutionSubtitle = document.getElementById('loadEvolutionSubtitle');

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function (char) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
            });
        }

        function renderEvolution(points, subtitle) {
            if (!evolutionPanel) {
                return;
            }

            if (evolutionSubtitle) {
                evolutionSubtitle.textContent = subtitle;
            }

            if (!points || points.length === 0) {
                evolutionPanel.innerHTML = '<p class="py-6 text-center text-sm text-text-secondary">Sem evolução registrada.</p>';
                return;
            }

            const max = Math.max(1, ...points.map(function (point) {
                return Number(point.value) || 0;
            }));

            let html = '<div class="space-y-3">';

            points.forEach(function (point) {
                const percent = Math.max(4, Math.round(((Number(point.value) || 0) / max) * 100));
                html += '<div class="space-y-1">'
                    + '<div class="flex items-center justify-between gap-3 text-xs">'
                    + '<span class="text-text-secondary">' + escapeHtml(point.date) + '</span>'
                    + '<span class="font-medium">' + escapeHtml(point.label) + '</span>'
                    + '</div>'
                    + '<div class="h-2.5 w-full overflow-hidden rounded-full bg-bg-secondary">'
                    + '<div class="animate-progress h-full rounded-full" style="width: ' + percent + '%; background: var(--color-primary);"></div>'
                    + '</div>'
                    + '</div>';
            });

            html += '</div>';
            evolutionPanel.innerHTML = html;
        }

        window.selectExerciseProgress = function (index) {
            const exercise = exerciseProgress[index];

            if (!exercise) {
                return;
            }

            document.querySelectorAll('[data-exercise-progress]').forEach(function (button) {
                button.classList.remove('border-cyan', 'bg-surface');
            });

            const selected = document.querySelector('[data-exercise-progress="' + index + '"]');

            if (selected) {
                selected.classList.add('border-cyan', 'bg-surface');
            }

            renderEvolution(
                exercise.evolution,
                exercise.muscle_group + ' · ' + exercise.sets + ' séries · maior carga ' + exercise.max_weight_label
            );
        };

        renderEvolution(overallEvolution, 'Todos os exercícios · volume total por data.');

        window.addEventListener('load', function () {
            document.querySelectorAll('[data-dasharray]').forEach(function (segment) {
                segment.style.strokeDasharray = segment.dataset.dasharray;
            });
        });

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

        window.openSessionDetailsModal = function (id) {
            const modal = document.getElementById('sessionDetailsModal-' + id);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeSessionDetailsModal = function (id) {
            const modal = document.getElementById('sessionDetailsModal-' + id);

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        window.openPlanMuscleGroupModal = function (group) {
            const modal = document.getElementById('planMuscleGroupModal-' + group);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closePlanMuscleGroupModal = function (group) {
            const modal = document.getElementById('planMuscleGroupModal-' + group);

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        window.openAppliedPlanExtractModal = function (id) {
            const modal = document.getElementById('appliedPlanExtractModal-' + id);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeAppliedPlanExtractModal = function (id) {
            const modal = document.getElementById('appliedPlanExtractModal-' + id);

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        window.openWorkoutExtractModal = function (id) {
            const modal = document.getElementById('workoutExtractModal-' + id);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeWorkoutExtractModal = function (id) {
            const modal = document.getElementById('workoutExtractModal-' + id);

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        document.querySelectorAll('[data-session-details-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeSessionDetailsModal(modal.dataset.sessionDetailsModal);
                }
            });
        });

        document.querySelectorAll('[data-plan-muscle-group-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closePlanMuscleGroupModal(modal.dataset.planMuscleGroupModal);
                }
            });
        });

        document.querySelectorAll('[data-applied-plan-extract-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeAppliedPlanExtractModal(modal.dataset.appliedPlanExtractModal);
                }
            });
        });

        document.querySelectorAll('[data-workout-extract-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeWorkoutExtractModal(modal.dataset.workoutExtractModal);
                }
            });
        });

        const applyModal = document.getElementById('applyWorkoutModal');

        applyModal?.addEventListener('click', function (event) {
            if (event.target === applyModal) {
                closeApplyWorkoutModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-session-details-modal]:not(.hidden)').forEach(function (modal) {
                closeSessionDetailsModal(modal.dataset.sessionDetailsModal);
            });

            document.querySelectorAll('[data-plan-muscle-group-modal]:not(.hidden)').forEach(function (modal) {
                closePlanMuscleGroupModal(modal.dataset.planMuscleGroupModal);
            });

            document.querySelectorAll('[data-applied-plan-extract-modal]:not(.hidden)').forEach(function (modal) {
                closeAppliedPlanExtractModal(modal.dataset.appliedPlanExtractModal);
            });

            document.querySelectorAll('[data-workout-extract-modal]:not(.hidden)').forEach(function (modal) {
                closeWorkoutExtractModal(modal.dataset.workoutExtractModal);
            });

            if (applyModal && !applyModal.classList.contains('hidden')) {
                closeApplyWorkoutModal();
            }
        });
    })();
</script>
@endsection
