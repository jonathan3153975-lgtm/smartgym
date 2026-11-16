@extends('layouts.student')

@section('title', 'Histórico de treinos')

@section('content')
<div class="space-y-6">
    @include('pages.student-portal.workouts._nav')

    @php($monthNames = [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'])

    <form method="GET" action="{{ route('student.portal.workouts.history', $network) }}" class="grid grid-cols-2 gap-2">
        <div>
            <label for="history_month" class="sr-only">Mês</label>
            <select id="history_month" name="month" class="input-field" onchange="this.form.submit()">
                <option value="">Todos os meses</option>
                @foreach ($monthNames as $value => $label)
                    <option value="{{ $value }}" @selected((string) request('month') === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="history_year" class="sr-only">Ano</label>
            <select id="history_year" name="year" class="input-field" onchange="this.form.submit()">
                <option value="">Todos os anos</option>
                @foreach ($years as $yearOption)
                    <option value="{{ $yearOption }}" @selected((string) request('year') === (string) $yearOption)>{{ $yearOption }}</option>
                @endforeach
            </select>
        </div>
    </form>

    @if ($logs->isEmpty())
        <div class="card py-12 text-center">
            <i class="fas fa-clock-rotate-left mb-4 text-4xl text-text-secondary"></i>
            <p class="text-lg font-medium">Nenhum treino encontrado</p>
            <p class="mt-1 text-sm text-text-secondary">
                {{ request('month') || request('year') ? 'Ajuste o período ou conclua um treino para vê-lo aqui.' : 'Inicie um treino do seu plano para começar o histórico.' }}
            </p>
            <a href="{{ route('student.portal.workouts.index', $network) }}" class="btn-primary mt-4">
                <i class="fas fa-dumbbell mr-2"></i>
                Ver meus treinos
            </a>
        </div>
    @else
        <div class="-mx-2 space-y-3 sm:mx-0">
            @foreach ($logs as $log)
                <a href="{{ route('student.portal.workouts.history.show', [$network, $log]) }}"
                   class="card card-mobile-tight block transition-colors hover:border-cyan">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="min-w-0 flex-1 basis-60">
                            <h2 class="-ml-1 truncate text-lg font-bold sm:-ml-[18px] sm:text-xl">{{ $log->session_name }}</h2>

                            <div style="padding-left: 10px;">
                                <h2 class="text-lg font-semibold">{{ $log->plan?->name ?? 'Treino avulso' }}</h2>
                                <p class="text-xs text-text-secondary">{{ $log->started_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>

                        <div class="ml-auto flex shrink-0 items-center gap-3">
                            <div class="text-right">
                                <p class="text-xs text-text-secondary">Volume</p>
                                <p class="font-semibold text-cyan">{{ number_format($log->total_volume, 0, ',', '.') }} kg</p>
                            </div>
                            <i class="fas fa-chevron-right text-text-secondary"></i>
                        </div>
                    </div>

                    @if ($log->muscle_group_summary->isNotEmpty())
                        <div class="mt-4 space-y-2 border-t border-custom pt-3">
                            <p class="text-[11px] font-semibold uppercase tracking-wider text-text-secondary">
                                Resumo por grupo muscular
                            </p>

                            @foreach ($log->muscle_group_summary as $group)
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
                        </div>
                    @endif
                </a>
            @endforeach
        </div>

        <div class="border-t border-custom pt-4">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
