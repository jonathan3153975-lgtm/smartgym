@extends('layouts.student')

@section('title', 'Histórico de treinos')

@section('content')
<div class="space-y-6">
    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('student.portal.workouts.index', $network) }}" class="text-sm text-text-secondary hover:text-cyan">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Voltar para meus treinos
                </a>
                <h1 class="mt-1 text-2xl font-bold">Histórico de treinos</h1>
                <p class="mt-1 text-sm text-text-secondary">Treinos concluídos, duração e volume registrados.</p>
            </div>

            <a href="{{ route('student.portal.workouts.evolution', $network) }}" class="btn-outline self-start sm:self-auto">
                <i class="fas fa-chart-line mr-2"></i>
                Ver evolução
            </a>
        </div>
    </div>

    @if ($logs->isEmpty())
        <div class="card py-12 text-center">
            <i class="fas fa-clock-rotate-left mb-4 text-4xl text-text-secondary"></i>
            <p class="text-lg font-medium">Nenhum treino concluído ainda</p>
            <p class="mt-1 text-sm text-text-secondary">Inicie um treino do seu plano para começar o histórico.</p>
            <a href="{{ route('student.portal.workouts.index', $network) }}" class="btn-primary mt-4">
                <i class="fas fa-dumbbell mr-2"></i>
                Ver meus treinos
            </a>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($logs as $log)
                <a href="{{ route('student.portal.workouts.history.show', [$network, $log]) }}"
                   class="card block transition-colors hover:border-cyan">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs text-text-secondary">{{ $log->started_at->format('d/m/Y H:i') }}</p>
                            <h2 class="mt-1 text-lg font-semibold">{{ $log->session_name }}</h2>
                            <p class="mt-1 text-sm text-text-secondary">
                                {{ $log->sets->count() }} séries
                                &middot; {{ $log->duration_label }}
                                @if ($log->plan)
                                    &middot; {{ $log->plan->name }}
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <p class="text-xs text-text-secondary">Volume</p>
                                <p class="font-semibold text-cyan">{{ number_format($log->total_volume, 0, ',', '.') }} kg</p>
                            </div>
                            <i class="fas fa-chevron-right text-text-secondary"></i>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

        <div class="border-t border-custom pt-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
