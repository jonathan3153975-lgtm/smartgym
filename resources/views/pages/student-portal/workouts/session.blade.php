@extends('layouts.student')

@section('title', $workoutSession->name)

@section('content')
<div class="mx-auto w-full max-w-4xl space-y-5">
    <div class="card card-mobile-tight">
        <a href="{{ route('student.portal.workouts.index', $network) }}" class="text-sm text-text-secondary hover:text-cyan">
            <i class="fas fa-arrow-left mr-1"></i>
            Voltar para meus treinos
        </a>

        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">{{ $workoutSession->plan->name }}</p>
                <h1 class=" text-2xl font-bold">{{ $workoutSession->name }}</h1>
                <p class="text-sm text-text-secondary">
                    Sessão {{ $workoutSession->order }}
                    &middot; {{ $workoutSession->exercises->count() }} exercício(s)
                    @if ($workoutSession->plan->objective)
                        &middot; {{ $workoutSession->plan->objective }}
                    @endif
                </p>
            </div>

            @if ($inProgress)
                <a href="{{ route('student.portal.workouts.log', [$network, $inProgress]) }}" class="btn-primary self-end sm:self-auto">
                    <i class="fas fa-play mr-2"></i>
                    Continuar treino
                </a>
            @else
                <form method="POST" action="{{ route('student.portal.workouts.start', [$network, $workoutSession]) }}" class="self-end sm:self-auto">
                    @csrf
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-play mr-2"></i>
                        Iniciar treino
                    </button>
                </form>
            @endif
        </div>

        @if ($workoutSession->notes)
            <p class="mt-3 whitespace-pre-line text-sm text-text-secondary">{{ $workoutSession->notes }}</p>
        @endif
    </div>

    <div class="-mx-2 space-y-3 sm:mx-0">
        @foreach ($workoutSession->exercises as $workoutExercise)
            @php($stats = $exerciseStats[$workoutExercise->exercise_id] ?? null)
            <div class="card card-mobile-tight">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        @if ($workoutExercise->exercise?->image_url)
                            <img src="{{ $workoutExercise->exercise->image_url }}" alt="{{ $workoutExercise->exercise_name }}"
                                 class="h-16 w-16 flex-shrink-0 rounded-xl border border-custom object-cover">
                        @endif
                        <div>
                            <p class="text-xs text-text-secondary">Exercício {{ $workoutExercise->order }}</p>
                            <h2 class="mt-1 text-lg font-semibold">{{ $workoutExercise->exercise_name }}</h2>
                            <span class="badge badge-info mt-1">{{ $workoutExercise->muscle_group_label }}</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2 text-sm text-text-secondary">
                        <span class="badge badge-secondary">{{ $workoutExercise->sets }} séries</span>
                        <span class="badge badge-secondary">{{ $workoutExercise->reps }} repetições</span>
                        <span class="badge badge-secondary">descanso {{ $workoutExercise->rest_seconds }}s</span>
                    </div>
                </div>

                <div class="mt-3 space-y-1 text-sm text-text-secondary">
                    @if ($workoutExercise->suggested_load)
                        <p>
                            <i class="fas {{ $workoutExercise->isCardio() ? 'fa-stopwatch' : 'fa-weight-hanging' }} mr-2 w-4 text-center text-cyan"></i>
                            {{ $workoutExercise->isCardio() ? 'Tempo sugerido' : 'Carga sugerida' }}: {{ $workoutExercise->suggested_load }}
                        </p>
                    @endif
                    @if ($workoutExercise->notes)
                        <p><i class="fas fa-circle-info mr-2 w-4 text-center text-cyan"></i>{{ $workoutExercise->notes }}</p>
                    @endif
                </div>

                @if ($stats)
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @if (! $workoutExercise->isCardio())
                            @if ($stats['last_weight_label'])
                                <span class="badge badge-secondary">Última carga: {{ $stats['last_weight_label'] }}</span>
                            @endif
                            @if ($stats['max_weight_label'])
                                <span class="badge badge-secondary">Máxima: {{ $stats['max_weight_label'] }}</span>
                            @endif
                        @else
                            @if ($stats['last_seconds_label'])
                                <span class="badge badge-secondary">Último tempo: {{ $stats['last_seconds_label'] }}</span>
                            @endif
                            @if ($stats['max_seconds_label'])
                                <span class="badge badge-secondary">Melhor tempo: {{ $stats['max_seconds_label'] }}</span>
                            @endif
                            @if ($stats['last_distance_label'])
                                <span class="badge badge-secondary">Última distância: {{ $stats['last_distance_label'] }}</span>
                            @endif
                            @if ($stats['max_distance_label'])
                                <span class="badge badge-secondary">Maior distância: {{ $stats['max_distance_label'] }}</span>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
@endsection
