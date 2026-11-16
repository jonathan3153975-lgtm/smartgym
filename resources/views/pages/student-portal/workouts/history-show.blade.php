@extends('layouts.student')

@section('title', 'Detalhe do treino')

@section('content')
@php($groups = $workoutLog->sets->groupBy('exercise_name'))

<div class="mx-auto w-full max-w-4xl space-y-5">
    <div class="card">
        <a href="{{ route('student.portal.workouts.history', $network) }}" class="text-sm text-text-secondary hover:text-cyan">
            <i class="fas fa-arrow-left mr-1"></i>
            Voltar para o histórico
        </a>

        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Treino concluído</p>
                <h1 class="mt-1 text-xl font-bold sm:text-2xl">{{ $workoutLog->session_name }}</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    {{ $workoutLog->started_at->format('d/m/Y H:i') }}
                    &middot; {{ $workoutLog->duration_label }}
                    @if ($workoutLog->plan)
                        &middot; {{ $workoutLog->plan->name }}
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <span class="badge badge-success">Concluído</span>
                <span class="badge badge-info">{{ number_format($workoutLog->total_volume, 0, ',', '.') }} kg de volume</span>
            </div>
        </div>

        @if ($workoutLog->notes)
            <p class="mt-3 whitespace-pre-line text-sm text-text-secondary">{{ $workoutLog->notes }}</p>
        @endif
    </div>

    @if ($workoutLog->muscle_group_summary->isNotEmpty())
        <section class="card card-mobile-tight space-y-3">
            <h2 class="text-base font-semibold sm:text-lg">Resumo por grupo muscular</h2>

            @foreach ($workoutLog->muscle_group_summary as $group)
                <div>
                    <div class="flex items-center justify-between gap-2 text-xs">
                        <span class="font-medium">{{ $group['label'] }}</span>
                        <span class="text-text-secondary">
                            {{ $group['percent'] }}% &middot; {{ $group['exercises'] }} exercício(s)
                        </span>
                    </div>
                    <div class="progress mt-1 h-2">
                        <div class="progress-bar" role="progressbar"
                             style="width: {{ max(6, $group['percent']) }}%;"
                             aria-valuenow="{{ $group['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            @endforeach
        </section>
    @endif

    @if ($groups->isEmpty())
        <div class="card py-10 text-center">
            <p class="font-medium">Nenhuma série registrada neste treino</p>
        </div>
    @else
        <div class="-mx-2 space-y-4 sm:mx-0">
            @foreach ($groups as $exerciseName => $sets)
                @php($isCardio = $sets->contains(fn ($set) => (int) $set->cardio_seconds > 0 || (float) $set->distance_km > 0))
                <section class="card card-mobile-tight space-y-3">
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-semibold sm:text-lg">{{ $exerciseName }}</h2>
                        <div class="mt-1 flex items-center justify-between gap-2">
                            <p class="text-xs text-text-secondary">{{ $sets->count() }} série(s) registrada(s)</p>
                            <span class="badge badge-info whitespace-nowrap">{{ $sets->first()->muscle_group_label }}</span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        @foreach ($sets->sortBy('set_number') as $set)
                            <div class="rounded-xl border border-custom bg-bg-secondary p-2.5 sm:p-3">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold">{{ $set->set_number }}ª série</span>

                                    @if ($isCardio)
                                        <span class="text-right text-xs text-text-secondary">
                                            @if ((int) $set->cardio_seconds > 0)
                                                {{ sprintf('%d min %02d s', intdiv((int) $set->cardio_seconds, 60), (int) $set->cardio_seconds % 60) }}
                                            @else
                                                —
                                            @endif
                                            @if ($set->distance_km !== null && (float) $set->distance_km > 0)
                                                &middot; {{ number_format((float) $set->distance_km, 3, ',', '.').' km' }}
                                            @endif
                                        </span>
                                    @else
                                        <span class="text-right text-xs text-text-secondary">
                                            {{ $set->reps ?? '—' }} reps
                                            &middot; {{ $set->weight_kg !== null ? number_format((float) $set->weight_kg, 2, ',', '.').' kg' : '—' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif

    <div class="flex justify-center">
        <a href="{{ route('student.portal.workouts.index', $network) }}" class="btn-outline">
            <i class="fas fa-dumbbell mr-2"></i>
            Meus treinos
        </a>
    </div>
</div>
@endsection
