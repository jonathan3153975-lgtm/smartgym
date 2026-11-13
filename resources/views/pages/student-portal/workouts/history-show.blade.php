@extends('layouts.student')

@section('title', 'Detalhe do treino')

@section('content')
@php($groups = $workoutLog->sets->groupBy('exercise_name'))

<div class="mx-auto w-full max-w-4xl space-y-6">
    <div class="card">
        <a href="{{ route('student.portal.workouts.history', $network) }}" class="text-sm text-text-secondary hover:text-cyan">
            <i class="fas fa-arrow-left mr-1"></i>
            Voltar para o histórico
        </a>

        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Treino concluído</p>
                <h1 class="mt-1 text-2xl font-bold">{{ $workoutLog->session_name }}</h1>
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

    @if ($groups->isEmpty())
        <div class="card py-10 text-center">
            <p class="font-medium">Nenhuma série registrada neste treino</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($groups as $exerciseName => $sets)
                <section class="card space-y-3">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-lg font-semibold">{{ $exerciseName }}</h2>
                        <span class="badge badge-info">{{ $sets->first()->muscle_group_label }}</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th class="w-24">Série</th>
                                    <th>Repetições</th>
                                    <th>Carga</th>
                                    <th>Volume</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sets->sortBy('set_number') as $set)
                                    <tr>
                                        <td class="font-medium">{{ $set->set_number }}ª</td>
                                        <td>{{ $set->reps ?? '—' }}</td>
                                        <td>{{ $set->weight_kg !== null ? number_format((float) $set->weight_kg, 2, ',', '.').' kg' : '—' }}</td>
                                        <td>{{ number_format(((int) $set->reps) * ((float) $set->weight_kg), 0, ',', '.') }} kg</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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
