@extends('layouts.student')

@section('title', 'Turmas')

@section('content')
<div class="space-y-6">
    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Agenda</p>
                <h1 class="mt-1 text-2xl font-bold">Turmas</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    Confira as aulas disponíveis na sua rede e na unidade mais próxima.
                </p>
            </div>

            <form method="GET" action="{{ route('student.portal.classes.index', $network) }}" class="flex items-end gap-2">
                <div>
                    <label for="weekday" class="label-field">Dia da semana</label>
                    <select id="weekday" name="weekday" class="input-field" onchange="this.form.submit()">
                        @foreach (\App\Models\SchoolClass::WEEKDAYS as $value => $label)
                            <option value="{{ $value }}" @selected((int) $weekday === (int) $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <noscript><button type="submit" class="btn-outline">Filtrar</button></noscript>
            </form>
        </div>
    </div>

    @if (! $activeSubscription)
        <div class="card" style="border-color: color-mix(in srgb, var(--color-primary) 45%, var(--color-border));">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-semibold">Ainda sem plano ativo?</p>
                    <p class="mt-1 text-sm text-text-secondary">
                        Faça um check-in experimental gratuito ou garanta uma diária/aula avulsa.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if (! $trialUsed)
                        <form method="POST" action="{{ route('student.portal.checkin.trial', $network) }}">
                            @csrf
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-bolt mr-2"></i>
                                Check-in experimental
                            </button>
                        </form>
                    @endif
                    <a href="{{ route('student.portal.plans', $network) }}" class="btn-outline">
                        Ver planos e diárias
                    </a>
                </div>
            </div>
        </div>
    @endif

    @if ($classes->isEmpty())
        <div class="card py-12 text-center">
            <i class="fas fa-calendar-xmark mb-4 text-4xl text-text-secondary"></i>
            <p class="text-lg font-medium">Nenhuma turma neste dia</p>
            <p class="mt-1 text-sm text-text-secondary">Escolha outro dia da semana para ver a agenda.</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach ($classes as $class)
                @php
                    $nextDate = $nextDates[$class->id];
                    $booking = $bookings->get($class->id.'-'.$nextDate->format('Y-m-d'));
                @endphp

                <article class="card flex flex-col gap-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs text-text-secondary">{{ $nextDate->format('d/m/Y') }}</p>
                            <h2 class="mt-1 text-lg font-bold">{{ $class->name }}</h2>
                            <p class="mt-1 text-sm text-text-secondary">
                                {{ $class->modality?->name ?? 'Sem modalidade' }}
                                &middot; {{ $class->time_label }}
                            </p>
                        </div>
                        <span class="badge badge-info whitespace-nowrap">{{ $class->vacancies }} vagas</span>
                    </div>

                    <div class="space-y-1 text-sm text-text-secondary">
                        <p>
                            <i class="fas fa-building mr-2 w-4 text-center text-cyan"></i>
                            {{ $class->unit?->name ?? 'Unidade' }}
                            @if ($class->unit?->city)
                                &middot; {{ $class->unit->city }}/{{ $class->unit->state }}
                            @endif
                        </p>
                        @if ($class->teacher)
                            <p><i class="fas fa-chalkboard-user mr-2 w-4 text-center text-cyan"></i>{{ $class->teacher->name }}</p>
                        @endif
                        @if ($class->drop_in_price)
                            <p>
                                <i class="fas fa-ticket mr-2 w-4 text-center text-cyan"></i>
                                Aula avulsa: R$ {{ number_format((float) $class->drop_in_price, 2, ',', '.') }}
                            </p>
                        @endif
                    </div>

                    <div class="mt-auto">
                        @if ($booking)
                            <a href="{{ route('student.portal.classes.booking.status', [$network, $booking]) }}"
                               class="btn-outline w-full">
                                <i class="fas fa-ticket mr-2"></i>
                                {{ $booking->isConfirmed() ? 'Aula reservada' : 'Pagamento pendente' }}
                            </a>
                        @elseif ($activeSubscription)
                            <form method="POST" action="{{ route('student.portal.classes.reserve', [$network, $class]) }}">
                                @csrf
                                <button type="submit" class="btn-primary w-full">
                                    <i class="fas fa-calendar-check mr-2"></i>
                                    Reservar aula
                                </button>
                            </form>
                        @elseif ($class->drop_in_price && (float) $class->drop_in_price > 0)
                            <form method="POST" action="{{ route('student.portal.classes.checkout', [$network, $class]) }}">
                                @csrf
                                <button type="submit" class="btn-primary w-full">
                                    <i class="fas fa-credit-card mr-2"></i>
                                    Pagar aula avulsa
                                </button>
                            </form>
                        @else
                            <span class="badge badge-secondary">Aula avulsa não disponível</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
