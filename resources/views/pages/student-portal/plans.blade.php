@extends('layouts.student')

@section('title', 'Planos disponíveis')

@section('content')
@php
    $hasCoordinates = $units->contains(fn ($unit) => $unit->latitude !== null && $unit->longitude !== null);
@endphp

<div class="space-y-6">
    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Planos</p>
                <h1 class="mt-1 text-2xl font-bold">Escolha o plano ideal para você</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    @if ($studentUnit)
                        Unidade de interesse: <span class="font-medium text-text-primary">{{ $studentUnit->name }}</span>
                    @else
                        Selecione uma unidade para ver os planos disponíveis.
                    @endif
                </p>
            </div>

            @if ($activeSubscription)
                <span class="badge badge-success self-start sm:self-auto">Plano ativo</span>
            @else
                <span class="badge badge-warning self-start sm:self-auto">Nenhum plano ativo</span>
            @endif
        </div>
    </div>

    @if ($activeSubscription)
        <div class="card">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Seu plano</p>
                    <h2 class="mt-1 text-xl font-bold">{{ $activeSubscription->plan_name }}</h2>
                    <p class="mt-1 text-sm text-text-secondary">
                        {{ $activeSubscription->price_label }} &middot; {{ $activeSubscription->period_label }}
                        @if ($activeSubscription->ends_at)
                            &middot; válido até {{ $activeSubscription->ends_at->format('d/m/Y') }}
                        @endif
                    </p>
                </div>

                <a href="{{ route('student.portal.home', $network) }}" class="btn-primary self-start sm:self-auto">
                    <i class="fas fa-house mr-2"></i>
                    Ir para a home
                </a>
            </div>
        </div>
    @endif

    {{-- Plans --}}
    <section class="space-y-3">
        <h2 class="flex items-center gap-2 text-lg font-semibold">
            <i class="fas fa-layer-group text-cyan"></i>
            Planos disponíveis
        </h2>

        @if ($plans->isEmpty())
            <div class="card py-10 text-center">
                <i class="fas fa-layer-group mb-3 text-3xl text-text-secondary"></i>
                <p class="font-medium">Nenhum plano disponível no momento</p>
                <p class="mt-1 text-sm text-text-secondary">Fale com a recepção da sua unidade para mais informações.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($plans as $plan)
                    <article class="card flex flex-col gap-4" style="border-color: color-mix(in srgb, var(--color-primary) 35%, var(--color-border));">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-lg font-bold">{{ $plan->name }}</h3>
                                <span class="badge badge-secondary whitespace-nowrap">{{ $plan->period_label }}</span>
                            </div>

                            @if ($plan->description)
                                <p class="mt-2 text-sm text-text-secondary">{{ $plan->description }}</p>
                            @endif
                        </div>

                        <div>
                            <p class="text-2xl font-bold text-cyan">
                                @if ($plan->price !== null)
                                    R$ {{ number_format((float) $plan->price, 2, ',', '.') }}
                                @else
                                    Sob consulta
                                @endif
                            </p>
                            <p class="text-xs text-text-secondary">
                                {{ $plan->period === 'daily' ? 'válido por 1 dia' : 'a cada '.$plan->duration_days.' dias' }}
                            </p>
                        </div>

                        <ul class="space-y-2 text-sm text-text-secondary">
                            <li><i class="fas fa-building mr-2 w-4 text-center text-cyan"></i>{{ $plan->unit_access_label }}</li>
                            <li><i class="fas fa-dumbbell mr-2 w-4 text-center text-cyan"></i>{{ $plan->modality_access_label }}</li>
                            <li><i class="fas fa-calendar-check mr-2 w-4 text-center text-cyan"></i>{{ $plan->weekly_accesses }}x por semana</li>
                            <li><i class="fas fa-clock mr-2 w-4 text-center text-cyan"></i>{{ $plan->daily_checkins }} check-in(s) por dia</li>
                        </ul>

                        <div class="mt-auto space-y-2">
                            @if ($activeSubscription)
                                <button type="button" class="btn-outline w-full" disabled>
                                    <i class="fas fa-circle-check mr-2"></i>
                                    Você já possui um plano ativo
                                </button>
                            @elseif ($plan->price === null || (float) $plan->price <= 0)
                                <button type="button" class="btn-outline w-full" disabled>
                                    <i class="fas fa-headset mr-2"></i>
                                    Fale com a recepção
                                </button>
                                <p class="text-center text-xs text-text-secondary">
                                    Este plano não está disponível para assinatura online.
                                </p>
                            @else
                                <form method="POST" action="{{ route('student.portal.plans.checkout', [$network, $plan]) }}">
                                    @csrf
                                    <button type="submit" class="btn-primary w-full">
                                        <i class="fas fa-cart-plus mr-2"></i>
                                        Assinar plano
                                    </button>
                                </form>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Units --}}
    <section class="space-y-3">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <i class="fas fa-building text-cyan"></i>
                Unidades
            </h2>

            @if ($hasCoordinates)
                <button type="button" id="locateButton" class="btn-outline text-sm">
                    <i class="fas fa-location-crosshairs mr-2"></i>
                    Usar minha localização
                </button>
            @endif
        </div>

        <p id="locationStatus" class="text-xs text-text-secondary">
            @if ($hasCoordinates)
                Use sua localização para ver a distância de cada unidade.
            @else
                As unidades ainda não informaram suas coordenadas, por isso a distância não pode ser calculada.
            @endif
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @forelse ($units as $unit)
                <article class="card space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate font-semibold">{{ $unit->name }}</h3>
                            @if ($unit->region)
                                <p class="text-xs text-text-secondary">{{ $unit->region->name }}</p>
                            @endif
                        </div>

                        @if ($unit->latitude !== null && $unit->longitude !== null)
                            <span class="badge badge-secondary hidden whitespace-nowrap"
                                  data-unit-distance
                                  data-latitude="{{ $unit->latitude }}"
                                  data-longitude="{{ $unit->longitude }}">
                                —
                            </span>
                        @endif
                    </div>

                    <div class="space-y-1 text-sm text-text-secondary">
                        <p>
                            <i class="fas fa-map-marker-alt mr-2 w-4 text-center text-cyan"></i>
                            {{ $unit->full_address ?: 'Endereço não informado' }}
                        </p>
                        @if ($unit->phone)
                            <p><i class="fas fa-phone mr-2 w-4 text-center text-cyan"></i>{{ $unit->phone }}</p>
                        @endif
                        @if ($unit->email)
                            <p><i class="fas fa-envelope mr-2 w-4 text-center text-cyan"></i>{{ $unit->email }}</p>
                        @endif
                    </div>
                </article>
            @empty
                <div class="card py-8 text-center md:col-span-2">
                    <p class="font-medium">Nenhuma unidade disponível</p>
                </div>
            @endforelse
        </div>
    </section>

    {{-- Modalities with classes --}}
    <section class="space-y-3">
        <h2 class="flex items-center gap-2 text-lg font-semibold">
            <i class="fas fa-dumbbell text-cyan"></i>
            Modalidades com turmas
        </h2>

        @if ($classes->isEmpty())
            <div class="card py-10 text-center">
                <i class="fas fa-chalkboard mb-3 text-3xl text-text-secondary"></i>
                <p class="font-medium">Nenhuma turma cadastrada no momento</p>
                <p class="mt-1 text-sm text-text-secondary">Consulte a recepção da unidade para conhecer as modalidades.</p>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach ($classes as $modalityClasses)
                    @php($modality = $modalityClasses->first()->modality)
                    <article class="card space-y-3">
                        <div class="flex items-center gap-3">
                            @if ($modality?->image_url)
                                <img src="{{ $modality->image_url }}" alt="{{ $modality->name }}"
                                     class="h-12 w-12 rounded-xl border border-custom object-cover">
                            @else
                                <div class="flex h-12 w-12 items-center justify-center rounded-xl"
                                     style="background: var(--color-info-soft); color: var(--color-info);">
                                    <i class="fas fa-dumbbell"></i>
                                </div>
                            @endif
                            <h3 class="text-base font-semibold">{{ $modality?->name ?? 'Modalidade' }}</h3>
                        </div>

                        <ul class="divide-y divide-custom text-sm">
                            @foreach ($modalityClasses as $class)
                                <li class="py-2 first:pt-0 last:pb-0">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <span class="font-medium">{{ $class->name }}</span>
                                        <span class="badge badge-success">{{ $class->vacancies }} vagas</span>
                                    </div>
                                    <p class="mt-1 text-xs text-text-secondary">
                                        {{ $class->unit?->name ?? '—' }}
                                        &middot; {{ $class->weekday_label }}
                                        &middot; {{ $class->time_label }}
                                        @if ($class->teacher)
                                            &middot; Prof. {{ $class->teacher->name }}
                                        @endif
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</div>

<script>
    (function () {
        const locateButton = document.getElementById('locateButton');
        const locationStatus = document.getElementById('locationStatus');
        const distanceBadges = Array.from(document.querySelectorAll('[data-unit-distance]'));

        if (!locateButton || distanceBadges.length === 0) {
            return;
        }

        const toRadians = function (degrees) {
            return (degrees * Math.PI) / 180;
        };

        const distanceInKm = function (latitudeA, longitudeA, latitudeB, longitudeB) {
            const earthRadius = 6371;
            const deltaLatitude = toRadians(latitudeB - latitudeA);
            const deltaLongitude = toRadians(longitudeB - longitudeA);

            const a = Math.sin(deltaLatitude / 2) * Math.sin(deltaLatitude / 2)
                + Math.cos(toRadians(latitudeA)) * Math.cos(toRadians(latitudeB))
                * Math.sin(deltaLongitude / 2) * Math.sin(deltaLongitude / 2);

            return earthRadius * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
        };

        const formatDistance = function (distance) {
            if (distance < 1) {
                return Math.round(distance * 1000) + ' m';
            }

            return distance.toFixed(1).replace('.', ',') + ' km';
        };

        locateButton.addEventListener('click', function () {
            if (!('geolocation' in navigator)) {
                locationStatus.textContent = 'Seu dispositivo não permite obter a localização.';
                return;
            }

            locationStatus.textContent = 'Obtendo sua localização...';
            locateButton.disabled = true;

            navigator.geolocation.getCurrentPosition(function (position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;

                distanceBadges.forEach(function (badge) {
                    const distance = distanceInKm(
                        latitude,
                        longitude,
                        parseFloat(badge.dataset.latitude),
                        parseFloat(badge.dataset.longitude)
                    );

                    badge.textContent = formatDistance(distance);
                    badge.classList.remove('hidden');
                });

                locationStatus.textContent = 'Distâncias calculadas a partir da sua localização atual.';
                locateButton.disabled = false;
            }, function () {
                locationStatus.textContent = 'Não foi possível obter sua localização. Verifique as permissões do navegador.';
                locateButton.disabled = false;
            }, {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 300000,
            });
        });
    })();
</script>
@endsection
