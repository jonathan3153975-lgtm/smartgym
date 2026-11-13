@extends('layouts.app')

@section('title', 'Avaliação - '.$student->name)

@section('content')
@php($assessment = $student->assessment)

<div class="container mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('students.show', $student) }}" class="text-sm text-text-secondary transition-colors hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para o aluno
            </a>
            <h1 class="mt-1 text-2xl font-bold">Ficha de avaliação</h1>
            <p class="mt-1 text-text-secondary">{{ $student->name }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($assessment?->isCompleted())
                <span class="badge badge-success">Concluída</span>
                @if ($assessment->reviewed_at)
                    <span class="badge badge-success">Verificada</span>
                @else
                    <span class="badge badge-warning">A verificar</span>
                @endif
                <button type="button" onclick="window.print()" class="btn-outline">
                    <i class="fas fa-print mr-2"></i>
                    Imprimir
                </button>
            @else
                <span class="badge badge-secondary">Não preenchida</span>
            @endif
        </div>

        @if ($assessment?->reviewed_at)
            <p class="mt-3 text-xs text-text-secondary">
                Verificada por {{ $assessment->reviewedBy?->name ?? 'equipe' }}
                em {{ $assessment->reviewed_at->format('d/m/Y H:i') }}.
            </p>
        @endif
    </div>

    @if (! $assessment?->isCompleted())
        <div class="card py-10 text-center">
            <i class="fas fa-clipboard-check mb-3 text-3xl text-text-secondary"></i>
            <p class="font-medium">Ficha de avaliação não preenchida</p>
            <p class="mt-1 text-sm text-text-secondary">
                O aluno poderá preencher a ficha no portal após assinar um plano.
            </p>
        </div>
    @else
        @if ($assessment->parq_positive)
            <div class="card" style="border-color: var(--color-warning);">
                <div class="flex items-start gap-3">
                    <i class="fas fa-triangle-exclamation mt-0.5 text-xl" style="color: var(--color-warning);"></i>
                    <div>
                        <p class="font-semibold">Atenção: respostas positivas no PAR-Q</p>
                        <p class="mt-1 text-sm text-text-secondary">
                            O aluno assinalou pelo menos uma condição no questionário de prontidão.
                            Recomenda-se orientação médica antes de iniciar ou intensificar o treino.
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
                        <dd class="text-right font-medium">{{ $assessment->training_frequency }}x</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-text-secondary">Enviada em</dt>
                        <dd class="text-right font-medium">{{ $assessment->submitted_at?->format('d/m/Y H:i') ?? '—' }}</dd>
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
                    Questionário de prontidão (PAR-Q)
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

        @if ($student->assessments->count() > 1)
            <section class="card space-y-3">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-clock-rotate-left text-cyan"></i>
                    Histórico de avaliações
                </h2>

                <div class="overflow-x-auto">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Objetivo</th>
                                <th>Experiência</th>
                                <th>Peso</th>
                                <th>IMC</th>
                                <th>PAR-Q</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($student->assessments as $historyAssessment)
                                <tr>
                                    <td class="font-medium">{{ $historyAssessment->submitted_at?->format('d/m/Y') ?? '—' }}</td>
                                    <td>{{ $historyAssessment->goal_label }}</td>
                                    <td>{{ $historyAssessment->experience_level_label }}</td>
                                    <td>{{ $historyAssessment->weight_kg ? $historyAssessment->weight_kg.' kg' : '—' }}</td>
                                    <td>{{ $historyAssessment->bmi ? number_format($historyAssessment->bmi, 1, ',', '.') : '—' }}</td>
                                    <td>
                                        @if ($historyAssessment->parq_positive)
                                            <span class="badge badge-warning">Alerta</span>
                                        @else
                                            <span class="badge badge-success">Sem alerta</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    @endif
</div>
@endsection
