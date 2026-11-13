@extends('layouts.student')

@section('title', 'Evolução dos treinos')

@section('content')
<div class="space-y-6">
    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('student.portal.workouts.index', $network) }}" class="text-sm text-text-secondary hover:text-cyan">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Voltar para meus treinos
                </a>
                <h1 class="mt-1 text-2xl font-bold">Evolução dos treinos</h1>
                <p class="mt-1 text-sm text-text-secondary">Volume, frequência e cargas registradas nos treinos concluídos.</p>
            </div>

            <a href="{{ route('student.portal.workouts.history', $network) }}" class="btn-outline self-start sm:self-auto">
                <i class="fas fa-clock-rotate-left mr-2"></i>
                Histórico
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
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
            <section class="card space-y-4">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-layer-group text-cyan"></i>
                    Volume por grupo muscular
                </h2>

                @if ($volumeByMuscle->isEmpty())
                    <p class="text-sm text-text-secondary">Nenhuma carga registrada ainda.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($volumeByMuscle as $item)
                            <div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">{{ $item['label'] }}</span>
                                    <span class="text-text-secondary">
                                        {{ number_format($item['volume'], 0, ',', '.') }} kg
                                        &middot; {{ $item['sets'] }} séries
                                    </span>
                                </div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-surface-elevated">
                                    <div class="h-full rounded-full"
                                         style="width: {{ $item['percent'] }}%; background: var(--color-primary);"></div>
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
                                <div class="h-full rounded-full"
                                     style="width: {{ $week['percent'] }}%; background: var(--color-success);"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="card space-y-4">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-weight-hanging text-cyan"></i>
                Principais exercícios
            </h2>

            @if ($topExercises->isEmpty())
                <p class="text-sm text-text-secondary">Nenhum exercício registrado ainda.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Exercício</th>
                                <th>Séries</th>
                                <th>Carga máxima</th>
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
    @endif
</div>
@endsection
