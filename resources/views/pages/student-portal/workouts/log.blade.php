@extends('layouts.student')

@section('title', 'Treino em andamento')

@section('content')
@php
    $rowIndex = 0;
@endphp

<div class="mx-auto w-full max-w-4xl space-y-6">
    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Treino em andamento</p>
                <h1 class="mt-1 text-2xl font-bold">{{ $workoutLog->session_name }}</h1>
                <p class="mt-1 text-sm text-text-secondary">
                    Iniciado em {{ $workoutLog->started_at->format('d/m/Y H:i') }}
                </p>
                <p class="mt-1 text-sm text-text-secondary">
                    Tempo de treino: <span id="workoutElapsed" class="font-mono font-medium text-text-primary">00:00</span>
                </p>
            </div>

            <a href="{{ route('student.portal.workouts.index', $network) }}" class="btn-outline self-start sm:self-auto">
                <i class="fas fa-clock-rotate-left mr-2"></i>
                Meus treinos
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('student.portal.workouts.complete', [$network, $workoutLog]) }}" class="space-y-6">
        @csrf

        @foreach ($exercises as $workoutExercise)
            <section class="card space-y-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-3">
                        @if ($workoutExercise->exercise?->image_url)
                            <img src="{{ $workoutExercise->exercise->image_url }}" alt="{{ $workoutExercise->exercise_name }}"
                                 class="h-16 w-16 flex-shrink-0 rounded-xl border border-custom object-cover">
                        @endif
                        <div>
                            <p class="text-xs text-text-secondary">Exercício {{ $workoutExercise->order }}</p>
                            <h2 class="mt-1 text-lg font-semibold">{{ $workoutExercise->exercise_name }}</h2>
                            <p class="mt-1 text-xs text-text-secondary">
                                {{ $workoutExercise->muscle_group_label }}
                                &middot; {{ $workoutExercise->sets }} séries
                                &middot; {{ $workoutExercise->reps }} repetições
                                @if ($workoutExercise->suggested_load)
                                    &middot; carga sugerida: {{ $workoutExercise->suggested_load }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <button type="button"
                            onclick="startRest({{ $workoutExercise->rest_seconds }})"
                            class="btn-outline self-start sm:self-auto"
                            @disabled($workoutExercise->rest_seconds <= 0)>
                        <i class="fas fa-stopwatch mr-2"></i>
                        Descanso {{ $workoutExercise->rest_seconds }}s
                    </button>
                </div>

                @if ($workoutExercise->notes)
                    <p class="text-sm text-text-secondary">
                        <i class="fas fa-circle-info mr-1 text-cyan"></i>
                        {{ $workoutExercise->notes }}
                    </p>
                @endif

                <div class="overflow-x-auto">
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th class="w-24">Série</th>
                                <th>Repetições</th>
                                <th>Carga (kg)</th>
                                <th class="w-36">Execução</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($setNumber = 1; $setNumber <= $workoutExercise->sets; $setNumber++)
                                <tr>
                                    <td class="font-medium">{{ $setNumber }}ª</td>
                                    <td>
                                        <input type="hidden" name="sets[{{ $rowIndex }}][workout_exercise_id]" value="{{ $workoutExercise->id }}">
                                        <input type="hidden" name="sets[{{ $rowIndex }}][exercise_id]" value="{{ $workoutExercise->exercise_id }}">
                                        <input type="hidden" name="sets[{{ $rowIndex }}][exercise_name]" value="{{ $workoutExercise->exercise_name }}">
                                        <input type="hidden" name="sets[{{ $rowIndex }}][muscle_group]" value="{{ $workoutExercise->muscle_group }}">
                                        <input type="hidden" name="sets[{{ $rowIndex }}][set_number]" value="{{ $setNumber }}">
                                        <input type="hidden" name="sets[{{ $rowIndex }}][rest_seconds]" value="{{ $workoutExercise->rest_seconds }}">

                                        <input type="number" name="sets[{{ $rowIndex }}][reps]" min="0" max="1000"
                                               class="input-field" placeholder="{{ $workoutExercise->reps }}"
                                               aria-label="Repetições da série {{ $setNumber }}">
                                    </td>
                                    <td>
                                        <input type="number" name="sets[{{ $rowIndex }}][weight_kg]" min="0" max="999.99" step="0.001"
                                               class="input-field" placeholder="{{ $workoutExercise->suggested_load ?: '0' }}"
                                               aria-label="Carga da série {{ $setNumber }}">
                                    </td>
                                    <td>
                                        <button type="button"
                                                class="btn-outline w-full complete-set-btn"
                                                data-rest="{{ $workoutExercise->rest_seconds }}"
                                                onclick="completeSet(this)">
                                            <i class="fas fa-check mr-1"></i>
                                            Concluir série
                                        </button>
                                    </td>
                                </tr>
                                @php $rowIndex++; @endphp
                            @endfor
                        </tbody>
                    </table>
                </div>
            </section>
        @endforeach

        <section class="card">
            <label for="workout_notes" class="label-field">Observações do treino</label>
            <textarea id="workout_notes" name="notes" rows="3" maxlength="2000" class="input-field"
                      placeholder="Como foi o treino? Alguma dificuldade ou ajuste?">{{ $workoutLog->notes }}</textarea>
        </section>

        <div class="flex flex-col-reverse justify-end gap-3 sm:flex-row">
            <a href="{{ route('student.portal.workouts.index', $network) }}" class="btn-outline justify-center">
                Voltar depois
            </a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-flag-checkered mr-2"></i>
                Concluir treino
            </button>
        </div>
    </form>
</div>

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

    let restInterval = null;
    let restDeadline = 0;

    function completeSet(button) {
        const row = button.closest('tr');

        if (row) {
            row.style.background = 'color-mix(in srgb, var(--color-success) 12%, transparent)';
        }

        button.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Feita';
        button.disabled = true;
        button.style.color = 'var(--color-success)';

        startRest(Number(button.dataset.rest || 0));
    }

    function startRest(seconds) {
        if (!seconds || seconds <= 0) {
            return;
        }

        clearInterval(restInterval);

        const timer = document.getElementById('restTimer');
        const label = document.getElementById('restTimerLabel');

        restDeadline = Date.now() + seconds * 1000;
        label.textContent = 'Descanso';
        timer.classList.remove('hidden');

        updateRest();
        restInterval = setInterval(updateRest, 250);
    }

    function updateRest() {
        const remaining = Math.max(0, Math.ceil((restDeadline - Date.now()) / 1000));

        document.getElementById('restTimerValue').textContent = remaining + 's';

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
</script>
@endsection
