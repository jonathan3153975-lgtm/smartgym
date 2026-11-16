@extends('layouts.student')

@section('title', 'Treino em andamento')

@section('content')
@php
    $rowIndex = 0;
@endphp

<div class="mx-auto w-full max-w-4xl space-y-5">
    <div class="card card-mobile-tight">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Treino em andamento</p>
        <h1 class="text-2xl font-bold">{{ $workoutLog->session_name }}</h1>
        <p class="text-sm text-text-secondary">
            Iniciado em {{ $workoutLog->started_at->format('d/m/Y H:i') }}
        </p>
        <p class="text-sm text-text-secondary">
            Tempo de treino: <span id="workoutElapsed" class="font-mono font-medium text-text-primary">00:00</span>
        </p>
    </div>

    <form id="workoutCompleteForm" method="POST"
          action="{{ route('student.portal.workouts.complete', [$network, $workoutLog]) }}"
          data-autosave-url="{{ route('student.portal.workouts.save-progress', [$network, $workoutLog]) }}"
          class="space-y-5">
        @csrf
        <input type="hidden" name="update_plan" id="update_plan" value="0">

        @foreach ($exercises as $workoutExercise)
            @php
                $isCardio = $workoutExercise->isCardio();
                $loadDisabled = $workoutExercise->isLoadDisabled();
                $suggestedLoad = $workoutExercise->suggested_load;
                $stats = $exerciseStats[$workoutExercise->exercise_id] ?? null;
                $cardioPlannedSeconds = 0;

                if ($isCardio && is_string($suggestedLoad) && preg_match('/^(\d{1,3}):([0-5]\d)$/', trim($suggestedLoad), $loadMatches) === 1) {
                    $cardioPlannedSeconds = ((int) $loadMatches[1] * 60) + (int) $loadMatches[2];
                }

                $savedSets = $workoutLog->sets
                    ->where('workout_exercise_id', $workoutExercise->id)
                    ->keyBy('set_number');

                $lastWeight = $stats['last_weight'] ?? null;
                $loadValue = $lastWeight !== null
                    ? rtrim(rtrim(number_format((float) $lastWeight, 3, ',', ''), '0'), ',')
                    : $suggestedLoad;
            @endphp

            <section class="card card-mobile-tight space-y-3">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        @if ($workoutExercise->exercise?->image_url)
                            <img src="{{ $workoutExercise->exercise->image_url }}" alt="{{ $workoutExercise->exercise_name }}"
                                 class="h-16 w-16 flex-shrink-0 rounded-xl border border-custom object-cover">
                        @endif
                        <div>
                            <p class="text-xs text-text-secondary">Exercício {{ $workoutExercise->order }}</p>
                            <h2 class="text-lg font-semibold">{{ $workoutExercise->exercise_name }}</h2>
                            <p class="text-xs text-text-secondary">
                                {{ $workoutExercise->muscle_group_label }}
                                @if ($isCardio)
                                    &middot; cardio
                                    @if ($suggestedLoad)
                                        &middot; tempo sugerido: {{ $suggestedLoad }}
                                    @endif
                                @else
                                    &middot; {{ $workoutExercise->sets }} séries
                                    &middot; {{ $workoutExercise->reps }} repetições
                                    @if ($suggestedLoad && ! $loadDisabled)
                                        &middot; carga sugerida: {{ $suggestedLoad }}
                                    @endif
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1 self-start sm:self-auto">
                        @unless ($isCardio)
                            <button type="button"
                                    onclick="startRest({{ $workoutExercise->rest_seconds }})"
                                    class="btn-outline"
                                    @disabled($workoutExercise->rest_seconds <= 0)>
                                <i class="fas fa-stopwatch mr-2"></i>
                                Descanso {{ $workoutExercise->rest_seconds }}s
                            </button>
                        @endunless

                        @if (! in_array($workoutExercise->id, $startedExerciseIds))
                            <button type="button"
                                    data-replace-exercise
                                    data-replace-url="{{ route('student.portal.workouts.exercises.replace', [$network, $workoutLog, $workoutExercise]) }}"
                                    data-muscle-group="{{ $workoutExercise->muscle_group }}"
                                    data-equipment="{{ $workoutExercise->exercise?->equipment }}"
                                    data-exercise-name="{{ $workoutExercise->exercise_name }}"
                                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-cyan"
                                    title="Substituir exercício" aria-label="Substituir exercício"
                                    onclick="openReplaceExerciseModal(this)">
                                <i class="fas fa-right-left"></i>
                            </button>
                        @endif
                    </div>
                </div>

                @if ($workoutExercise->notes)
                    <p class="text-sm text-text-secondary">
                        <i class="fas fa-circle-info mr-1 text-cyan"></i>
                        {{ $workoutExercise->notes }}
                    </p>
                @endif

                @if ($stats)
                    <div class="flex flex-wrap gap-1.5">
                        @if (! $isCardio)
                            @if ($stats['last_weight_label'])
                                <span class="badge badge-secondary">Última carga: {{ $stats['last_weight_label'] }}</span>
                            @endif
                            @if ($stats['max_weight_label'])
                                <span class="badge badge-secondary">Máxima: {{ $stats['max_weight_label'] }}</span>
                            @endif
                        @else
                            @if ($stats['last_seconds_label'])
                                <span class="badge badge-secondary">Último tempo: {{ $stats['last_seconds_label'] }}</span>
                            @endif
                            @if ($stats['max_seconds_label'])
                                <span class="badge badge-secondary">Melhor tempo: {{ $stats['max_seconds_label'] }}</span>
                            @endif
                            @if ($stats['last_distance_label'])
                                <span class="badge badge-secondary">Última distância: {{ $stats['last_distance_label'] }}</span>
                            @endif
                            @if ($stats['max_distance_label'])
                                <span class="badge badge-secondary">Maior distância: {{ $stats['max_distance_label'] }}</span>
                            @endif
                        @endif
                    </div>
                @endif

                @if ($isCardio)
                    <div class="space-y-3 rounded-xl border border-custom bg-bg-secondary p-3" data-cardio
                         data-planned-seconds="{{ $cardioPlannedSeconds }}">
                        @php
                            $savedCardio = $savedSets->get(1);
                        @endphp
                        <input type="hidden" name="sets[{{ $rowIndex }}][workout_exercise_id]" value="{{ $workoutExercise->id }}">
                        <input type="hidden" name="sets[{{ $rowIndex }}][exercise_id]" value="{{ $workoutExercise->exercise_id }}">
                        <input type="hidden" name="sets[{{ $rowIndex }}][exercise_name]" value="{{ $workoutExercise->exercise_name }}">
                        <input type="hidden" name="sets[{{ $rowIndex }}][muscle_group]" value="{{ $workoutExercise->muscle_group }}">
                        <input type="hidden" name="sets[{{ $rowIndex }}][set_number]" value="1">
                        <input type="hidden" name="sets[{{ $rowIndex }}][rest_seconds]" value="0">
                        <input type="hidden" name="sets[{{ $rowIndex }}][touched]" value="0" data-cardio-touched>
                        <input type="hidden" name="sets[{{ $rowIndex }}][completed]" value="0" data-cardio-completed>
                        <input type="hidden" name="sets[{{ $rowIndex }}][cardio_seconds]" value="{{ $savedCardio?->cardio_seconds ?? 0 }}" data-cardio-seconds>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs uppercase tracking-wider text-text-secondary">Tempo de cardio</p>
                                <p class="font-mono text-3xl font-bold text-cyan" data-cardio-display>00:00</p>
                            </div>

                            <div class="flex w-full gap-2 sm:w-auto">
                                <button type="button" class="btn-primary flex-1" data-cardio-toggle>
                                    <i class="fas fa-play mr-2"></i>
                                    Iniciar
                                </button>
                                <button type="button" class="btn-outline hidden flex-1" data-cardio-stop>
                                    <i class="fas fa-flag-checkered mr-2"></i>
                                    Finalizar
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="cardio_distance_{{ $workoutExercise->id }}" class="label-field">Distância percorrida (km)</label>
                            <input type="text" id="cardio_distance_{{ $workoutExercise->id }}"
                                   name="sets[{{ $rowIndex }}][distance_km]"
                                   inputmode="decimal" placeholder="0,000" maxlength="12" class="input-field"
                                   value="{{ $savedCardio?->distance_km !== null ? rtrim(rtrim(number_format((float) $savedCardio->distance_km, 3, ',', ''), '0'), ',') : '' }}"
                                   data-decimal-input="3">
                            <p class="mt-1 text-xs text-text-secondary">Opcional. Preencha se o equipamento mostrar a distância.</p>
                        </div>
                    </div>
                    @php $rowIndex++; @endphp
                @else
                    <div class="space-y-2" data-set-list>
                        @for ($setNumber = 1; $setNumber <= $workoutExercise->sets; $setNumber++)
                            @php
                                $savedSet = $savedSets->get($setNumber);
                                $savedWeight = $savedSet?->weight_kg;
                                $setRepsValue = $savedSet?->reps ?? $workoutExercise->reps;
                                $setLoadValue = $loadDisabled
                                    ? ''
                                    : ($savedWeight !== null
                                        ? rtrim(rtrim(number_format((float) $savedWeight, 3, ',', ''), '0'), ',')
                                        : $loadValue);
                            @endphp
                            <div class="rounded-xl border border-custom bg-bg-secondary p-2.5" data-set-row
                                 data-planned-reps="{{ $workoutExercise->reps }}"
                                 data-planned-load="{{ $loadDisabled ? '' : $suggestedLoad }}"
                                 data-initial-reps="{{ $setRepsValue }}"
                                 data-initial-load="{{ $setLoadValue }}">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="text-sm font-semibold">{{ $setNumber }}ª série</span>

                                    <div class="flex items-center gap-2">
                                        <span class="hidden text-right text-xs text-text-secondary" data-set-summary></span>
                                        <i class="fas fa-check-circle hidden text-green" data-set-check></i>
                                        <button type="button"
                                                class="hidden rounded-lg p-1.5 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-cyan"
                                                data-edit-set title="Editar série" aria-label="Editar série">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button"
                                                class="btn-outline complete-set-btn text-xs"
                                                data-rest="{{ $workoutExercise->rest_seconds }}"
                                                onclick="completeSet(this)">
                                            <i class="fas fa-check mr-1"></i>
                                            Concluir série
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-2 grid grid-cols-2 gap-2" data-set-inputs>
                                    <div>
                                        <label class="label-field text-xs" for="reps_{{ $workoutExercise->id }}_{{ $setNumber }}">Repetições</label>
                                        <input type="hidden" name="sets[{{ $rowIndex }}][workout_exercise_id]" value="{{ $workoutExercise->id }}">
                                        <input type="hidden" name="sets[{{ $rowIndex }}][exercise_id]" value="{{ $workoutExercise->exercise_id }}">
                                        <input type="hidden" name="sets[{{ $rowIndex }}][exercise_name]" value="{{ $workoutExercise->exercise_name }}">
                                        <input type="hidden" name="sets[{{ $rowIndex }}][muscle_group]" value="{{ $workoutExercise->muscle_group }}">
                                        <input type="hidden" name="sets[{{ $rowIndex }}][set_number]" value="{{ $setNumber }}">
                                        <input type="hidden" name="sets[{{ $rowIndex }}][rest_seconds]" value="{{ $workoutExercise->rest_seconds }}">
                                        <input type="hidden" name="sets[{{ $rowIndex }}][touched]" value="0" data-set-touched>
                                        <input type="hidden" name="sets[{{ $rowIndex }}][completed]" value="{{ $savedSet?->completed ? 1 : 0 }}" data-set-completed-flag>

                                        <input type="text" inputmode="numeric" maxlength="4"
                                               id="reps_{{ $workoutExercise->id }}_{{ $setNumber }}"
                                               name="sets[{{ $rowIndex }}][reps]"
                                               class="input-field" value="{{ $setRepsValue }}"
                                               aria-label="Repetições da série {{ $setNumber }}">
                                    </div>

                                    <div>
                                        <label class="label-field text-xs" for="load_{{ $workoutExercise->id }}_{{ $setNumber }}">Carga (kg)</label>
                                        <input type="text" inputmode="decimal" maxlength="12"
                                               id="load_{{ $workoutExercise->id }}_{{ $setNumber }}"
                                               name="sets[{{ $rowIndex }}][weight_kg]"
                                               class="input-field {{ $loadDisabled ? 'cursor-not-allowed opacity-60' : '' }}"
                                               value="{{ $setLoadValue }}"
                                               placeholder="{{ $loadDisabled ? 'Não se aplica' : '0,000' }}"
                                               data-decimal-input="3"
                                               @disabled($loadDisabled)
                                               aria-label="Carga da série {{ $setNumber }}">
                                    </div>
                                </div>
                            </div>
                            @php $rowIndex++; @endphp
                        @endfor
                    </div>
                @endif
            </section>
        @endforeach

        <section class="card">
            <label for="workout_notes" class="label-field">Observações do treino</label>
            <textarea id="workout_notes" name="notes" rows="3" maxlength="2000" class="input-field"
                      placeholder="Como foi o treino? Alguma dificuldade ou ajuste?">{{ $workoutLog->notes }}</textarea>
        </section>

        <div class="sticky bottom-0 -mx-4 border-t border-custom bg-bg-primary/95 px-4 py-3 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0">
            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('student.portal.workouts.index', $network) }}" class="btn-outline justify-center text-center" data-leave-workout>
                    Voltar depois
                </a>
                <button type="submit" class="btn-primary justify-center">
                    <i class="fas fa-flag-checkered mr-2"></i>
                    Concluir treino
                </button>
            </div>
        </div>
    </form>
</div>

{{-- Replace exercise modal --}}
<div id="replaceExerciseModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="replaceExerciseTitle">
    <div class="modal-content modal-content-lg flex max-h-[88vh] flex-col p-3 sm:p-6">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h2 id="replaceExerciseTitle" class="text-xl font-bold">Substituir exercício</h2>
                <p class="mt-1 text-xs text-text-secondary">
                    Atual: <span id="replaceExerciseName" class="font-medium">{{ '' }}</span>
                </p>
            </div>
            <button type="button" onclick="closeReplaceExerciseModal()"
                    class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
            <div>
                <label for="replace_equipment" class="label-field">Equipamento</label>
                <select id="replace_equipment" class="input-field">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Exercise::EQUIPMENT_OPTIONS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="replace_group" class="label-field">Grupo muscular</label>
                <select id="replace_group" class="input-field">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Exercise::MUSCLE_GROUPS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <input type="search" id="replace_search" class="input-field mt-2" placeholder="Buscar exercício" autocomplete="off">

        <div id="replaceExerciseList" class="mt-2 min-h-0 flex-1 space-y-2 overflow-y-auto">
            @foreach ($exerciseCatalog as $exercise)
                <div data-replace-item
                     data-name="{{ mb_strtolower($exercise->name) }}"
                     data-equipment="{{ $exercise->equipment }}"
                     data-muscle-group="{{ $exercise->muscle_group }}"
                     class="rounded-xl border border-custom bg-bg-secondary p-2">
                    <div class="flex items-start gap-2.5">
                        @if ($exercise->image_url)
                            <img src="{{ $exercise->image_url }}" alt="{{ $exercise->name }}"
                                 class="h-10 w-10 flex-shrink-0 rounded-lg border border-custom object-cover">
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $exercise->name }}</p>
                            <p class="truncate text-xs text-text-secondary">
                                {{ $exercise->muscle_group_label }} &middot; {{ $exercise->equipment_label }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-2 flex justify-end">
                        <button type="button" data-replace-pick="{{ $exercise->id }}"
                                class="btn-primary p-2" title="Substituir por este exercício"
                                aria-label="Substituir por este exercício">
                            <i class="fas fa-right-left"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <p id="replaceEmpty" class="hidden py-6 text-center text-sm text-text-secondary">Nenhum exercício encontrado.</p>

        <div class="mt-3 flex justify-end border-t border-custom pt-3">
            <button type="button" onclick="closeReplaceExerciseModal()" class="btn-outline">Fechar</button>
        </div>
    </div>
</div>

<form id="replaceExerciseForm" method="POST" class="hidden">
    @csrf
    <input type="hidden" name="exercise_id" id="replace_exercise_id" value="">
</form>

<div id="restTimer" class="hidden fixed bottom-4 left-1/2 z-50 w-[calc(100%-2rem)] max-w-sm -translate-x-1/2 rounded-xl border border-custom bg-secondary p-4 shadow-xl">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p id="restTimerLabel" class="text-xs uppercase tracking-wider text-text-secondary">Descanso</p>
            <p id="restTimerValue" class="text-3xl font-bold text-cyan">0s</p>
        </div>
        <button type="button" onclick="stopRestTimer()" class="btn-outline">
            Parar
        </button>
    </div>
</div>

<script>
    (function () {
        const startedAt = new Date(@json($workoutLog->started_at->toIso8601String())).getTime();
        const elapsed = document.getElementById('workoutElapsed');

        function updateElapsed() {
            if (!elapsed) {
                return;
            }

            const total = Math.max(0, Math.floor((Date.now() - startedAt) / 1000));
            const minutes = Math.floor(total / 60);
            const seconds = total % 60;

            elapsed.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
        }

        updateElapsed();
        setInterval(updateElapsed, 1000);
    })();

    function formatClock(totalSeconds) {
        const total = Math.max(0, Math.floor(totalSeconds));
        const hours = Math.floor(total / 3600);
        const minutes = Math.floor((total % 3600) / 60);
        const seconds = total % 60;
        const clock = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

        return hours > 0 ? String(hours).padStart(2, '0') + ':' + clock : clock;
    }

    // Cardio timer: starts counting and stores the elapsed seconds on submit.
    document.querySelectorAll('[data-cardio]').forEach(function (block) {
        const display = block.querySelector('[data-cardio-display]');
        const hidden = block.querySelector('[data-cardio-seconds]');
        const toggle = block.querySelector('[data-cardio-toggle]');
        const stop = block.querySelector('[data-cardio-stop]');
        const touched = block.querySelector('[data-cardio-touched]');
        const completed = block.querySelector('[data-cardio-completed]');

        function markTouched() {
            if (touched) {
                touched.value = '1';
            }
        }

        let seconds = Number(hidden?.value || 0);
        let interval = null;

        function render() {
            if (display) {
                display.textContent = formatClock(seconds);
            }

            if (hidden) {
                hidden.value = seconds;
            }
        }

        function pause(label) {
            if (interval) {
                clearInterval(interval);
                interval = null;
            }

            if (toggle) {
                toggle.innerHTML = '<i class="fas fa-play mr-2"></i>' + label;
            }
        }

        toggle?.addEventListener('click', function () {
            markTouched();

            if (interval) {
                pause('Retomar');
                return;
            }

            interval = setInterval(function () {
                seconds += 1;
                render();
            }, 1000);

            toggle.innerHTML = '<i class="fas fa-pause mr-2"></i>Pausar';
            stop?.classList.remove('hidden');
        });

        stop?.addEventListener('click', function () {
            markTouched();

            if (completed) {
                completed.value = '1';
            }

            pause(seconds > 0 ? 'Retomar' : 'Iniciar');
            stop.classList.add('hidden');
            render();
            scheduleAutoSave();
        });

        block.querySelector('input[name$="[distance_km]"]')?.addEventListener('input', markTouched);

        render();
    });

    let restInterval = null;
    let restDeadline = 0;
    let restTotal = 0;

    function completeSet(button) {
        const row = button.closest('[data-set-row]');

        if (!row) {
            return;
        }

        toggleSetCompleted(row, true);
        startRest(Number(button.dataset.rest || 0));
    }

    function describeSet(row) {
        const reps = row.querySelector('input[name$="[reps]"]');
        const load = row.querySelector('input[name$="[weight_kg]"]');
        const parts = [];

        const repsValue = reps ? parseReps(reps.value) : null;

        if (repsValue !== null) {
            parts.push(repsValue + ' reps');
        }

        const loadValue = load && !load.disabled ? parseNumber(load.value) : null;

        if (loadValue !== null) {
            parts.push(loadValue.toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }) + ' kg');
        }

        return parts.length ? parts.join(' · ') : 'Série registrada';
    }

    function toggleSetCompleted(row, completed) {
        const inputs = row.querySelector('[data-set-inputs]');
        const summary = row.querySelector('[data-set-summary]');
        const complete = row.querySelector('.complete-set-btn');
        const check = row.querySelector('[data-set-check]');
        const edit = row.querySelector('[data-edit-set]');

        if (!inputs || !summary || !complete || !check || !edit) {
            return;
        }

        const touched = row.querySelector('[data-set-touched]');
        const completedFlag = row.querySelector('[data-set-completed-flag]');

        if (touched) {
            touched.value = '1';
        }

        if (completedFlag) {
            completedFlag.value = completed ? '1' : '0';
        }

        if (completed) {
            summary.textContent = describeSet(row);
        }

        inputs.classList.toggle('hidden', completed);
        summary.classList.toggle('hidden', !completed);
        complete.classList.toggle('hidden', completed);
        check.classList.toggle('hidden', !completed);
        edit.classList.toggle('hidden', !completed);
        row.classList.toggle('set-completed', completed);

        if (completed) {
            const list = row.closest('[data-set-list]');

            if (list) {
                list.querySelectorAll('[data-set-row]').forEach(function (item) {
                    item.classList.remove('set-current');
                });

                const next = row.nextElementSibling;

                if (next && next.matches('[data-set-row]')) {
                    next.classList.add('set-current');
                }
            }
        } else {
            row.classList.remove('set-completed');
            row.classList.add('set-current');
        }

        scheduleAutoSave();
    }

    document.querySelectorAll('[data-edit-set]').forEach(function (button) {
        button.addEventListener('click', function () {
            const row = button.closest('[data-set-row]');

            if (!row) {
                return;
            }

            toggleSetCompleted(row, false);

            const reps = row.querySelector('input[name$="[reps]"]');

            if (reps) {
                reps.focus();
            }
        });
    });

    document.querySelectorAll('[data-set-row]').forEach(function (row) {
        const touched = row.querySelector('[data-set-touched]');

        if (!touched) {
            return;
        }

        row.querySelectorAll('input, select, textarea').forEach(function (input) {
            if (input === touched) {
                return;
            }

            input.addEventListener('input', function () {
                touched.value = '1';
            });

            input.addEventListener('change', function () {
                touched.value = '1';
            });
        });
    });

    const csrf = @json(csrf_token());
    const workoutForm = document.getElementById('workoutCompleteForm');
    const autosaveUrl = workoutForm?.dataset.autosaveUrl;
    let autoSaveTimer = null;

    function scheduleAutoSave() {
        if (!workoutForm || !autosaveUrl) {
            return;
        }

        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(function () {
            autoSave();
        }, 900);
    }

    function autoSave(keepalive = false) {
        if (!workoutForm || !autosaveUrl) {
            return Promise.resolve();
        }

        const body = new FormData(workoutForm);
        body.set('update_plan', '0');

        return fetch(autosaveUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body,
            keepalive,
        }).catch(function () {
            // Ignore transient save failures; the next change retries.
        });
    }

    if (workoutForm) {
        workoutForm.querySelectorAll('input, textarea, select').forEach(function (input) {
            input.addEventListener('input', scheduleAutoSave);
            input.addEventListener('change', scheduleAutoSave);
        });
    }

    document.querySelectorAll('[data-leave-workout]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            clearTimeout(autoSaveTimer);

            autoSave().finally(function () {
                window.location.href = link.href;
            });
        });
    });

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            autoSave(true);
        }
    });

    window.addEventListener('pagehide', function () {
        autoSave(true);
    });

    function parseNumber(value) {
        let cleaned = String(value ?? '').replace(/[^\d,.-]/g, '');

        if (cleaned === '') {
            return null;
        }

        if (cleaned.includes(',')) {
            cleaned = cleaned.replace(/\./g, '').replace(',', '.');
        } else if ((cleaned.match(/\./g) || []).length > 1) {
            cleaned = cleaned.replace(/\./g, '');
        }

        const number = Number(cleaned);

        return Number.isFinite(number) ? number : null;
    }

    function parseReps(value) {
        const digits = String(value ?? '').replace(/\D/g, '');

        return digits === '' ? null : Number(digits);
    }

    function hasPlanChanges() {
        let changed = false;

        document.querySelectorAll('[data-set-row]').forEach(function (row) {
            const reps = row.querySelector('input[name$="[reps]"]');
            const load = row.querySelector('input[name$="[weight_kg]"]');

            const currentReps = reps ? parseReps(reps.value) : null;
            const initialReps = parseReps(row.dataset.initialReps);

            if (currentReps !== null && initialReps !== null && currentReps !== initialReps) {
                changed = true;
            }

            const currentLoad = load && !load.disabled ? parseNumber(load.value) : null;
            const initialLoad = parseNumber(row.dataset.initialLoad);

            if (currentLoad !== null && initialLoad !== null && Math.abs(currentLoad - initialLoad) > 0.0001) {
                changed = true;
            }
        });

        document.querySelectorAll('[data-cardio]').forEach(function (block) {
            const seconds = Number(block.querySelector('[data-cardio-seconds]')?.value || 0);
            const planned = Number(block.dataset.plannedSeconds || 0);

            if (seconds > 0 && planned > 0 && seconds !== planned) {
                changed = true;
            }
        });

        return changed;
    }

    if (workoutForm) {
        workoutForm.addEventListener('submit', function (event) {
            if (workoutForm.dataset.confirmed === '1' || !hasPlanChanges()) {
                return;
            }

            event.preventDefault();

            function submitWithUpdate(updatePlan) {
                document.getElementById('update_plan').value = updatePlan ? '1' : '0';
                workoutForm.dataset.confirmed = '1';
                workoutForm.submit();
            }

            const message = 'Você alterou repetições, carga ou tempo. Deseja atualizar essas definições no seu plano de treino?';

            if (typeof Swal === 'undefined') {
                submitWithUpdate(window.confirm(message));

                return;
            }

            Swal.fire({
                title: 'Atualizar plano de treino?',
                text: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Atualizar plano',
                cancelButtonText: 'Só registrar',
            }).then(function (result) {
                submitWithUpdate(result.isConfirmed);
            });
        });
    }

    function startRest(seconds) {
        if (!seconds || seconds <= 0) {
            return;
        }

        clearInterval(restInterval);

        const timer = document.getElementById('restTimer');
        const label = document.getElementById('restTimerLabel');

        restDeadline = Date.now() + seconds * 1000;
        restTotal = seconds;
        label.textContent = 'Descanso';
        timer.classList.remove('hidden');

        updateRest();
        restInterval = setInterval(updateRest, 250);
    }

    function updateRest() {
        const remaining = Math.max(0, Math.ceil((restDeadline - Date.now()) / 1000));
        const timer = document.getElementById('restTimer');
        const value = document.getElementById('restTimerValue');

        value.textContent = remaining + 's';

        if (restTotal > 0) {
            const ratio = remaining / restTotal;
            const color = ratio > 0.5
                ? 'var(--color-success)'
                : (ratio > 0.2 ? 'var(--color-warning)' : 'var(--color-danger)');

            value.style.color = color;
            timer.style.borderColor = color;
        }

        if (remaining <= 0) {
            clearInterval(restInterval);
            document.getElementById('restTimerLabel').textContent = 'Descanso concluído!';
            notifyRestFinished();

            setTimeout(function () {
                document.getElementById('restTimer').classList.add('hidden');
            }, 5000);
        }
    }

    function stopRestTimer() {
        clearInterval(restInterval);
        document.getElementById('restTimer').classList.add('hidden');
    }

    function notifyRestFinished() {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            const context = new AudioContext();
            const oscillator = context.createOscillator();
            const gain = context.createGain();

            oscillator.connect(gain);
            gain.connect(context.destination);
            oscillator.frequency.value = 880;
            gain.gain.setValueAtTime(0.15, context.currentTime);
            oscillator.start();
            oscillator.stop(context.currentTime + 0.45);
        } catch (error) {
            // Ignore audio failures (browser restrictions).
        }

        if (navigator.vibrate) {
            navigator.vibrate([200, 100, 200]);
        }
    }

    const replaceModal = document.getElementById('replaceExerciseModal');
    const replaceForm = document.getElementById('replaceExerciseForm');
    const replaceEquipment = document.getElementById('replace_equipment');
    const replaceGroup = document.getElementById('replace_group');
    const replaceSearch = document.getElementById('replace_search');
    const replaceItems = Array.from(document.querySelectorAll('[data-replace-item]'));
    const replaceEmpty = document.getElementById('replaceEmpty');

    window.openReplaceExerciseModal = function (button) {
        if (!replaceModal || !replaceForm) {
            return;
        }

        replaceForm.action = button.dataset.replaceUrl;
        replaceGroup.value = button.dataset.muscleGroup || '';
        replaceEquipment.value = button.dataset.equipment || '';
        replaceSearch.value = '';
        document.getElementById('replaceExerciseName').textContent = button.dataset.exerciseName || '';

        applyReplaceFilters();
        replaceModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        setTimeout(function () {
            replaceSearch.focus();
        }, 100);
    };

    window.closeReplaceExerciseModal = function () {
        if (!replaceModal) {
            return;
        }

        replaceModal.classList.add('hidden');
        document.body.style.overflow = '';
    };

    function applyReplaceFilters() {
        const equipment = replaceEquipment.value;
        const group = replaceGroup.value;
        const term = replaceSearch.value.trim().toLowerCase();
        let visible = 0;

        replaceItems.forEach(function (item) {
            const matches = (!equipment || item.dataset.equipment === equipment)
                && (!group || item.dataset.muscleGroup === group)
                && (!term || item.dataset.name.includes(term));

            item.classList.toggle('hidden', !matches);

            if (matches) {
                visible++;
            }
        });

        replaceEmpty?.classList.toggle('hidden', visible > 0);
    }

    [replaceEquipment, replaceGroup].forEach(function (select) {
        select?.addEventListener('change', applyReplaceFilters);
    });

    replaceSearch?.addEventListener('input', applyReplaceFilters);

    document.querySelectorAll('[data-replace-pick]').forEach(function (button) {
        button.addEventListener('click', function () {
            document.getElementById('replace_exercise_id').value = button.dataset.replacePick;
            replaceForm.submit();
        });
    });

    replaceModal?.addEventListener('click', function (event) {
        if (event.target === replaceModal) {
            closeReplaceExerciseModal();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && replaceModal && !replaceModal.classList.contains('hidden')) {
            closeReplaceExerciseModal();
        }
    });

    document.querySelectorAll('[data-set-row]').forEach(function (row) {
        const flag = row.querySelector('[data-set-completed-flag]');

        if (flag && flag.value === '1') {
            toggleSetCompleted(row, true);
        }
    });
</script>
@endsection
