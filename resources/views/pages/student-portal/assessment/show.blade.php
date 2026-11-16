@extends('layouts.student')

@section('title', 'Minha avaliação')

@section('content')
<div class="mx-auto w-full max-w-4xl space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Ficha de avaliação</h1>
            <p class="mt-1 text-sm text-text-secondary">
                Última avaliação em {{ $assessment->submitted_at?->format('d/m/Y H:i') ?? '—' }}
            </p>
        </div>

        <div class="flex items-center gap-2 self-end sm:self-auto">
            <a href="{{ route('student.portal.assessment.history', $network) }}" class="btn-outline p-2"
               title="Histórico de avaliações" aria-label="Histórico de avaliações">
                <i class="fas fa-clock-rotate-left"></i>
            </a>
            <a href="{{ route('student.portal.assessment.create', $network) }}" class="btn-primary p-2"
               title="Nova avaliação" aria-label="Nova avaliação">
                <i class="fas fa-plus"></i>
            </a>
        </div>
    </div>

    <section class="-mx-2 card card-mobile-tight space-y-4 sm:mx-0">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="font-semibold">Última avaliação</h2>
                <p class="text-xs text-text-secondary">
                    Alterada em {{ $assessment->submitted_at?->format('d/m/Y H:i') ?? '—' }}
                </p>
            </div>
            <div class="flex flex-wrap gap-1">
                <span class="badge badge-success">Concluída</span>
                @if ($assessment->reviewed_at)
                    <span class="badge badge-success">Verificada</span>
                @else
                    <span class="badge badge-warning">A verificar</span>
                @endif
            </div>
        </div>

        <div class="divide-y divide-custom rounded-xl border border-custom bg-bg-secondary">
            @foreach ($metrics as $metric)
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
    </section>

    @if ($assessment->parq_positive)
        <div class="card" style="border-color: var(--color-warning);">
            <div class="flex items-start gap-3">
                <i class="fas fa-triangle-exclamation mt-0.5 text-xl" style="color: var(--color-warning);"></i>
                <div>
                    <p class="font-semibold">Atenção</p>
                    <p class="mt-1 text-sm text-text-secondary">
                        Você respondeu "sim" a pelo menos uma pergunta do questionário de prontidão.
                        Procure orientação médica antes de iniciar ou intensificar seus treinos e converse com a equipe da unidade.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="-mx-2 grid grid-cols-1 gap-6 sm:mx-0 md:grid-cols-2">
        <section class="card card-mobile-tight space-y-3">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-bullseye text-cyan"></i>
                Objetivo e rotina
            </h2>

            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-text-secondary">Objetivo</dt>
                    <dd class="text-right font-medium">{{ $assessment->goal_label }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-text-secondary">Experiência</dt>
                    <dd class="text-right font-medium">{{ $assessment->experience_level_label }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-text-secondary">Frequência semanal</dt>
                    <dd class="text-right font-medium">{{ $assessment->training_frequency }}x por semana</dd>
                </div>
                @if ($assessment->goal_notes)
                    <div>
                        <dt class="text-text-secondary">Detalhes</dt>
                        <dd class="mt-1 whitespace-pre-line font-medium">{{ $assessment->goal_notes }}</dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="card card-mobile-tight space-y-3">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-heart-pulse text-cyan"></i>
                Histórico de saúde
            </h2>

            <dl class="space-y-2 text-sm">
                <div>
                    <dt class="text-text-secondary">Condições de saúde</dt>
                    <dd class="mt-1 font-medium">{{ $assessment->medical_conditions ?: 'Nenhuma informada' }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary">Medicamentos</dt>
                    <dd class="mt-1 font-medium">{{ $assessment->medications ?: 'Nenhum informado' }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary">Lesões</dt>
                    <dd class="mt-1 font-medium">{{ $assessment->injuries ?: 'Nenhuma informada' }}</dd>
                </div>
                <div>
                    <dt class="text-text-secondary">Cirurgias</dt>
                    <dd class="mt-1 font-medium">{{ $assessment->surgeries ?: 'Nenhuma informada' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-text-secondary">Fumante</dt>
                    <dd class="text-right font-medium">{{ $assessment->smoker ? 'Sim' : 'Não' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-text-secondary">Álcool</dt>
                    <dd class="text-right font-medium">{{ $assessment->alcohol_consumption_label }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-text-secondary">Sono</dt>
                    <dd class="text-right font-medium">{{ $assessment->sleep_hours ? $assessment->sleep_hours.' h/noite' : '—' }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-text-secondary">Estresse</dt>
                    <dd class="text-right font-medium">{{ $assessment->stress_level ? $assessment->stress_level.'/5' : '—' }}</dd>
                </div>
            </dl>
        </section>

        <section class="card card-mobile-tight space-y-3 md:col-span-2">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-clipboard-question text-cyan"></i>
                Questionário de prontidão
            </h2>

            <ul class="space-y-2 text-sm">
                @foreach (\App\Models\StudentAssessment::PARQ_QUESTIONS as $key => $question)
                    @php($positive = (bool) ($assessment->parq_answers[$key] ?? false))
                    <li class="flex items-start justify-between gap-3 rounded-lg bg-bg-secondary px-3 py-2">
                        <span class="text-text-secondary">{{ $question }}</span>
                        <span class="badge {{ $positive ? 'badge-warning' : 'badge-success' }} whitespace-nowrap">
                            {{ $positive ? 'Sim' : 'Não' }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="card card-mobile-tight space-y-3">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-address-book text-cyan"></i>
                Contato de emergência
            </h2>
            <p class="text-sm font-medium">{{ $assessment->emergency_contact_name ?: '—' }}</p>
            <p class="text-sm text-text-secondary">{{ $assessment->emergency_contact_phone ?: '—' }}</p>
        </section>

        <section class="card card-mobile-tight space-y-3">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-notes-medical text-cyan"></i>
                Observações
            </h2>
            <p class="whitespace-pre-line text-sm text-text-secondary">{{ $assessment->observations ?: 'Nenhuma observação.' }}</p>
        </section>
    </div>

    <div class="flex justify-center">
        <a href="{{ route('student.portal.home', $network) }}" class="btn-outline">
            <i class="fas fa-house mr-2"></i>
            Voltar para a home
        </a>
    </div>
</div>
@endsection
