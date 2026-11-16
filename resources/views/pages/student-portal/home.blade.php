@extends('layouts.student')

@section('title', 'Home')

@section('content')
@php
    $student = $account->student;
    $nearestUnits = $accessibleUnits->map(fn ($unit) => [
        'name' => $unit->name,
        'address' => $unit->full_address,
        'latitude' => $unit->latitude,
        'longitude' => $unit->longitude,
    ])->values();
@endphp

<div class="space-y-5">
    @if ($pendingCharges->isNotEmpty())
        <div class="card border-0"
             style="background: var(--color-warning-soft); border-left: 4px solid var(--color-warning);">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl text-lg"
                         style="background: color-mix(in srgb, var(--color-warning) 20%, transparent); color: var(--color-warning);">
                        <i class="fas fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <p class="font-semibold">Você possui cobrança pendente</p>
                        <p class="mt-1 text-sm text-text-secondary">
                            {{ $pendingCharges->count() }} cobrança(s) totalizando
                            R$ {{ number_format((float) $pendingCharges->sum('amount'), 2, ',', '.') }}.
                            Regularize para manter seu acesso ativo.
                        </p>
                    </div>
                </div>

                <a href="{{ route('student.portal.finance.index', $network) }}" class="btn-primary self-end sm:self-auto">
                    <i class="fas fa-credit-card mr-2"></i>
                    Pagar agora
                </a>
            </div>
        </div>
    @endif

    <section class="card space-y-4">
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-lg text-white shadow-lg"
                         style="background: linear-gradient(135deg, var(--color-primary), var(--color-info));">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <h2 class="font-semibold">Meus planos</h2>
                        <p class="text-xs text-text-secondary">Planos ativos e vigências</p>
                    </div>
                </div>

                @forelse ($activeSubscriptions as $subscription)
                    <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="truncate font-semibold">{{ $subscription->plan_name }}</p>
                                <p class="text-xs text-text-secondary">
                                    {{ $subscription->plan_type_label }} &middot; {{ $subscription->period_label }}
                                </p>
                            </div>
                            <span class="badge badge-success whitespace-nowrap">Ativo</span>
                        </div>

                        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-text-secondary">
                            @if ($subscription->ends_at)
                                <span><i class="fas fa-calendar-check mr-1"></i>Válido até {{ $subscription->ends_at->format('d/m/Y') }}</span>
                            @endif
                            @if ($subscription->weekly_accesses)
                                <span><i class="fas fa-calendar-week mr-1"></i>{{ $subscription->weekly_accesses }}x/semana</span>
                            @endif
                            @if ($subscription->unit)
                                <span><i class="fas fa-building mr-1"></i>{{ $subscription->unit->name }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-custom bg-bg-secondary p-4 text-center">
                        <p class="font-medium">Nenhum plano ativo</p>
                        <p class="mt-1 text-sm text-text-secondary">Escolha um plano para liberar o acesso à unidade.</p>
                        <a href="{{ route('student.portal.plans', $network) }}" class="btn-primary mt-3 justify-center">
                            <i class="fas fa-layer-group mr-2"></i>
                            Ver planos
                        </a>
                    </div>
                @endforelse

                @if ($activeSubscriptions->isNotEmpty())
                    <a href="{{ route('student.portal.plans', $network) }}" class="inline-flex text-sm font-semibold text-cyan hover:underline">
                        Ver catálogo de planos
                    </a>
                @endif
            </div>

            <div class="space-y-4 lg:border-l lg:border-custom lg:pl-5" data-nearest-unit>
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-lg text-white shadow-lg"
                         style="background: linear-gradient(135deg, var(--color-info), var(--color-success));">
                        <i class="fas fa-location-dot"></i>
                    </div>
                    <div>
                        <h2 class="font-semibold">Unidade mais próxima</h2>
                        <p class="text-xs text-text-secondary">De acordo com a sua localização e planos ativos</p>
                    </div>
                </div>

                <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                    <p class="font-semibold" data-nearest-name>{{ $primaryUnit?->name ?? 'Nenhuma unidade vinculada' }}</p>
                    <p class="mt-1 text-xs text-text-secondary" data-nearest-address>{{ $primaryUnit?->full_address ?: 'Endereço não informado' }}</p>
                    <p class="mt-2 text-xs text-text-secondary" data-nearest-distance>
                        Ative a localização para calcular a unidade mais próxima.
                    </p>
                </div>
            </div>
        </div>
    </section>

    @if ($hasTrainingAccess)
        <section class="card">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-lg text-white shadow-lg"
                         style="background: linear-gradient(135deg, var(--color-primary), var(--color-success));">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <div>
                        <h2 class="font-semibold">Treino mais recente</h2>
                        <p class="text-xs text-text-secondary">Seu último treino concluído</p>
                    </div>
                </div>

                <a href="{{ route('student.portal.workouts.index', $network) }}" class="btn-outline self-end sm:self-auto">
                    <i class="fas fa-arrow-right mr-2"></i>
                    Meus treinos
                </a>
            </div>

            @if ($latestWorkout)
                <a href="{{ route('student.portal.workouts.history.show', [$network, $latestWorkout]) }}"
                   class="mt-4 block rounded-xl border border-custom bg-bg-secondary p-3 transition-colors hover:border-cyan">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="min-w-0 flex-1 basis-60">
                            <h2 class="truncate text-lg font-bold">{{ $latestWorkout->session_name }}</h2>

                            <div class="mt-3">
                                <h2 class="mt-1 text-lg font-semibold">{{ $latestWorkout->plan?->name ?? 'Treino avulso' }}</h2>
                                <p class="text-xs text-text-secondary">{{ $latestWorkout->started_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>

                        <div class="ml-auto flex shrink-0 items-center gap-3">
                            <div class="text-right">
                                <p class="text-xs text-text-secondary">Volume</p>
                                <p class="font-semibold text-cyan">{{ number_format($latestWorkout->total_volume, 0, ',', '.') }} kg</p>
                            </div>
                            <i class="fas fa-chevron-right text-text-secondary"></i>
                        </div>
                    </div>
                </a>
            @else
                <p class="mt-4 text-sm text-text-secondary">Nenhum treino concluído ainda. Comece pelo seu plano de treinos.</p>
            @endif
        </section>
    @endif

    @if ($hasClassAccess || $openClassBookings->isNotEmpty())
        <section class="card space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-lg text-white shadow-lg"
                         style="background: linear-gradient(135deg, var(--color-accent), var(--color-primary));">
                        <i class="fas fa-calendar-days"></i>
                    </div>
                    <div>
                        <h2 class="font-semibold">Aulas</h2>
                        <p class="text-xs text-text-secondary">Turmas do dia e suas reservas</p>
                    </div>
                </div>

                <a href="{{ route('student.portal.classes.index', $network) }}" class="btn-outline self-end sm:self-auto">
                    <i class="fas fa-arrow-right mr-2"></i>
                    Ver turmas
                </a>
            </div>

            @if ($lastClassCheckin)
                <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                    <p class="text-xs text-text-secondary">Último check-in em aula</p>
                    <p class="mt-1 font-semibold">
                        {{ $lastClassCheckin->schoolClass?->name ?? 'Aula' }}
                        @if ($lastClassCheckin->schoolClass?->modality)
                            &middot; {{ $lastClassCheckin->schoolClass->modality->name }}
                        @endif
                    </p>
                    <p class="mt-1 text-xs text-text-secondary">
                        {{ $lastClassCheckin->checked_in_at?->format('d/m/Y H:i') }}
                        @if ($lastClassCheckin->schoolClass?->unit)
                            &middot; {{ $lastClassCheckin->schoolClass->unit->name }}
                        @endif
                    </p>
                </div>
            @endif

            @if ($openClassBookings->isNotEmpty())
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-text-secondary">Aulas em aberto</p>
                    <div class="mt-2 space-y-2">
                        @foreach ($openClassBookings as $booking)
                            <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">{{ $booking->schoolClass?->name ?? 'Aula' }}</p>
                                        <p class="text-xs text-text-secondary">
                                            {{ $booking->booked_for?->format('d/m/Y') }}
                                            @if ($booking->schedule?->time_label)
                                                &middot; {{ $booking->schedule->time_label }}
                                            @endif
                                            @if ($booking->schoolClass?->unit)
                                                &middot; {{ $booking->schoolClass->unit->name }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="badge {{ $booking->status === \App\Models\ClassBooking::STATUS_CONFIRMED ? 'badge-success' : 'badge-warning' }} whitespace-nowrap">
                                        {{ $booking->status_label }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-text-secondary">Turmas de hoje</p>

                @if ($todayClasses->isEmpty())
                    <p class="mt-2 text-sm text-text-secondary">Nenhuma turma disponível hoje para os seus planos.</p>
                @else
                    <div class="mt-2 space-y-2">
                        @foreach ($todayClasses as $schedule)
                            @php($class = $schedule->schoolClass)
                            <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">{{ $class?->name ?? 'Aula' }}</p>
                                        <p class="text-xs text-text-secondary">
                                            {{ $class?->modality?->name ?? 'Sem modalidade' }}
                                            &middot; {{ $schedule->time_label }}
                                        </p>
                                        <p class="mt-1 text-xs text-text-secondary">
                                            <i class="fas fa-building mr-1"></i>
                                            {{ $class?->unit?->name ?? 'Unidade' }}
                                        </p>
                                    </div>
                                    <span class="badge badge-info whitespace-nowrap">{{ $schedule->vacancies }} vagas</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if ($activeSubscription)
        <section class="card">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl text-lg text-white shadow-lg"
                         style="background: linear-gradient(135deg, var(--color-warning), var(--color-accent));">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <h2 class="font-semibold">Ficha de avaliação</h2>
                        <p class="mt-1 text-sm text-text-secondary">
                            @if ($assessment?->isCompleted())
                                Alterada em {{ $assessment->submitted_at?->format('d/m/Y') }}. Você pode atualizar quando quiser.
                            @else
                                Preencha sua ficha para que a equipe monte um treino adequado ao seu objetivo.
                            @endif
                        </p>
                    </div>
                </div>

                <a href="{{ route('student.portal.assessment.show', $network) }}"
                   class="{{ $assessment?->isCompleted() ? 'btn-outline' : 'btn-primary' }} self-end sm:self-auto">
                    <i class="fas {{ $assessment?->isCompleted() ? 'fa-eye' : 'fa-clipboard-check' }} mr-2"></i>
                    {{ $assessment?->isCompleted() ? 'Ver ficha' : 'Preencher ficha' }}
                </a>
            </div>

            @if ($assessment?->isCompleted() && $assessmentMetrics)
                <div class="mt-4 divide-y divide-custom rounded-xl border border-custom bg-bg-secondary">
                    @foreach ($assessmentMetrics as $metric)
                        <div class="flex items-center justify-between gap-3 px-3 py-2.5">
                            <span class="font-semibold text-[19pt] text-text-secondary">{{ $metric['label'] }}</span>
                            <div class="min-w-0 text-right">
                                <span class="font-semibold">{{ $metric['value'] }}</span>
                                @if ($metric['percent_label'])
                                    <span class="block text-xs text-text-secondary">
                                        @if ($metric['direction'] === 'up')
                                            <i class="fas fa-arrow-up mr-1"></i>
                                        @elseif ($metric['direction'] === 'down')
                                            <i class="fas fa-arrow-down mr-1"></i>
                                        @endif
                                        {{ $metric['percent_label'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ([
            ['route' => 'student.portal.dashboard', 'label' => 'Dashboard', 'icon' => 'fa-chart-pie', 'color' => 'linear-gradient(135deg, var(--color-primary), var(--color-info))'],
            ['route' => 'student.portal.assessment.show', 'label' => 'Avaliação', 'icon' => 'fa-clipboard-check', 'color' => 'linear-gradient(135deg, var(--color-success), var(--color-info))'],
            ['route' => 'student.portal.classes.index', 'label' => 'Turmas', 'icon' => 'fa-calendar-days', 'color' => 'linear-gradient(135deg, var(--color-accent), var(--color-primary))'],
            ['route' => 'student.portal.finance.index', 'label' => 'Financeiro', 'icon' => 'fa-receipt', 'color' => 'linear-gradient(135deg, var(--color-info), var(--color-primary))'],
        ] as $shortcut)
            <a href="{{ route($shortcut['route'], $network) }}"
               class="card flex flex-col items-center gap-2 p-4 text-center transition-transform hover:-translate-y-0.5">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl text-white shadow-lg"
                      style="background: {{ $shortcut['color'] }};">
                    <i class="fas {{ $shortcut['icon'] }}"></i>
                </span>
                <span class="text-xs font-semibold">{{ $shortcut['label'] }}</span>
            </a>
        @endforeach
    </div>

    @if ($notifications->isNotEmpty())
        <section class="card space-y-3">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-bell text-cyan"></i>
                Avisos
            </h2>
            <div class="space-y-2">
                @foreach ($notifications as $notification)
                    <div class="rounded-lg border border-custom bg-bg-secondary px-3 py-2">
                        <p class="font-medium">{{ $notification->title }}</p>
                        <p class="mt-1 text-sm text-text-secondary">{{ $notification->message }}</p>
                        <p class="mt-1 text-xs text-text-secondary">{{ $notification->created_at?->format('d/m/Y H:i') }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if (! $activeSubscription)
        <div class="card overflow-hidden border-0"
             style="background: linear-gradient(135deg, color-mix(in srgb, var(--color-primary) 92%, #000 8%), color-mix(in srgb, var(--color-info) 60%, #000 40%));">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.24em] text-white/70">Experimente grátis</p>
                    <h2 class="mt-2 text-2xl font-black text-white">Faça seu check-in experimental</h2>
                    <p class="mt-2 max-w-xl text-sm leading-relaxed text-white/80">
                        Conheça a estrutura da sua unidade e treine um dia sem custo. O check-in experimental é válido uma única vez.
                    </p>
                </div>

                <div class="flex flex-shrink-0 flex-col items-start gap-2 sm:items-end">
                    @if ($trialUsed)
                        <span class="badge bg-white/15 text-white">Check-in experimental já utilizado</span>
                        <a href="{{ route('student.portal.plans', $network) }}" class="btn-outline border-white/40 text-white">
                            <i class="fas fa-ticket mr-2"></i>
                            Ver planos e diárias
                        </a>
                    @else
                        <form method="POST" action="{{ route('student.portal.checkin.trial', $network) }}">
                            @csrf
                            <button type="submit" class="btn-outline border-white/40 text-white">
                                <i class="fas fa-bolt mr-2"></i>
                                Quero experimentar
                            </button>
                        </form>
                        <a href="{{ route('student.portal.plans', $network) }}" class="text-xs font-medium text-white/80 underline-offset-2 hover:underline">
                            ou conheça os planos
                        </a>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>

<script id="nearestUnitsData" type="application/json">@json($nearestUnits)</script>
<script>
    (function () {
        const card = document.querySelector('[data-nearest-unit]');
        const data = document.getElementById('nearestUnitsData');

        if (!card || !data || !navigator.geolocation) {
            return;
        }

        const name = card.querySelector('[data-nearest-name]');
        const address = card.querySelector('[data-nearest-address]');
        const distance = card.querySelector('[data-nearest-distance]');

        let units = [];

        try {
            units = JSON.parse(data.textContent || '[]');
        } catch (error) {
            units = [];
        }

        units = units.filter((unit) => unit.latitude && unit.longitude);

        if (!units.length) {
            if (distance) {
                distance.textContent = 'A localização das unidades ainda não foi cadastrada.';
            }

            return;
        }

        function distanceInKm(lat1, lon1, lat2, lon2) {
            const earth = 6371;
            const toRad = (value) => value * Math.PI / 180;
            const dLat = toRad(lat2 - lat1);
            const dLon = toRad(lon2 - lon1);
            const a = Math.sin(dLat / 2) ** 2
                + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;

            return earth * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        navigator.geolocation.getCurrentPosition(function (position) {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;

            units.sort((a, b) => distanceInKm(latitude, longitude, a.latitude, a.longitude)
                - distanceInKm(latitude, longitude, b.latitude, b.longitude));

            const nearest = units[0];
            const km = distanceInKm(latitude, longitude, nearest.latitude, nearest.longitude);

            if (name) {
                name.textContent = nearest.name;
            }

            if (address) {
                address.textContent = nearest.address || 'Endereço não informado';
            }

            if (distance) {
                distance.textContent = 'A ' + km.toLocaleString('pt-BR', {
                    minimumFractionDigits: km < 10 ? 1 : 0,
                    maximumFractionDigits: 1,
                }) + ' km de você';
            }
        }, function () {
            if (distance) {
                distance.textContent = 'Não foi possível obter sua localização. Confira a unidade principal acima.';
            }
        }, {
            enableHighAccuracy: false,
            timeout: 8000,
            maximumAge: 600000,
        });
    })();
</script>
@endsection
