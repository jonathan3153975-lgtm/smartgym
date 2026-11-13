@extends('layouts.app')

@section('title', 'Ficha técnica - '.$student->name)

@section('content')
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
            <a href="{{ route('students.measurements', $student) }}" class="btn-outline">
                <i class="fas fa-weight-scale mr-2"></i>
                Medidas
            </a>
            @if ($activePlan)
                <a href="{{ route('admin.workout-plans.builder', $activePlan) }}" class="btn-primary">
                    <i class="fas fa-list-check mr-2"></i>
                    Editar treino atual
                </a>
            @endif
        </div>
    </div>

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

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="card space-y-4">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-layer-group text-cyan"></i>
                Grupos musculares treinados
            </h2>

            @if ($volumeByMuscle->isEmpty())
                <p class="text-sm text-text-secondary">Sem treinos concluídos ainda.</p>
            @else
                <div class="space-y-3">
                    @foreach ($volumeByMuscle as $item)
                        <div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-medium">{{ $item['label'] }}</span>
                                <span class="text-text-secondary">{{ $item['sets'] }} séries · {{ number_format($item['volume'], 0, ',', '.') }} kg</span>
                            </div>
                            <div class="mt-1 h-2 overflow-hidden rounded-full bg-surface-elevated">
                                <div class="h-full rounded-full" style="width: {{ $item['percent'] }}%; background: var(--color-primary);"></div>
                            </div>
                        </div>
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

            @if ($topExercises->isEmpty())
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
            <div class="flex items-center justify-between">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-weight-scale text-cyan"></i>
                    Acompanhamento de medidas
                </h2>
                <a href="{{ route('students.measurements', $student) }}" class="text-sm font-semibold text-cyan hover:underline">Ver todas</a>
            </div>

            @if ($measurements->isEmpty())
                <p class="text-sm text-text-secondary">Nenhuma medida registrada.</p>
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

            @if ($student->assessment?->isCompleted())
                <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                    <p class="text-xs text-text-secondary">Avaliação mais recente</p>
                    <p class="mt-1 font-medium">{{ $student->assessment->goal_label }}</p>
                    <p class="text-xs text-text-secondary">
                        {{ $student->assessment->experience_level_label }}
                        &middot; {{ $student->assessment->training_frequency }}x por semana
                        @if ($student->assessment->parq_positive)
                            &middot; <span class="text-yellow">PAR-Q com alerta</span>
                        @endif
                    </p>
                </div>
            @else
                <p class="text-sm text-text-secondary">O aluno ainda não preencheu a avaliação.</p>
            @endif
        </section>
    </div>

    <section class="card space-y-4">
        <h2 class="flex items-center gap-2 font-semibold">
            <i class="fas fa-clock-rotate-left text-cyan"></i>
            Treinos recentes
        </h2>

        @if ($recentLogs->isEmpty())
            <p class="text-sm text-text-secondary">Nenhum treino concluído ainda.</p>
        @else
            <div class="space-y-2">
                @foreach ($recentLogs as $log)
                    <div class="flex flex-col gap-2 rounded-xl border border-custom bg-bg-secondary p-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium">{{ $log->session_name }}</p>
                            <p class="text-xs text-text-secondary">{{ $log->started_at->format('d/m/Y H:i') }} &middot; {{ $log->duration_label }}</p>
                        </div>
                        <span class="badge badge-info">{{ $log->sets->count() }} séries &middot; {{ number_format($log->total_volume, 0, ',', '.') }} kg</span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="card space-y-4">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-copy text-cyan"></i>
                Aplicar treino existente
            </h2>
            <p class="text-xs text-text-secondary">
                Copie um treino público da rede para o aluno. Uma cópia particular e editável será criada.
            </p>

            @if ($publicPlans->isEmpty())
                <p class="text-sm text-text-secondary">Nenhum treino público disponível. Crie um em Treinos.</p>
            @else
                <form method="POST" action="{{ route('students.technical-sheet.apply', $student) }}" class="space-y-3">
                    @csrf
                    <select name="workout_plan_id" required class="input-field">
                        <option value="">Selecione o treino</option>
                        @foreach ($publicPlans as $publicPlan)
                            <option value="{{ $publicPlan->id }}">
                                {{ $publicPlan->name }} ({{ $publicPlan->sessions_count }} sessões)
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

        <section class="card space-y-4">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-plus text-cyan"></i>
                Criar treino personalizado
            </h2>
            <p class="text-xs text-text-secondary">
                Crie um treino particular para este aluno e monte as sessões em seguida.
            </p>

            <form method="POST" action="{{ route('students.technical-sheet.plan', $student) }}" class="space-y-3">
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
</div>
@endsection
