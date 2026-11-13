@extends('layouts.student')

@section('title', 'Minha avaliação')

@section('content')
<div class="mx-auto w-full max-w-4xl space-y-6">
    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Avaliação inicial</p>
                <h1 class="mt-1 text-2xl font-bold">Sua ficha de avaliação</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    Enviada em {{ $assessment->submitted_at?->format('d/m/Y H:i') ?? '—' }}.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 self-start">
                <span class="badge badge-success">Concluída</span>
                <span class="badge badge-info">{{ $totalAssessments }} avaliação(ões)</span>
                <a href="{{ route('student.portal.assessment.history', $network) }}" class="btn-outline">
                    <i class="fas fa-clock-rotate-left mr-2"></i>
                    Histórico
                </a>
                <a href="{{ route('student.portal.assessment.create', $network) }}" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>
                    Nova reavaliação
                </a>
            </div>
        </div>
    </div>

    @if ($comparison)
        <section class="card space-y-3" style="border-color: var(--color-info);">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-arrow-trend-up text-cyan"></i>
                    Comparação com a avaliação anterior
                </h2>
                <span class="badge badge-info">{{ $comparison['date']?->format('d/m/Y') }}</span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm lg:grid-cols-5">
                <div>
                    <p class="text-xs text-text-secondary">Peso</p>
                    <p class="mt-1 font-medium">
                        @if ($comparison['weight'] !== null)
                            {{ $comparison['weight'] > 0 ? '+' : '' }}{{ number_format($comparison['weight'], 2, ',', '.') }} kg
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">IMC</p>
                    <p class="mt-1 font-medium">
                        @if ($comparison['bmi'] !== null)
                            {{ $comparison['bmi'] > 0 ? '+' : '' }}{{ number_format($comparison['bmi'], 1, ',', '.') }}
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">Gordura</p>
                    <p class="mt-1 font-medium">
                        @if ($comparison['body_fat'] !== null)
                            {{ $comparison['body_fat'] > 0 ? '+' : '' }}{{ number_format($comparison['body_fat'], 1, ',', '.') }}%
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">Cintura</p>
                    <p class="mt-1 font-medium">
                        @if ($comparison['waist'] !== null)
                            {{ $comparison['waist'] > 0 ? '+' : '' }}{{ number_format($comparison['waist'], 2, ',', '.') }} cm
                        @else
                            —
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">PAR-Q</p>
                    <p class="mt-1 font-medium">
                        @if ($comparison['parq_changed'])
                            {{ $comparison['parq_positive'] ? 'Agora com alerta' : 'Alerta removido' }}
                        @else
                            Sem mudança
                        @endif
                    </p>
                </div>
            </div>
        </section>
    @endif

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

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <section class="card space-y-3">
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

        <section class="card space-y-3">
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

        <section class="card space-y-3 md:col-span-2">
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

        <section class="card space-y-3 md:col-span-2">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-ruler text-cyan"></i>
                Medidas corporais
            </h2>

            <div class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                <div>
                    <p class="text-text-secondary">Peso</p>
                    <p class="mt-1 font-medium">{{ $assessment->weight_kg ? $assessment->weight_kg.' kg' : '—' }}</p>
                </div>
                <div>
                    <p class="text-text-secondary">Altura</p>
                    <p class="mt-1 font-medium">{{ $assessment->height_cm ? $assessment->height_cm.' cm' : '—' }}</p>
                </div>
                <div>
                    <p class="text-text-secondary">IMC</p>
                    <p class="mt-1 font-medium">{{ $assessment->bmi ? number_format($assessment->bmi, 1, ',', '.').' kg/m²' : '—' }}</p>
                </div>
                <div>
                    <p class="text-text-secondary">Gordura</p>
                    <p class="mt-1 font-medium">{{ $assessment->body_fat_percent ? $assessment->body_fat_percent.'%' : '—' }}</p>
                </div>
                @foreach (['waist_cm' => 'Cintura', 'hip_cm' => 'Quadril', 'chest_cm' => 'Tórax', 'arm_cm' => 'Braço', 'thigh_cm' => 'Coxa'] as $field => $label)
                    <div>
                        <p class="text-text-secondary">{{ $label }}</p>
                        <p class="mt-1 font-medium">{{ $assessment->{$field} ? $assessment->{$field}.' cm' : '—' }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card space-y-3">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-address-book text-cyan"></i>
                Contato de emergência
            </h2>
            <p class="text-sm font-medium">{{ $assessment->emergency_contact_name ?: '—' }}</p>
            <p class="text-sm text-text-secondary">{{ $assessment->emergency_contact_phone ?: '—' }}</p>
        </section>

        <section class="card space-y-3">
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
