@extends('layouts.app')

@section('title', 'Ficha técnica - '.$student->name)

@section('content')
@php
    $currentAssessment = $bodySummary['current'];
    $previousAssessment = $bodySummary['previous'];
    $cards = $bodySummary['cards'];

    $palette = [
        'var(--color-primary)',
        'var(--color-info)',
        'var(--color-success)',
        'var(--color-warning)',
        'var(--color-danger)',
        'var(--color-accent)',
        'var(--color-purple-neon)',
    ];
    $circumference = 2 * M_PI * 42;
    $totalPercent = max(1, array_sum(array_column($muscleGroups, 'percent')));
    $donutOffset = 0;
    $donutSegments = [];

    foreach ($muscleGroups as $index => $group) {
        $length = ($group['percent'] / $totalPercent) * $circumference;
        $donutSegments[] = [
            'group' => $group,
            'color' => $palette[$index % count($palette)],
            'length' => $length,
            'offset' => $donutOffset,
        ];
        $donutOffset += $length;
    }
@endphp

<div class="space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('students.show', $student) }}" class="text-sm text-text-secondary transition-colors hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para o aluno
            </a>
            <h1 class="mt-1 text-2xl font-bold">Ficha técnica</h1>
            <p class="mt-1 text-text-secondary">{{ $student->name }}</p>
        </div>

        <div class="flex flex-col items-start gap-2 sm:items-end">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('students.assessments.create', $student) }}" class="btn-primary">
                    <i class="fas fa-plus mr-2"></i>
                    Nova avaliação
                </a>
                <a href="{{ route('students.technical-sheet.print', $student) }}" target="_blank" rel="noopener" class="btn-outline">
                    <i class="fas fa-print mr-2"></i>
                    Imprimir
                </a>
                @if ($currentAssessment?->isCompleted() && auth()->user()?->canVerifyAssessments())
                    <a href="{{ route('students.assessments.verify', [$student, $currentAssessment]) }}" class="btn-outline">
                        <i class="fas fa-user-check mr-2"></i>
                        {{ $currentAssessment->reviewed_at ? 'Revalidar avaliação' : 'Validar avaliação' }}
                    </a>
                @endif
            </div>

            <div class="flex flex-wrap gap-2 sm:justify-end">
                @if ($currentAssessment?->isCompleted())
                    <span class="badge badge-success">Concluída</span>
                    @if ($currentAssessment->reviewed_at)
                        <span class="badge badge-success">Verificada</span>
                    @else
                        <span class="badge badge-warning">A verificar</span>
                    @endif
                @elseif ($currentAssessment)
                    <span class="badge badge-warning">Em preenchimento</span>
                @else
                    <span class="badge badge-secondary">Não preenchida</span>
                @endif
            </div>
        </div>
    </div>

    @include('pages.students._tabs', ['student' => $student, 'activeTab' => 'technical'])

    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div class="card">
            <p class="text-sm text-text-secondary">Peso atual</p>
            <p class="mt-1 text-2xl font-bold">{{ $cards['weight']['value'] }}</p>
            <p class="mt-1 text-xs text-text-secondary">{{ $cards['weight']['meta'] }}</p>
        </div>

        <div class="card">
            <p class="text-sm text-text-secondary">Variação de peso</p>
            @php
                $weightVariation = $cards['weight']['variation'];
                $weightVariationUp = $weightVariation !== null && str_starts_with($weightVariation, '+');
            @endphp
            <p class="mt-1 text-2xl font-bold {{ $weightVariation === null || $weightVariation === 'Sem alteração' ? '' : ($weightVariationUp ? 'text-red' : 'text-green') }}">
                {{ $weightVariation ?? '—' }}
            </p>
            @if ($cards['weight']['variation_percent'])
                <p class="mt-1 text-xs {{ $weightVariationUp ? 'text-red' : 'text-green' }} opacity-80">
                    {{ $cards['weight']['variation_percent'] }}
                </p>
            @else
                <p class="mt-1 text-xs text-text-secondary">sem comparação anterior</p>
            @endif
        </div>

        <div class="card">
            <p class="text-sm text-text-secondary">IMC</p>
            <p class="mt-1 text-2xl font-bold">{{ $cards['bmi']['value'] }}</p>
            @if ($cards['bmi']['comparison'])
                <p class="mt-1 text-xs text-text-secondary">{{ $cards['bmi']['comparison'] }}</p>
            @endif
            <p class="mt-1 text-xs text-text-secondary">{{ $cards['bmi']['meta'] }}</p>
        </div>

        <div class="card">
            <p class="text-sm text-text-secondary">Gordura / cintura</p>
            <p class="mt-1 text-2xl font-bold">{{ $cards['fat_waist']['value'] }}</p>
            @if ($cards['fat_waist']['comparison'])
                <p class="mt-1 text-xs text-text-secondary">{{ $cards['fat_waist']['comparison'] }}</p>
            @endif
            <p class="mt-1 text-xs text-text-secondary">{{ $cards['fat_waist']['meta'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="space-y-6">
            <section class="card space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <i class="fas fa-bullseye text-cyan"></i>
                        Objetivo e rotina
                    </h2>
                    @if ($currentAssessment?->isCompleted())
                        <button type="button" onclick="openAssessmentDetailsModal()" class="btn-outline self-start">
                            <i class="fas fa-eye mr-2"></i>
                            Detalhes
                        </button>
                    @endif
                </div>

                @if ($currentAssessment?->isCompleted())
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-text-secondary">Objetivo</dt>
                            <dd class="text-right font-medium">{{ $currentAssessment->goal_label }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-text-secondary">Experiência</dt>
                            <dd class="text-right font-medium">{{ $currentAssessment->experience_level_label }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-text-secondary">Frequência semanal</dt>
                            <dd class="text-right font-medium">{{ $currentAssessment->training_frequency }}x</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-text-secondary">Enviada em</dt>
                            <dd class="text-right font-medium">{{ $currentAssessment->submitted_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-3">
                            <dt class="text-text-secondary">Prontidão (PAR-Q)</dt>
                            <dd class="text-right">
                                @if ($currentAssessment->parq_positive)
                                    <span class="badge badge-warning">Atenção</span>
                                @else
                                    <span class="badge badge-success">Sem alertas</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if ($currentAssessment->goal_notes)
                        <div class="rounded-lg border border-custom bg-bg-secondary p-3">
                            <p class="text-xs font-medium uppercase tracking-wider text-text-secondary">Detalhes do objetivo</p>
                            <p class="mt-1 whitespace-pre-line text-sm">{{ $currentAssessment->goal_notes }}</p>
                        </div>
                    @endif
                @else
                    <p class="text-sm text-text-secondary">O aluno ainda não preencheu a ficha de avaliação.</p>
                @endif
            </section>

            <section class="card space-y-4">
                <div class="flex flex-col gap-1">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <i class="fas fa-weight-scale text-cyan"></i>
                        Medidas corporais
                    </h2>
                    <p class="text-xs text-text-secondary">
                        @if ($currentAssessment?->submitted_at)
                            Última avaliação em {{ $currentAssessment->submitted_at->format('d/m/Y') }}.
                        @else
                            Nenhuma avaliação registrada.
                        @endif
                        @if ($previousAssessment)
                            Comparativos em relação à avaliação anterior.
                        @endif
                    </p>
                </div>

                @if ($assessmentMetrics !== [])
                    <div class="overflow-x-auto">
                        <table class="table-custom">
                            <thead>
                                <tr>
                                    <th>Medida</th>
                                    <th class="text-right">Atual</th>
                                    <th class="text-right">Variação</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($assessmentMetrics as $metric)
                                    <tr>
                                        <td class="font-medium">{{ $metric['label'] }}</td>
                                        <td class="text-right font-semibold">{{ $metric['value'] }}</td>
                                        <td class="text-right text-sm">
                                            @if ($metric['percent_label'])
                                                <span class="text-text-secondary">
                                                    @if ($metric['direction'] === 'up')
                                                        <i class="fas fa-arrow-up mr-1"></i>
                                                    @elseif ($metric['direction'] === 'down')
                                                        <i class="fas fa-arrow-down mr-1"></i>
                                                    @endif
                                                    {{ $metric['percent_label'] }}
                                                </span>
                                            @else
                                                <span class="text-text-secondary">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-text-secondary">Nenhuma medida registrada na última avaliação.</p>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            <section class="card space-y-4">
                <div class="flex flex-col gap-1">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <i class="fas fa-chart-pie text-cyan"></i>
                        Grupos musculares
                    </h2>
                    <p class="text-xs text-text-secondary">Proporção das repetições registradas nos treinos concluídos.</p>
                </div>

                @if ($muscleGroups === [])
                    <p class="text-sm text-text-secondary">Sem séries registradas ainda.</p>
                @else
                    <div class="relative mx-auto h-52 w-52">
                        <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90" role="img" aria-label="Distribuição por grupos musculares">
                            <circle cx="50" cy="50" r="42" fill="none" stroke="var(--color-border)" stroke-width="16"></circle>
                            @foreach ($donutSegments as $segment)
                                <circle cx="50" cy="50" r="42" fill="none"
                                        stroke="{{ $segment['color'] }}"
                                        stroke-width="16"
                                        stroke-dasharray="{{ $segment['length'] }} {{ $circumference - $segment['length'] }}"
                                        stroke-dashoffset="{{ -$segment['offset'] }}"
                                        class="cursor-pointer transition-opacity hover:opacity-75"
                                        onclick="openTechnicalMuscleGroupModal(@js($segment['group']['group']))">
                                    <title>{{ $segment['group']['label'] }}: {{ $segment['group']['percent'] }}%</title>
                                </circle>
                            @endforeach
                        </svg>
                        <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <p class="text-2xl font-bold">{{ count($muscleGroups) }}</p>
                            <p class="text-[0.65rem] uppercase tracking-wider text-text-secondary">grupos</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        @foreach ($donutSegments as $segment)
                            <button type="button" onclick="openTechnicalMuscleGroupModal(@js($segment['group']['group']))"
                                    class="interactive-item flex w-full cursor-pointer items-center justify-between gap-2 rounded-lg border border-custom bg-bg-secondary px-3 py-2 text-left text-sm transition-colors hover:border-cyan">
                                <span class="flex min-w-0 items-center gap-2">
                                    <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full" style="background: {{ $segment['color'] }};"></span>
                                    <span class="truncate">{{ $segment['group']['label'] }}</span>
                                </span>
                                <span class="flex-shrink-0 text-text-secondary">{{ $segment['group']['percent'] }}%</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="card space-y-4">
                <div class="flex flex-col gap-1">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <i class="fas fa-calendar-week text-cyan"></i>
                        Frequência semanal
                    </h2>
                    <p class="text-xs text-text-secondary">Últimas 4 semanas.</p>
                </div>

                <div class="flex items-end justify-between gap-3">
                    <div>
                        <p class="text-3xl font-bold">{{ number_format($frequencySummary['average'], 1, ',', '.') }}</p>
                        <p class="text-xs text-text-secondary">treinos por semana, em média</p>
                    </div>
                    <span class="badge badge-info">{{ $frequencySummary['total'] }} treinos em 4 semanas</span>
                </div>

                <div class="grid grid-cols-4 gap-2">
                    @foreach ($weeklyFrequency as $week)
                        <div class="flex flex-col items-center gap-1">
                            <div class="flex h-28 w-full items-end overflow-hidden rounded-lg bg-bg-secondary">
                                <div class="animate-column w-full rounded-lg" style="height: {{ max(4, $week['percent']) }}%; background: var(--color-primary);"></div>
                            </div>
                            <span class="text-sm font-semibold">{{ $week['count'] }}</span>
                            <span class="text-center text-[0.62rem] leading-tight text-text-secondary">{{ $week['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <section class="card space-y-4">
        <div class="flex flex-col gap-1">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-clock-rotate-left text-cyan"></i>
                Treinos das últimas 4 semanas
            </h2>
            <p class="text-xs text-text-secondary">Abra o extrato para ver exercícios, séries, cargas e o que não foi executado.</p>
        </div>

        @if ($lastFourWeeksLogs->isEmpty())
            <p class="text-sm text-text-secondary">Nenhum treino concluído nas últimas 4 semanas.</p>
        @else
            <div class="space-y-2">
                @foreach ($lastFourWeeksLogs as $log)
                    <div class="flex flex-col gap-2 rounded-xl border border-custom bg-bg-secondary p-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium">{{ $log->session_name }}</p>
                            <p class="text-xs text-text-secondary">
                                {{ $log->started_at?->format('d/m/Y H:i') ?? '—' }}
                                &middot; {{ $log->duration_label }}
                                &middot; {{ $log->sets->count() }} séries
                                &middot; {{ number_format($log->total_volume, 0, ',', '.') }} kg
                            </p>
                        </div>
                        <button type="button" onclick="openWorkoutExtractModal({{ $log->id }})" class="btn-outline self-start sm:self-auto">
                            <i class="fas fa-eye mr-2"></i>
                            Extrato do treino
                        </button>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="card space-y-4">
        <div class="flex flex-col gap-1">
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-clipboard-list text-cyan"></i>
                Histórico de avaliações
            </h2>
            <p class="text-xs text-text-secondary">Todas as avaliações registradas para o aluno.</p>
        </div>

        @if ($assessmentHistory->isEmpty())
            <p class="text-sm text-text-secondary">Nenhuma avaliação registrada.</p>
        @else
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
                            <th>Status</th>
                            <th class="text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($assessmentHistory as $historyAssessment)
                            <tr>
                                <td class="font-medium">{{ $historyAssessment->submitted_at?->format('d/m/Y') ?? '—' }}</td>
                                <td>{{ $historyAssessment->goal_label }}</td>
                                <td>{{ $historyAssessment->experience_level_label }}</td>
                                <td>{{ $historyAssessment->weight_kg ? number_format((float) $historyAssessment->weight_kg, 2, ',', '.').' kg' : '—' }}</td>
                                <td>{{ $historyAssessment->bmi ? number_format($historyAssessment->bmi, 1, ',', '.') : '—' }}</td>
                                <td>
                                    @if ($historyAssessment->parq_positive)
                                        <span class="badge badge-warning">Alerta</span>
                                    @else
                                        <span class="badge badge-success">Sem alerta</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($historyAssessment->reviewed_at)
                                        <span class="badge badge-success">Verificada</span>
                                    @else
                                        <span class="badge badge-warning">A verificar</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    @if (auth()->user()?->canVerifyAssessments())
                                        <a href="{{ route('students.assessments.verify', [$student, $historyAssessment]) }}"
                                           class="inline-flex rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-cyan"
                                           title="{{ $historyAssessment->reviewed_at ? 'Revalidar avaliação' : 'Validar avaliação' }}">
                                            <i class="fas fa-user-check"></i>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>

@if ($currentAssessment?->isCompleted())
    <div id="assessmentDetailsModal" class="modal-overlay hidden" role="dialog" aria-modal="true"
         aria-labelledby="assessmentDetailsTitle">
        <div class="modal-content modal-content-lg flex max-h-[88vh] flex-col">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="assessmentDetailsTitle" class="text-xl font-bold">Detalhes da avaliação</h2>
                    <p class="mt-1 text-xs text-text-secondary">
                        Enviada em {{ $currentAssessment->submitted_at?->format('d/m/Y H:i') ?? '—' }}
                    </p>
                </div>
                <button type="button" onclick="closeAssessmentDetailsModal()"
                        class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto pr-1">
                <section class="rounded-xl border border-custom p-3">
                    <h3 class="text-sm font-semibold">Objetivo e rotina</h3>
                    <dl class="mt-2 space-y-2 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-text-secondary">Objetivo</dt>
                            <dd class="text-right font-medium">{{ $currentAssessment->goal_label }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-text-secondary">Experiência</dt>
                            <dd class="text-right font-medium">{{ $currentAssessment->experience_level_label }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-text-secondary">Frequência semanal</dt>
                            <dd class="text-right font-medium">{{ $currentAssessment->training_frequency }}x</dd>
                        </div>
                        @if ($currentAssessment->goal_notes)
                            <div>
                                <dt class="text-text-secondary">Detalhes</dt>
                                <dd class="mt-1 whitespace-pre-line font-medium">{{ $currentAssessment->goal_notes }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                <section class="rounded-xl border border-custom p-3">
                    <h3 class="text-sm font-semibold">Histórico de saúde</h3>
                    <dl class="mt-2 space-y-2 text-sm">
                        <div><dt class="text-text-secondary">Condições de saúde</dt><dd class="mt-1 font-medium">{{ $currentAssessment->medical_conditions ?: 'Nenhuma informada' }}</dd></div>
                        <div><dt class="text-text-secondary">Medicamentos</dt><dd class="mt-1 font-medium">{{ $currentAssessment->medications ?: 'Nenhum informado' }}</dd></div>
                        <div><dt class="text-text-secondary">Lesões</dt><dd class="mt-1 font-medium">{{ $currentAssessment->injuries ?: 'Nenhuma informada' }}</dd></div>
                        <div><dt class="text-text-secondary">Cirurgias</dt><dd class="mt-1 font-medium">{{ $currentAssessment->surgeries ?: 'Nenhuma informada' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-secondary">Fumante</dt><dd class="text-right font-medium">{{ $currentAssessment->smoker ? 'Sim' : 'Não' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-secondary">Álcool</dt><dd class="text-right font-medium">{{ $currentAssessment->alcohol_consumption_label }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-secondary">Sono</dt><dd class="text-right font-medium">{{ $currentAssessment->sleep_hours ? $currentAssessment->sleep_hours.' h/noite' : '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-text-secondary">Estresse</dt><dd class="text-right font-medium">{{ $currentAssessment->stress_level ? $currentAssessment->stress_level.'/5' : '—' }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-xl border border-custom p-3">
                    <h3 class="text-sm font-semibold">Questionário de prontidão (PAR-Q)</h3>
                    <ul class="mt-2 space-y-2 text-sm">
                        @foreach (\App\Models\StudentAssessment::PARQ_QUESTIONS as $key => $question)
                            @php($positive = filter_var($currentAssessment->parq_answers[$key] ?? false, FILTER_VALIDATE_BOOL))
                            <li class="flex items-start justify-between gap-3 rounded-lg bg-bg-secondary px-3 py-2">
                                <span class="text-text-secondary">{{ $question }}</span>
                                <span class="badge {{ $positive ? 'badge-warning' : 'badge-success' }} whitespace-nowrap">
                                    {{ $positive ? 'Sim' : 'Não' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="rounded-xl border border-custom p-3">
                    <h3 class="text-sm font-semibold">Medidas corporais</h3>
                    <div class="mt-2 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                        @foreach ($assessmentMetrics as $metric)
                            <div>
                                <p class="text-xs text-text-secondary">{{ $metric['label'] }}</p>
                                <p class="mt-1 font-medium">{{ $metric['value'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-xl border border-custom p-3">
                    <h3 class="text-sm font-semibold">Contato de emergência</h3>
                    <p class="mt-2 text-sm font-medium">{{ $currentAssessment->emergency_contact_name ?: '—' }}</p>
                    <p class="text-sm text-text-secondary">{{ $currentAssessment->emergency_contact_phone ?: '—' }}</p>
                </section>

                <section class="rounded-xl border border-custom p-3">
                    <h3 class="text-sm font-semibold">Observações</h3>
                    <p class="mt-2 whitespace-pre-line text-sm text-text-secondary">{{ $currentAssessment->observations ?: 'Nenhuma observação.' }}</p>
                    @if ($currentAssessment->trainer_notes)
                        <p class="mt-3 text-xs font-medium uppercase tracking-wider text-text-secondary">Observação do treinador</p>
                        <p class="mt-1 whitespace-pre-line text-sm text-text-secondary">{{ $currentAssessment->trainer_notes }}</p>
                    @endif
                </section>
            </div>

            <div class="mt-3 flex justify-end border-t border-custom pt-3">
                <button type="button" onclick="closeAssessmentDetailsModal()" class="btn-outline">Fechar</button>
            </div>
        </div>
    </div>
@endif

@foreach ($muscleGroups as $group)
    <div id="technicalMuscleGroupModal-{{ $group['group'] }}" data-technical-muscle-group-modal="{{ $group['group'] }}"
         class="modal-overlay hidden" role="dialog" aria-modal="true"
         aria-labelledby="technicalMuscleGroupTitle-{{ $group['group'] }}">
        <div class="modal-content flex max-h-[85vh] flex-col">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="technicalMuscleGroupTitle-{{ $group['group'] }}" class="text-xl font-bold">{{ $group['label'] }}</h2>
                    <p class="mt-1 text-xs text-text-secondary">{{ $group['percent'] }}% das repetições registradas</p>
                </div>
                <button type="button" onclick="closeTechnicalMuscleGroupModal(@js($group['group']))"
                        class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                @forelse ($muscleGroupExercises[$group['group']] ?? [] as $exercise)
                    <div class="rounded-xl border border-custom bg-bg-secondary p-2.5">
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="truncate font-medium">{{ $exercise['name'] }}</span>
                            <span class="shrink-0 text-text-secondary">{{ $exercise['max_weight_label'] }} &middot; {{ $exercise['percent'] }}%</span>
                        </div>
                        <div class="progress mt-1 h-2">
                            <div class="progress-bar" role="progressbar"
                                 style="width: {{ max(4, $exercise['percent']) }}%;"
                                 aria-valuenow="{{ $exercise['percent'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-text-secondary">Nenhum exercício registrado para este grupo.</p>
                @endforelse
            </div>

            <div class="mt-3 flex justify-end border-t border-custom pt-3">
                <button type="button" onclick="closeTechnicalMuscleGroupModal(@js($group['group']))" class="btn-outline">Fechar</button>
            </div>
        </div>
    </div>
@endforeach

@foreach ($lastFourWeeksLogs as $log)
    @php($extract = $workoutExtracts[$log->id] ?? null)
    <div id="workoutExtractModal-{{ $log->id }}" data-workout-extract-modal="{{ $log->id }}"
         class="modal-overlay hidden" role="dialog" aria-modal="true"
         aria-labelledby="workoutExtractTitle-{{ $log->id }}">
        <div class="modal-content modal-content-lg flex max-h-[88vh] flex-col">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="workoutExtractTitle-{{ $log->id }}" class="text-xl font-bold">{{ $extract['name'] ?? $log->session_name }}</h2>
                    <p class="mt-1 text-xs text-text-secondary">
                        {{ $extract['date'] ?? '—' }}
                        &middot; {{ $extract['duration'] ?? $log->duration_label }}
                        &middot; {{ $extract['volume'] ?? number_format($log->total_volume, 2, ',', '.').' kg' }}
                    </p>
                </div>
                <button type="button" onclick="closeWorkoutExtractModal({{ $log->id }})"
                        class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                @forelse ($extract['exercises'] ?? [] as $exercise)
                    <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                        <div class="flex items-start gap-3">
                            @if ($exercise['image_url'])
                                <img src="{{ $exercise['image_url'] }}" alt="{{ $exercise['name'] }}"
                                     class="h-12 w-12 flex-shrink-0 rounded-lg border border-custom object-cover">
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-medium">{{ $exercise['name'] }}</p>
                                    @unless ($exercise['executed'])
                                        <span class="badge badge-warning">Não executado</span>
                                    @endunless
                                </div>
                                <p class="text-xs text-text-secondary">{{ $exercise['muscle_group'] }}</p>
                                @if ($exercise['planned_sets'])
                                    <p class="mt-1 text-xs text-text-secondary">
                                        Planejado: {{ $exercise['planned_sets'] }} séries × {{ $exercise['planned_reps'] }} reps
                                        @if ($exercise['planned_load'])
                                            &middot; carga {{ $exercise['planned_load'] }}
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>

                        @if ($exercise['sets'] !== [])
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                @foreach ($exercise['sets'] as $set)
                                    <span class="badge badge-secondary">
                                        {{ $set['number'] }}ª ·
                                        @if ($set['cardio_label'])
                                            {{ $set['cardio_label'] }}
                                            @if ($set['distance_label'])
                                                &middot; {{ $set['distance_label'] }}
                                            @endif
                                        @else
                                            {{ $set['reps'] ?? '—' }} reps
                                            @if ($set['weight_label'])
                                                &middot; {{ $set['weight_label'] }}
                                            @endif
                                        @endif
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="mt-2 text-sm text-text-secondary">Nenhuma série registrada para este exercício.</p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-text-secondary">Nenhum exercício planejado para este treino.</p>
                @endforelse
            </div>

            <div class="mt-3 flex justify-end border-t border-custom pt-3">
                <button type="button" onclick="closeWorkoutExtractModal({{ $log->id }})" class="btn-outline">Fechar</button>
            </div>
        </div>
    </div>
@endforeach

<script>
    (function () {
        window.openAssessmentDetailsModal = function () {
            const modal = document.getElementById('assessmentDetailsModal');

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeAssessmentDetailsModal = function () {
            const modal = document.getElementById('assessmentDetailsModal');

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        window.openTechnicalMuscleGroupModal = function (group) {
            const modal = document.getElementById('technicalMuscleGroupModal-' + group);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeTechnicalMuscleGroupModal = function (group) {
            const modal = document.getElementById('technicalMuscleGroupModal-' + group);

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        window.openWorkoutExtractModal = function (id) {
            const modal = document.getElementById('workoutExtractModal-' + id);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeWorkoutExtractModal = function (id) {
            const modal = document.getElementById('workoutExtractModal-' + id);

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        ['assessmentDetailsModal'].forEach(function (id) {
            const modal = document.getElementById(id);

            modal?.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeAssessmentDetailsModal();
                }
            });
        });

        document.querySelectorAll('[data-technical-muscle-group-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeTechnicalMuscleGroupModal(modal.dataset.technicalMuscleGroupModal);
                }
            });
        });

        document.querySelectorAll('[data-workout-extract-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeWorkoutExtractModal(modal.dataset.workoutExtractModal);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-technical-muscle-group-modal]:not(.hidden)').forEach(function (modal) {
                closeTechnicalMuscleGroupModal(modal.dataset.technicalMuscleGroupModal);
            });

            document.querySelectorAll('[data-workout-extract-modal]:not(.hidden)').forEach(function (modal) {
                closeWorkoutExtractModal(modal.dataset.workoutExtractModal);
            });

            const assessmentModal = document.getElementById('assessmentDetailsModal');

            if (assessmentModal && !assessmentModal.classList.contains('hidden')) {
                closeAssessmentDetailsModal();
            }
        });
    })();
</script>
@endsection
