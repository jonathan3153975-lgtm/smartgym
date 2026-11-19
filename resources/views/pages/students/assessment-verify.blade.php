@extends('layouts.app')

@section('title', 'Validar avaliação - '.$student->name)

@section('content')
@php
    $parq = old('parq_answers', $assessment->parq_answers ?? []);
    $metricCard = 'rounded-xl border border-custom bg-bg-secondary p-3';

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
    $totalActivityPercent = max(1, array_sum(array_column($activity['muscle_groups'], 'percent')));
    $activityDonutOffset = 0;
    $activityDonutSegments = [];

    foreach ($activity['muscle_groups'] as $index => $group) {
        $length = ($group['percent'] / $totalActivityPercent) * $circumference;
        $activityDonutSegments[] = [
            'group' => $group,
            'color' => $palette[$index % count($palette)],
            'length' => $length,
            'offset' => $activityDonutOffset,
        ];
        $activityDonutOffset += $length;
    }
@endphp

<div class="container mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('students.show', $student) }}" class="text-sm text-text-secondary transition-colors hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para o aluno
            </a>
            <h1 class="mt-1 text-2xl font-bold">Validar avaliação</h1>
            <p class="mt-1 text-text-secondary">{{ $student->name }}</p>
            <p class="mt-1 text-xs text-text-secondary">
                Enviada em {{ $assessment->submitted_at?->format('d/m/Y H:i') ?? '—' }}
                @if ($previousAssessment)
                    &middot; comparada à avaliação de {{ $previousAssessment->submitted_at?->format('d/m/Y') ?? '—' }}
                @endif
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($assessment->reviewed_at)
                <span class="badge badge-success">Verificada</span>
            @else
                <span class="badge badge-warning">A verificar</span>
            @endif
            <button type="button" onclick="window.print()" class="btn-outline">
                <i class="fas fa-print mr-2"></i>
                Imprimir
            </button>
        </div>
    </div>

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

    <form method="POST" action="{{ route('students.assessments.verify.update', [$student, $assessment]) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="space-y-6">
            <section class="card space-y-4">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-bullseye text-cyan"></i>
                    Objetivo e rotina
                </h2>

                <div>
                    <label for="goal" class="label-field">Objetivo principal <span class="text-red">*</span></label>
                    <select id="goal" name="goal" required class="input-field">
                        <option value="">Selecione</option>
                        @foreach (\App\Models\StudentAssessment::GOALS as $value => $label)
                            <option value="{{ $value }}" @selected(old('goal', $assessment->goal) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @include('pages.students._change', ['field' => 'goal', 'previous' => $previousAssessment?->goal_label])
                    @error('goal')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="experience_level" class="label-field">Experiência <span class="text-red">*</span></label>
                        <select id="experience_level" name="experience_level" required class="input-field">
                            <option value="">Selecione</option>
                            @foreach (\App\Models\StudentAssessment::EXPERIENCE_LEVELS as $value => $label)
                                <option value="{{ $value }}" @selected(old('experience_level', $assessment->experience_level) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @include('pages.students._change', ['field' => 'experience_level', 'previous' => $previousAssessment?->experience_level_label])
                    </div>

                    <div>
                        <label for="training_frequency" class="label-field">Frequência semanal <span class="text-red">*</span></label>
                        <input type="number" id="training_frequency" name="training_frequency" min="1" max="7" required
                               class="input-field" value="{{ old('training_frequency', $assessment->training_frequency) }}">
                        @include('pages.students._change', [
                            'field' => 'training_frequency',
                            'previous' => $previousAssessment?->training_frequency !== null
                                ? $previousAssessment->training_frequency.'x'
                                : null,
                        ])
                    </div>
                </div>

                <div>
                    <label for="goal_notes" class="label-field">Detalhes do objetivo</label>
                    <textarea id="goal_notes" name="goal_notes" rows="3" maxlength="2000" class="input-field">{{ old('goal_notes', $assessment->goal_notes) }}</textarea>
                    @include('pages.students._change', ['field' => 'goal_notes', 'previous' => $previousAssessment?->goal_notes])
                </div>
            </section>

            <section class="card space-y-4">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-heart-pulse text-cyan"></i>
                    Histórico de saúde
                </h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="medical_conditions" class="label-field">Condições de saúde</label>
                        <textarea id="medical_conditions" name="medical_conditions" rows="2" maxlength="2000" class="input-field">{{ old('medical_conditions', $assessment->medical_conditions) }}</textarea>
                        @include('pages.students._change', ['field' => 'medical_conditions', 'previous' => $previousAssessment?->medical_conditions])
                    </div>

                    <div>
                        <label for="medications" class="label-field">Medicamentos</label>
                        <textarea id="medications" name="medications" rows="2" maxlength="2000" class="input-field">{{ old('medications', $assessment->medications) }}</textarea>
                        @include('pages.students._change', ['field' => 'medications', 'previous' => $previousAssessment?->medications])
                    </div>

                    <div>
                        <label for="injuries" class="label-field">Lesões</label>
                        <textarea id="injuries" name="injuries" rows="2" maxlength="2000" class="input-field">{{ old('injuries', $assessment->injuries) }}</textarea>
                        @include('pages.students._change', ['field' => 'injuries', 'previous' => $previousAssessment?->injuries])
                    </div>

                    <div>
                        <label for="surgeries" class="label-field">Cirurgias</label>
                        <textarea id="surgeries" name="surgeries" rows="2" maxlength="2000" class="input-field">{{ old('surgeries', $assessment->surgeries) }}</textarea>
                        @include('pages.students._change', ['field' => 'surgeries', 'previous' => $previousAssessment?->surgeries])
                    </div>

                    <div>
                        <label for="alcohol_consumption" class="label-field">Consumo de álcool</label>
                        <select id="alcohol_consumption" name="alcohol_consumption" class="input-field">
                            <option value="">Não informar</option>
                            @foreach (\App\Models\StudentAssessment::ALCOHOL_CONSUMPTION as $value => $label)
                                <option value="{{ $value }}" @selected(old('alcohol_consumption', $assessment->alcohol_consumption) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @include('pages.students._change', ['field' => 'alcohol_consumption', 'previous' => $previousAssessment?->alcohol_consumption_label])
                    </div>

                    <div>
                        <label for="sleep_hours" class="label-field">Horas de sono por noite</label>
                        <input type="number" id="sleep_hours" name="sleep_hours" min="1" max="16" class="input-field"
                               value="{{ old('sleep_hours', $assessment->sleep_hours) }}">
                        @include('pages.students._change', [
                            'field' => 'sleep_hours',
                            'previous' => $previousAssessment?->sleep_hours !== null
                                ? $previousAssessment->sleep_hours.' h/noite'
                                : null,
                        ])
                    </div>

                    <div>
                        <label for="stress_level" class="label-field">Nível de estresse</label>
                        <select id="stress_level" name="stress_level" class="input-field">
                            <option value="">Não informar</option>
                            @for ($level = 1; $level <= 5; $level++)
                                <option value="{{ $level }}" @selected((string) old('stress_level', $assessment->stress_level) === (string) $level)>
                                    {{ $level }} - {{ ['Muito baixo', 'Baixo', 'Moderado', 'Alto', 'Muito alto'][$level - 1] }}
                                </option>
                            @endfor
                        </select>
                        @include('pages.students._change', [
                            'field' => 'stress_level',
                            'previous' => $previousAssessment?->stress_level !== null
                                ? $previousAssessment->stress_level.'/5'
                                : null,
                        ])
                    </div>

                    <div class="flex items-end">
                        <input type="hidden" name="smoker" value="0">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" id="smoker" name="smoker" value="1" @checked(old('smoker', $assessment->smoker))>
                            <span class="text-sm">Fumante</span>
                        </label>
                    </div>
                </div>
            </section>
            </div>

            <section class="card space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 font-semibold">
                            <i class="fas fa-chart-simple text-cyan"></i>
                            Extrato de atividade
                        </h2>
                        <p class="mt-1 text-xs text-text-secondary">
                            Período entre {{ $activity['from']->format('d/m/Y') }} e {{ $activity['to']->format('d/m/Y') }}.
                        </p>
                    </div>
                    <button type="button" onclick="openActivityWorkoutExtractModal()" class="btn-outline self-start">
                        <i class="fas fa-eye mr-2"></i>
                        Extrato dos treinos
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div class="{{ $metricCard }}">
                        <p class="text-xs text-text-secondary">Treinos concluídos</p>
                        <p class="mt-1 text-2xl font-bold">{{ $activity['workouts'] }}</p>
                    </div>
                    <div class="{{ $metricCard }}">
                        <p class="text-xs text-text-secondary">Aulas realizadas</p>
                        <p class="mt-1 text-2xl font-bold">{{ $activity['classes'] }}</p>
                    </div>
                    <div class="{{ $metricCard }}">
                        <p class="text-xs text-text-secondary">Check-ins</p>
                        <p class="mt-1 text-2xl font-bold">{{ $activity['checkins'] }}</p>
                    </div>
                    <div class="{{ $metricCard }}">
                        <p class="text-xs text-text-secondary">Dias ativos</p>
                        <p class="mt-1 text-2xl font-bold">{{ $activity['activity_days'] }}</p>
                    </div>
                </div>

                <div>
                    <h3 class="flex items-center gap-2 text-sm font-semibold">
                        <i class="fas fa-layer-group text-cyan"></i>
                        Grupos musculares treinados
                    </h3>

                    @if ($activity['muscle_groups'] !== [])
                        <div class="relative mx-auto mt-3 h-40 w-40">
                            <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90" role="img" aria-label="Distribuição por grupos musculares no período">
                                <circle cx="50" cy="50" r="42" fill="none" stroke="var(--color-border)" stroke-width="16"></circle>
                                @foreach ($activityDonutSegments as $segment)
                                    <circle cx="50" cy="50" r="42" fill="none"
                                            stroke="{{ $segment['color'] }}"
                                            stroke-width="16"
                                            stroke-dasharray="0 {{ $circumference }}"
                                            data-verify-dasharray="{{ $segment['length'] }} {{ $circumference - $segment['length'] }}"
                                            stroke-dashoffset="{{ -$segment['offset'] }}"
                                            class="donut-segment cursor-pointer transition-opacity hover:opacity-75"
                                            onclick="openVerifyMuscleGroupModal(@js($segment['group']['group']))">
                                        <title>{{ $segment['group']['label'] }}: {{ $segment['group']['percent'] }}%</title>
                                    </circle>
                                @endforeach
                            </svg>
                            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                                <p class="text-2xl font-bold">{{ count($activity['muscle_groups']) }}</p>
                                <p class="text-[0.62rem] uppercase tracking-wider text-text-secondary">grupos</p>
                            </div>
                        </div>

                        <div class="mt-3 space-y-1.5">
                            @foreach ($activityDonutSegments as $segment)
                                <button type="button" onclick="openVerifyMuscleGroupModal(@js($segment['group']['group']))"
                                        class="interactive-item flex w-full cursor-pointer items-center justify-between gap-2 rounded-lg border border-custom bg-bg-secondary px-3 py-2 text-left text-sm transition-colors hover:border-cyan">
                                    <span class="flex min-w-0 items-center gap-2">
                                        <span class="h-2.5 w-2.5 flex-shrink-0 rounded-full" style="background: {{ $segment['color'] }};"></span>
                                        <span class="truncate">{{ $segment['group']['label'] }}</span>
                                    </span>
                                    <span class="flex-shrink-0 text-text-secondary">{{ $segment['group']['percent'] }}%</span>
                                </button>
                            @endforeach
                        </div>
                    @else
                        <p class="mt-2 text-sm text-text-secondary">Nenhuma série registrada no período.</p>
                    @endif
                </div>
            </section>

            <section class="card space-y-4 md:col-span-2">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 font-semibold">
                            <i class="fas fa-clipboard-question text-cyan"></i>
                            Questionário de prontidão (PAR-Q)
                        </h2>
                        <p class="mt-1 text-xs text-text-secondary">Alterações em relação à avaliação anterior são sinalizadas.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                    @foreach (\App\Models\StudentAssessment::PARQ_QUESTIONS as $key => $question)
                        @php($positive = filter_var($parq[$key] ?? false, FILTER_VALIDATE_BOOL))
                        <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                            <p class="text-sm font-medium">{{ $question }}</p>
                            @include('pages.students._change', [
                                'field' => 'parq_'.$key,
                                'previous' => $previousAssessment
                                    ? (filter_var($previousAssessment->parq_answers[$key] ?? false, FILTER_VALIDATE_BOOL) ? 'Sim' : 'Não')
                                    : null,
                            ])
                            <div class="mt-2 flex gap-4">
                                <label class="flex cursor-pointer items-center gap-2 text-sm">
                                    <input type="radio" name="parq_answers[{{ $key }}]" value="1" @checked($positive)>
                                    Sim
                                </label>
                                <label class="flex cursor-pointer items-center gap-2 text-sm">
                                    <input type="radio" name="parq_answers[{{ $key }}]" value="0" @checked(! $positive)>
                                    Não
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                @error('parq_answers')
                    <p class="text-sm text-red">{{ $message }}</p>
                @enderror
            </section>

            <section class="card space-y-4 md:col-span-2">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 font-semibold">
                            <i class="fas fa-ruler text-cyan"></i>
                            Medidas corporais
                        </h2>
                        <p class="mt-1 text-xs text-text-secondary">Valores editáveis pelo instrutor durante a verificação.</p>
                    </div>
                    @if ($previousAssessment)
                        <span class="badge badge-info self-start sm:self-auto">Comparado à avaliação anterior</span>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        'weight_kg' => ['label' => 'Peso (kg)', 'decimals' => 3],
                        'height_cm' => ['label' => 'Altura', 'decimals' => 2],
                        'body_fat_percent' => ['label' => 'Gordura (%)', 'decimals' => 2],
                        'waist_cm' => ['label' => 'Cintura', 'decimals' => 2],
                        'hip_cm' => ['label' => 'Quadril', 'decimals' => 2],
                        'chest_cm' => ['label' => 'Tórax', 'decimals' => 2],
                        'arm_cm' => ['label' => 'Braço', 'decimals' => 2],
                        'thigh_cm' => ['label' => 'Coxa', 'decimals' => 2],
                    ] as $field => $config)
                        <div>
                            <label for="{{ $field }}" class="label-field">{{ $config['label'] }}</label>
                            <input type="text" inputmode="decimal" maxlength="12"
                                   id="{{ $field }}" name="{{ $field }}" class="input-field"
                                   data-decimal-input="{{ $config['decimals'] }}"
                                   placeholder="{{ $config['decimals'] === 3 ? '0,000' : '0,00' }}"
                                   value="{{ old($field, $assessment->{$field}) }}">
                            @include('pages.students._change', ['field' => $field, 'previous' => $previousAssessment?->{$field}])
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="card space-y-4 md:col-span-2">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-address-book text-cyan"></i>
                    Contato de emergência
                </h2>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="emergency_contact_name" class="label-field">Nome <span class="text-red">*</span></label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" required maxlength="255"
                               class="input-field"
                               value="{{ old('emergency_contact_name', $assessment->emergency_contact_name ?? $student->emergency_contact_name) }}">
                        @include('pages.students._change', ['field' => 'emergency_contact_name', 'previous' => $previousAssessment?->emergency_contact_name])
                    </div>

                    <div>
                        <label for="emergency_contact_phone" class="label-field">Telefone <span class="text-red">*</span></label>
                        <input type="text" id="emergency_contact_phone" name="emergency_contact_phone" required maxlength="20"
                               class="input-field" data-phone placeholder="(00) 00000-0000"
                               value="{{ old('emergency_contact_phone', $assessment->emergency_contact_phone ?? $student->emergency_contact_phone) }}">
                        @include('pages.students._change', ['field' => 'emergency_contact_phone', 'previous' => $previousAssessment?->emergency_contact_phone])
                    </div>
                </div>

                <div>
                    <label for="observations" class="label-field">Observações do aluno</label>
                    <textarea id="observations" name="observations" rows="3" maxlength="4000" class="input-field">{{ old('observations', $assessment->observations) }}</textarea>
                    @include('pages.students._change', ['field' => 'observations', 'previous' => $previousAssessment?->observations])
                </div>
            </section>

            <section class="card space-y-4 md:col-span-2">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-notes-medical text-cyan"></i>
                    Observação do treinador
                </h2>
                <p class="text-xs text-text-secondary">
                    Registre orientações ou observações sobre a ficha antes de confirmar a verificação.
                </p>
                <textarea id="trainer_notes" name="trainer_notes" rows="6" maxlength="4000" class="input-field"
                          placeholder="Ex.: aluno relatou dor no joelho; orientado a reduzir impacto...">{{ old('trainer_notes', $assessment->trainer_notes) }}</textarea>
                @error('trainer_notes')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </section>
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <a href="{{ route('students.show', $student) }}" class="btn-outline justify-center">Cancelar</a>
            <button type="submit" class="btn-primary justify-center">
                <i class="fas fa-user-check mr-2"></i>
                Validar avaliação
            </button>
        </div>
    </form>
</div>

@foreach ($activity['muscle_groups'] as $group)
    <div id="verifyMuscleGroupModal-{{ $group['group'] }}" data-verify-muscle-group-modal="{{ $group['group'] }}"
         class="modal-overlay hidden" role="dialog" aria-modal="true"
         aria-labelledby="verifyMuscleGroupTitle-{{ $group['group'] }}">
        <div class="modal-content flex max-h-[85vh] flex-col">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 id="verifyMuscleGroupTitle-{{ $group['group'] }}" class="text-xl font-bold">{{ $group['label'] }}</h2>
                    <p class="mt-1 text-xs text-text-secondary">{{ $group['percent'] }}% do total de repetições</p>
                </div>
                <button type="button" onclick="closeVerifyMuscleGroupModal(@js($group['group']))"
                        class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
                @forelse ($activity['muscle_group_exercises'][$group['group']] ?? [] as $exercise)
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
                    <p class="text-sm text-text-secondary">Nenhum exercício registrado para este grupo no período.</p>
                @endforelse
            </div>

            <div class="mt-3 flex justify-end border-t border-custom pt-3">
                <button type="button" onclick="closeVerifyMuscleGroupModal(@js($group['group']))" class="btn-outline">Fechar</button>
            </div>
        </div>
    </div>
@endforeach

<div id="activityWorkoutExtractModal" class="modal-overlay hidden" role="dialog" aria-modal="true"
     aria-labelledby="activityWorkoutExtractTitle">
    <div class="modal-content modal-content-lg flex max-h-[88vh] flex-col">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h2 id="activityWorkoutExtractTitle" class="text-xl font-bold">Extrato dos treinos</h2>
                <p class="mt-1 text-xs text-text-secondary">
                    Período entre {{ $activity['from']->format('d/m/Y') }} e {{ $activity['to']->format('d/m/Y') }}.
                </p>
            </div>
            <button type="button" onclick="closeActivityWorkoutExtractModal()"
                    class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="min-h-0 flex-1 space-y-3 overflow-y-auto pr-1">
            @forelse ($activity['workout_extracts'] as $extract)
                <section class="rounded-xl border border-custom p-3">
                    <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-medium">{{ $extract['name'] }}</p>
                            <p class="text-xs text-text-secondary">
                                {{ $extract['date'] ?? '—' }}
                                &middot; {{ $extract['duration'] }}
                                &middot; {{ $extract['volume'] }}
                            </p>
                        </div>
                        <span class="badge badge-info">{{ count($extract['exercises']) }} exercício(s)</span>
                    </div>

                    <div class="mt-2 space-y-2">
                        @forelse ($extract['exercises'] as $exercise)
                            <div class="rounded-lg border border-custom bg-bg-secondary p-2.5">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium">{{ $exercise['name'] }}</p>
                                        <p class="text-xs text-text-secondary">{{ $exercise['muscle_group'] }}</p>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        <span class="{{ $exercise['load_increased'] ? 'font-semibold text-green' : 'text-text-secondary' }}">
                                            {{ $exercise['max_weight_label'] }}
                                        </span>
                                        @if ($exercise['load_increased'])
                                            <span class="badge badge-success">Carga aumentou</span>
                                        @endif
                                        @if ($exercise['previous_load_label'])
                                            <span class="text-text-secondary">anterior {{ $exercise['previous_load_label'] }}</span>
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
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-text-secondary">Nenhum exercício concluído neste treino.</p>
                        @endforelse
                    </div>
                </section>
            @empty
                <p class="text-sm text-text-secondary">Nenhum treino concluído no período.</p>
            @endforelse
        </div>

        <div class="mt-3 flex justify-end border-t border-custom pt-3">
            <button type="button" onclick="closeActivityWorkoutExtractModal()" class="btn-outline">Fechar</button>
        </div>
    </div>
</div>

<script>
    (function () {
        window.openVerifyMuscleGroupModal = function (group) {
            const modal = document.getElementById('verifyMuscleGroupModal-' + group);

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeVerifyMuscleGroupModal = function (group) {
            const modal = document.getElementById('verifyMuscleGroupModal-' + group);

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        window.openActivityWorkoutExtractModal = function () {
            const modal = document.getElementById('activityWorkoutExtractModal');

            if (!modal) {
                return;
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeActivityWorkoutExtractModal = function () {
            const modal = document.getElementById('activityWorkoutExtractModal');

            if (modal) {
                modal.classList.add('hidden');
            }

            document.body.style.overflow = '';
        };

        document.getElementById('activityWorkoutExtractModal')?.addEventListener('click', function (event) {
            if (event.target === this) {
                closeActivityWorkoutExtractModal();
            }
        });

        window.addEventListener('load', function () {
            document.querySelectorAll('[data-verify-dasharray]').forEach(function (segment) {
                segment.style.strokeDasharray = segment.dataset.verifyDasharray;
            });
        });

        document.querySelectorAll('[data-verify-muscle-group-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeVerifyMuscleGroupModal(modal.dataset.verifyMuscleGroupModal);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-verify-muscle-group-modal]:not(.hidden)').forEach(function (modal) {
                closeVerifyMuscleGroupModal(modal.dataset.verifyMuscleGroupModal);
            });

            const activityModal = document.getElementById('activityWorkoutExtractModal');

            if (activityModal && !activityModal.classList.contains('hidden')) {
                closeActivityWorkoutExtractModal();
            }
        });
    })();
</script>
@endsection
