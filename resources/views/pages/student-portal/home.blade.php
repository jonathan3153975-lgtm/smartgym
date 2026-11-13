@extends('layouts.student')

@section('title', 'Home')

@section('content')
@php
    $student = $account->student;
    $primaryUnit = $student?->units?->firstWhere('is_primary') ?? $student?->units?->first();
@endphp

<div class="space-y-6">
    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                @if ($student?->avatar_url)
                    <img src="{{ $student->avatar_url }}" alt="{{ $student->name }}"
                         class="h-16 w-16 flex-shrink-0 rounded-full border border-custom object-cover">
                @else
                    <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full text-xl font-bold"
                         style="background: var(--color-info-soft); color: var(--color-info);">
                        {{ strtoupper(mb_substr($student?->name ?? 'A', 0, 1)) }}
                    </div>
                @endif
                <div class="min-w-0">
                    <p class="text-sm text-text-secondary">Bem-vindo(a) de volta</p>
                    <h1 class="truncate text-2xl font-bold">{{ $student?->name ?? 'Aluno' }}</h1>
                    <p class="truncate text-sm text-text-secondary">{{ $account->email }}</p>
                </div>
            </div>

            <span class="badge badge-success self-start sm:self-auto">Aluno ativo</span>
        </div>
    </div>

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

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="card">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl"
                     style="background: color-mix(in srgb, var(--color-info) 15%, transparent); color: var(--color-info);">
                    <i class="fas fa-building"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-text-secondary">Unidade principal</p>
                    <p class="truncate font-semibold">{{ $primaryUnit?->unit?->name ?? 'Não definida' }}</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl"
                     style="background: color-mix(in srgb, var(--color-success) 15%, transparent); color: var(--color-success);">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-text-secondary">Meu plano</p>
                    @if ($activeSubscription)
                        <p class="truncate font-semibold">{{ $activeSubscription->plan_name }}</p>
                        @if ($activeSubscription->ends_at)
                            <p class="text-xs text-text-secondary">
                                Válido até {{ $activeSubscription->ends_at->format('d/m/Y') }}
                            </p>
                        @endif
                    @else
                        <p class="truncate font-semibold">Nenhum plano ativo</p>
                        <a href="{{ route('student.portal.plans', $network) }}"
                           class="text-sm font-semibold text-cyan hover:underline">
                            Ver planos
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="card">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl"
                     style="background: color-mix(in srgb, var(--color-accent) 15%, transparent); color: var(--color-accent);">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-sm text-text-secondary">Dashboard</p>
                    <a href="{{ route('student.portal.dashboard', $network) }}"
                       class="text-sm font-semibold text-cyan hover:underline">
                        Acessar painel
                    </a>
                </div>
            </div>
        </div>

        @if ($activeSubscription)
            <div class="card">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl"
                         style="background: color-mix(in srgb, var(--color-info) 15%, transparent); color: var(--color-info);">
                        <i class="fas fa-dumbbell"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-text-secondary">Treinos</p>
                        <a href="{{ route('student.portal.workouts.index', $network) }}"
                           class="text-sm font-semibold text-cyan hover:underline">
                            Ver meus treinos
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl"
                         style="background: color-mix(in srgb, var(--color-success) 15%, transparent); color: var(--color-success);">
                        <i class="fas fa-weight-scale"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-text-secondary">Medidas</p>
                        <a href="{{ route('student.portal.measurements.index', $network) }}"
                           class="text-sm font-semibold text-cyan hover:underline">
                            Registrar medidas
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    @if ($activeSubscription)
        <div class="card">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl"
                         style="background: color-mix(in srgb, var(--color-warning) 15%, transparent); color: var(--color-warning);">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div>
                        <p class="font-semibold">Ficha de avaliação</p>
                        @if ($assessment?->isCompleted())
                            <p class="mt-1 text-sm text-text-secondary">
                                Enviada em {{ $assessment->submitted_at?->format('d/m/Y') }}. Você pode atualizar suas informações quando quiser.
                            </p>
                        @else
                            <p class="mt-1 text-sm text-text-secondary">
                                Preencha sua ficha de avaliação para que a equipe monte um treino adequado ao seu objetivo.
                            </p>
                        @endif
                    </div>
                </div>

                <a href="{{ route('student.portal.assessment.show', $network) }}"
                   class="{{ $assessment?->isCompleted() ? 'btn-outline' : 'btn-primary' }} self-start sm:self-auto">
                    <i class="fas {{ $assessment?->isCompleted() ? 'fa-eye' : 'fa-clipboard-check' }} mr-2"></i>
                    {{ $assessment?->isCompleted() ? 'Ver ficha' : 'Preencher ficha' }}
                </a>
            </div>
        </div>
    @endif

    <section class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <i class="fas fa-calendar-day text-cyan"></i>
                Turmas de hoje
            </h2>
            <a href="{{ route('student.portal.classes.index', $network) }}" class="text-sm font-semibold text-cyan hover:underline">
                Ver todas
            </a>
        </div>

        @if ($todayClasses->isEmpty())
            <div class="card py-6 text-center text-sm text-text-secondary">
                Nenhuma turma programada para hoje.
            </div>
        @else
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($todayClasses as $class)
                    <article class="card space-y-2">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <p class="font-semibold">{{ $class->name }}</p>
                                <p class="text-xs text-text-secondary">
                                    {{ $class->modality?->name ?? 'Sem modalidade' }}
                                    &middot; {{ $class->time_label }}
                                </p>
                            </div>
                            <span class="badge badge-info whitespace-nowrap">{{ $class->vacancies }} vagas</span>
                        </div>
                        <p class="text-xs text-text-secondary">
                            <i class="fas fa-building mr-1"></i>
                            {{ $class->unit?->name ?? 'Unidade' }}
                            @if ($class->unit?->city)
                                &middot; {{ $class->unit->city }}/{{ $class->unit->state }}
                            @endif
                        </p>
                        @if ($class->drop_in_price)
                            <p class="text-xs text-text-secondary">
                                <i class="fas fa-ticket mr-1"></i>
                                Aula avulsa: R$ {{ number_format((float) $class->drop_in_price, 2, ',', '.') }}
                            </p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <div class="card">
        <h2 class="mb-4 flex items-center gap-2 font-semibold">
            <i class="fas fa-circle-info text-cyan"></i>
            Meus dados
        </h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <p class="text-xs text-text-secondary">Nome</p>
                <p class="mt-1 font-medium">{{ $student?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-text-secondary">CPF</p>
                <p class="mt-1 font-medium">{{ $student?->cpf ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-text-secondary">E-mail</p>
                <p class="mt-1 font-medium">{{ $account->email }}</p>
            </div>
            <div>
                <p class="text-xs text-text-secondary">Telefone</p>
                <p class="mt-1 font-medium">{{ $student?->phone ?? $student?->whatsapp ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-text-secondary">Unidades</p>
                <p class="mt-1 font-medium">
                    @if ($student?->units?->isNotEmpty())
                        {{ $student->units->map(fn ($unit) => $unit->unit?->name)->filter()->implode(', ') }}
                    @else
                        —
                    @endif
                </p>
            </div>
            <div>
                <p class="text-xs text-text-secondary">Situação</p>
                <p class="mt-1 font-medium">{{ $student?->status_label ?? '—' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
