@extends($layout)

@section('title', 'Montar treino - '.$workoutPlan->name)

@section('content')
<div class="container mx-auto max-w-6xl space-y-6 p-2 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ $builderRoutes['back'] }}" class="text-sm text-text-secondary transition-colors hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                {{ $builderRoutes['backLabel'] ?? 'Voltar para treinos' }}
            </a>
            <h1 class="mt-1 text-2xl font-bold">{{ $workoutPlan->name }}</h1>
            <p class="mt-1 text-text-secondary">
                @if ($workoutPlan->student)
                    {{ $workoutPlan->student->name }}
                @else
                    Treino genérico
                @endif
                @if ($workoutPlan->objective)
                    &middot; {{ $workoutPlan->objective }}
                @endif
            </p>
            @if ($workoutPlan->goal_labels)
                <div class="mt-2 flex flex-wrap gap-1">
                    @foreach ($workoutPlan->goal_labels as $goalLabel)
                        <span class="badge badge-info">{{ $goalLabel }}</span>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="badge {{ $workoutPlan->isPublic() ? 'badge-success' : 'badge-secondary' }}">
                {{ $workoutPlan->isPublic() ? 'Público' : 'Privado' }}
            </span>
            @if ($workoutPlan->isActive())
                <span class="badge badge-success">Ativo</span>
            @else
                <span class="badge badge-secondary">Inativo</span>
            @endif
        </div>
    </div>

    @if ($workoutPlan->notes)
        <div class="card whitespace-pre-line text-sm text-text-secondary">{{ $workoutPlan->notes }}</div>
    @endif

    <div class="flex flex-col gap-2 sm:flex-row sm:justify-end">
        @if ($canManageVisibility && $visibilityUpdate)
            <button type="button" onclick="togglePlanVisibility()" class="btn-outline justify-center">
                <i class="fas fa-eye mr-2"></i>
                {{ $workoutPlan->isPublic() ? 'Tornar privado' : 'Tornar público' }}
            </button>
        @endif
        <button type="button" onclick="openSessionModal()" class="btn-primary justify-center">
            <i class="fas fa-plus mr-2"></i>
            Adicionar sessão
        </button>
    </div>

    @if ($workoutPlan->sessions->isEmpty())
        <div class="card py-12 text-center">
            <i class="fas fa-list-check mb-4 text-4xl text-text-secondary"></i>
            <p class="text-lg font-medium">Nenhuma sessão cadastrada</p>
            <p class="mt-1 text-sm text-text-secondary">Adicione a primeira sessão e depois insira os exercícios.</p>
        </div>
    @else
        <div class="space-y-5">
            @foreach ($workoutPlan->sessions as $session)
                <section class="card card-mobile-tight space-y-4" data-session-card="{{ $session->id }}">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-start">
                        <div class="md:col-span-8">
                            <div class="flex items-center gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg text-sm font-bold"
                                      style="background: var(--color-info-soft); color: var(--color-info);">
                                    {{ $session->order }}
                                </span>
                                <h2 class="text-lg font-semibold">{{ $session->name }}</h2>
                            </div>
                            @if ($session->notes)
                                <p class="mt-1 max-w-2xl whitespace-pre-line text-sm text-text-secondary">{{ $session->notes }}</p>
                            @endif
                            <p class="mt-1 text-xs text-text-secondary">{{ $session->exercises->count() }} exercício(s)</p>
                        </div>

                        <div class="flex items-center justify-end gap-1 md:col-span-4">
                            @if ($session->exercises->count() > 1)
                                <button type="button" data-toggle-reorder="{{ $session->id }}"
                                        class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-cyan"
                                        title="Reordenar exercícios" aria-label="Reordenar exercícios">
                                    <i class="fas fa-sort"></i>
                                </button>
                            @endif
                            <button type="button"
                                    onclick="document.getElementById('session-exercises-{{ $session->id }}').classList.toggle('hidden')"
                                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-cyan"
                                    title="Visualizar exercícios" aria-label="Visualizar exercícios">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button"
                                    data-edit-session="{{ json_encode([
                                        'id' => $session->id,
                                        'name' => $session->name,
                                        'notes' => $session->notes,
                                    ]) }}"
                                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-cyan"
                                    title="Editar sessão" aria-label="Editar sessão">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" onclick="openExercisePicker({{ $session->id }})"
                                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-cyan"
                                    title="Inserir exercícios" aria-label="Inserir exercícios">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <div id="session-exercises-{{ $session->id }}" class="space-y-2" data-exercise-list data-session-id="{{ $session->id }}">
                        @forelse ($session->exercises as $workoutExercise)
                            <div class="flex items-start gap-2.5 rounded-xl border border-custom bg-bg-secondary p-2 sm:p-3"
                                 draggable="true" data-exercise-row data-id="{{ $workoutExercise->id }}">
                                <button type="button" data-grip style="touch-action: none;"
                                        class="mt-1 cursor-grab select-none text-text-secondary"
                                        title="Segure por 1 segundo e arraste para ordenar">
                                    <i class="fas fa-grip-vertical"></i>
                                </button>

                                @if ($workoutExercise->exercise?->image_url)
                                    <img src="{{ $workoutExercise->exercise->image_url }}" alt="{{ $workoutExercise->exercise_name }}"
                                         class="h-14 w-14 flex-shrink-0 rounded-lg border border-custom object-cover">
                                @endif

                                <div class="min-w-0 flex-1">
                                    <p class="font-medium">
                                        <span data-order-label>{{ $workoutExercise->order }}.</span>
                                        {{ $workoutExercise->exercise_name }}
                                    </p>
                                    <span class="badge badge-info mt-1 inline-block">{{ $workoutExercise->muscle_group_label }}</span>
                                    <p class="mt-1 text-xs text-text-secondary">
                                        {{ $workoutExercise->sets }} séries
                                        &middot; {{ $workoutExercise->reps }} repetições
                                        &middot; descanso {{ $workoutExercise->rest_seconds }}s
                                        @if ($workoutExercise->suggested_load)
                                            &middot; {{ $workoutExercise->muscle_group === 'cardio' || $workoutExercise->exercise?->equipment === 'cardio' ? 'tempo' : 'carga' }}: {{ $workoutExercise->suggested_load }}
                                        @endif
                                    </p>
                                    @if ($workoutExercise->notes)
                                        <p class="mt-1 text-xs text-text-secondary">{{ $workoutExercise->notes }}</p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1">
                                    <div class="hidden items-center gap-1" data-reorder-controls>
                                        <button type="button" data-move-up
                                                class="rounded-lg p-1.5 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-cyan disabled:cursor-not-allowed disabled:opacity-30"
                                                title="Mover para cima" aria-label="Mover para cima">
                                            <i class="fas fa-arrow-up"></i>
                                        </button>
                                        <button type="button" data-move-down
                                                class="rounded-lg p-1.5 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-cyan disabled:cursor-not-allowed disabled:opacity-30"
                                                title="Mover para baixo" aria-label="Mover para baixo">
                                            <i class="fas fa-arrow-down"></i>
                                        </button>
                                    </div>
                                    <button type="button"
                                            data-edit-exercise
                                            data-exercise="{{ json_encode([
                                                'id' => $workoutExercise->id,
                                                'sessionId' => $session->id,
                                                'exerciseId' => $workoutExercise->exercise_id,
                                                'name' => $workoutExercise->exercise_name,
                                                'muscleGroup' => $workoutExercise->muscle_group,
                                                'equipment' => $workoutExercise->exercise?->equipment,
                                                'sets' => $workoutExercise->sets,
                                                'reps' => $workoutExercise->reps,
                                                'rest' => $workoutExercise->rest_seconds,
                                                'load' => $workoutExercise->suggested_load,
                                                'notes' => $workoutExercise->notes,
                                                'image' => $workoutExercise->exercise?->image_url,
                                            ]) }}"
                                            class="p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                            title="Editar exercício">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST"
                                          action="{{ str_replace('__ID__', $workoutExercise->id, $builderRoutes['exerciseDestroy']) }}"
                                          onsubmit="return confirm('Remover este exercício da sessão?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                                                title="Remover exercício">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <p class="py-4 text-center text-sm text-text-secondary">
                                Nenhum exercício nesta sessão. Use “Inserir exercícios”.
                            </p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</div>

{{-- Session modal --}}
<div id="sessionModal" class="modal-overlay hidden" role="dialog" aria-modal="true">
    <div class="modal-content">
        <div class="mb-5 flex items-center justify-between">
            <h2 id="sessionModalTitle" class="text-xl font-bold">Nova sessão</h2>
            <button type="button" onclick="closeSessionModal()" class="rounded-lg p-2 text-text-secondary hover:bg-surface">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="sessionForm" method="POST" action="{{ $builderRoutes['sessionStore'] }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="sessionFormMethod" value="POST">

            <div>
                <label for="session_name" class="label-field">Nome <span class="text-red">*</span></label>
                <input type="text" id="session_name" name="name" required maxlength="255" class="input-field"
                       placeholder="Ex.: Treino de pernas 1">
            </div>

            <div>
                <label for="session_notes" class="label-field">Descrição</label>
                <textarea id="session_notes" name="notes" rows="3" maxlength="1000" class="input-field"
                          placeholder="Ex.: destinado aos membros inferiores, com ênfase em glúteos."></textarea>
            </div>

            <div class="flex flex-col-reverse justify-end gap-3 border-t border-custom pt-4 sm:flex-row">
                <button type="button" id="sessionDeleteBtn" onclick="deleteCurrentSession()" class="btn-outline hidden" style="color: var(--color-danger);">
                    <i class="fas fa-trash mr-2"></i>
                    Excluir
                </button>
                <button type="button" onclick="closeSessionModal()" class="btn-outline">Cancelar</button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Salvar sessão
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Exercise picker modal --}}
<div id="exercisePickerModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="exercisePickerTitle">
    <div class="modal-content modal-content-lg flex max-h-[88vh] flex-col p-3 sm:p-6">
        <div class="mb-4 flex items-center justify-between">
            <h2 id="exercisePickerTitle" class="text-xl font-bold">Inserir exercício</h2>
            <button type="button" onclick="closeExercisePicker()" class="rounded-lg p-2 text-text-secondary hover:bg-surface"
                    aria-label="Fechar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label for="picker_equipment" class="label-field">Equipamento</label>
                <select id="picker_equipment" class="input-field">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Exercise::EQUIPMENT_OPTIONS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="picker_group" class="label-field">Grupo muscular</label>
                <select id="picker_group" class="input-field">
                    <option value="">Todos</option>
                    @foreach (\App\Models\Exercise::MUSCLE_GROUPS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3">
            <label for="picker_search" class="label-field">Buscar exercício</label>
            <input type="search" id="picker_search" class="input-field" placeholder="Digite o nome do exercício"
                   autocomplete="off">
        </div>

        <p id="pickerCount" class="mt-2 text-xs text-text-secondary"></p>

        <div id="exercisePickerList" class="mt-2 min-h-0 flex-1 space-y-2 overflow-y-auto">
            @foreach ($exercises as $exercise)
                <div data-picker-item
                     data-equipment="{{ $exercise->equipment }}"
                     data-muscle-group="{{ $exercise->muscle_group }}"
                     data-name="{{ mb_strtolower($exercise->name) }}"
                     class="rounded-xl border border-custom bg-bg-secondary p-2 sm:p-2.5">
                    <div class="flex items-start gap-2.5">
                        @if ($exercise->image_url)
                            <img src="{{ $exercise->image_url }}" alt="{{ $exercise->name }}"
                                 class="h-11 w-11 flex-shrink-0 rounded-lg border border-custom object-cover">
                        @endif

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $exercise->name }}</p>
                            <p class="truncate text-xs text-text-secondary">
                                {{ $exercise->muscle_group_label }} &middot; {{ $exercise->equipment_label }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-2 flex justify-end">
                        <button type="button" data-add-exercise="{{ $exercise->id }}"
                                class="btn-primary p-2" title="Inserir exercício" aria-label="Inserir exercício">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <p id="pickerEmpty" class="hidden py-8 text-center text-sm text-text-secondary">Nenhum exercício encontrado.</p>

        <div class="mt-3 flex justify-end border-t border-custom pt-3">
            <button type="button" onclick="closeExercisePicker()" class="btn-outline">Fechar</button>
        </div>
    </div>
</div>

{{-- Exercise config modal (edit only) --}}
<div id="exerciseConfigModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="exerciseConfigTitle">
    <div class="modal-content modal-content-lg">
        <div class="mb-5 flex items-center justify-between">
            <h2 id="exerciseConfigTitle" class="text-xl font-bold">Editar exercício</h2>
            <button type="button" onclick="closeExerciseConfig()" class="rounded-lg p-2 text-text-secondary hover:bg-surface"
                    aria-label="Fechar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="exerciseForm" method="POST" action="" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="exercise_id" id="exercise_id" value="">

            <div class="rounded-xl border border-custom bg-bg-secondary p-3">
                <p id="configExerciseName" class="font-semibold"></p>
                <p id="configExerciseMeta" class="mt-1 text-xs text-text-secondary"></p>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-12">
                <div class="md:col-span-4">
                    <div class="relative mx-auto aspect-square w-full max-w-[240px] overflow-hidden rounded-xl border border-custom bg-bg-secondary">
                        <img id="exerciseImagePreview" src="" alt="Referência do exercício"
                             class="hidden h-full w-full object-cover">
                        <div id="exerciseImagePlaceholder"
                             class="flex h-full w-full flex-col items-center justify-center gap-2 px-4 text-center text-text-secondary">
                            <i class="fas fa-image text-3xl"></i>
                            <span class="text-xs">Imagem quadrada de referência</span>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-8">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="exercise_sets" class="label-field">Séries <span class="text-red">*</span></label>
                            <input type="number" id="exercise_sets" name="sets" min="1" max="20" value="3" required class="input-field">
                        </div>
                        <div>
                            <label for="exercise_reps" class="label-field">Repetições <span class="text-red">*</span></label>
                            <input type="number" id="exercise_reps" name="reps" min="1" max="1000" value="10" required class="input-field">
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="exercise_rest" class="label-field">Descanso (s) <span class="text-red">*</span></label>
                            <input type="number" id="exercise_rest" name="rest_seconds" min="0" max="600" value="60" required class="input-field">
                        </div>
                        <div>
                            <label for="exercise_load" id="exercise_load_label" class="label-field">Carga</label>
                            <input type="text" id="exercise_load" name="suggested_load" maxlength="80" class="input-field"
                                   inputmode="decimal" placeholder="0,000" data-decimal-mask="3">
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label for="exercise_notes" class="label-field">Observações</label>
                <input type="text" id="exercise_notes" name="notes" maxlength="1000" class="input-field"
                       placeholder="Ex.: cadência controlada, pausa de 1 s...">
            </div>

            <div class="flex justify-end gap-3 border-t border-custom pt-4">
                <button type="button" onclick="closeExerciseConfig()" class="btn-outline">Cancelar</button>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Salvar exercício
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const sessionModal = document.getElementById('sessionModal');
        const sessionForm = document.getElementById('sessionForm');
        const sessionMethod = document.getElementById('sessionFormMethod');
        const sessionTitle = document.getElementById('sessionModalTitle');
        const sessionDeleteBtn = document.getElementById('sessionDeleteBtn');

        const pickerModal = document.getElementById('exercisePickerModal');
        const pickerEquipment = document.getElementById('picker_equipment');
        const pickerGroup = document.getElementById('picker_group');
        const pickerSearch = document.getElementById('picker_search');
        const pickerCount = document.getElementById('pickerCount');
        const pickerEmpty = document.getElementById('pickerEmpty');
        const pickerItems = Array.from(document.querySelectorAll('[data-picker-item]'));

        const configModal = document.getElementById('exerciseConfigModal');
        const exerciseForm = document.getElementById('exerciseForm');
        const configName = document.getElementById('configExerciseName');
        const configMeta = document.getElementById('configExerciseMeta');
        const imagePreview = document.getElementById('exerciseImagePreview');
        const imagePlaceholder = document.getElementById('exerciseImagePlaceholder');
        const loadInput = document.getElementById('exercise_load');
        const loadLabel = document.getElementById('exercise_load_label');

        const routes = @json($builderRoutes);
        const csrf = @json(csrf_token());
        const equipmentLabels = @json(\App\Models\Exercise::EQUIPMENT_OPTIONS);
        const groupLabels = @json(\App\Models\Exercise::MUSCLE_GROUPS);

        let currentSessionId = null;
        let pickerSessionId = null;
        let pickerAdded = 0;

        window.openSessionModal = function (session = null) {
            sessionForm.reset();
            currentSessionId = session ? session.id : null;
            sessionTitle.textContent = session ? 'Editar sessão' : 'Nova sessão';
            sessionMethod.value = session ? 'PUT' : 'POST';
            sessionForm.action = session
                ? routes.sessionUpdate.replace('__ID__', session.id)
                : routes.sessionStore;
            sessionDeleteBtn.classList.toggle('hidden', !session);

            if (session) {
                document.getElementById('session_name').value = session.name ?? '';
                document.getElementById('session_notes').value = session.notes ?? '';
            }

            sessionModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeSessionModal = function () {
            sessionModal.classList.add('hidden');
            document.body.style.overflow = '';
        };

        window.deleteCurrentSession = function () {
            if (!currentSessionId || !confirm('Remover a sessão e todos os exercícios dela?')) {
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = routes.sessionDestroy.replace('__ID__', currentSessionId);
            form.innerHTML = `<input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="_method" value="DELETE">`;
            document.body.appendChild(form);
            form.submit();
        };

        window.openExercisePicker = function (sessionId) {
            pickerSessionId = sessionId;
            pickerEquipment.value = '';
            pickerGroup.value = '';
            pickerSearch.value = '';
            applyPickerFilters();
            pickerModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                pickerSearch.focus();
            }, 100);
        };

        window.closeExercisePicker = function () {
            pickerModal.classList.add('hidden');
            document.body.style.overflow = '';

            if (pickerAdded > 0) {
                window.location.reload();
            }
        };

        function applyPickerFilters() {
            const equipment = pickerEquipment.value;
            const group = pickerGroup.value;
            const term = pickerSearch.value.trim().toLowerCase();
            let visible = 0;

            pickerItems.forEach(function (item) {
                const matches = (!equipment || item.dataset.equipment === equipment)
                    && (!group || item.dataset.muscleGroup === group)
                    && (!term || item.dataset.name.includes(term));

                item.classList.toggle('hidden', !matches);

                if (matches) {
                    visible++;
                }
            });

            pickerCount.textContent = visible + ' exercício(s)';
            pickerEmpty.classList.toggle('hidden', visible > 0);
        }

        [pickerEquipment, pickerGroup].forEach(function (select) {
            select?.addEventListener('change', applyPickerFilters);
        });

        pickerSearch?.addEventListener('input', applyPickerFilters);

        document.querySelectorAll('[data-add-exercise]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!pickerSessionId || button.disabled) {
                    return;
                }

                const original = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                button.title = 'Inserindo...';

                const body = new FormData();
                body.append('_token', csrf);
                body.append('exercise_id', button.dataset.addExercise);
                body.append('sets', '3');
                body.append('reps', '10');
                body.append('rest_seconds', '60');

                fetch(routes.exerciseStore.replace('__ID__', pickerSessionId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                }).then(function (response) {
                    if (!response.ok) {
                        return response.json().then(function (data) {
                            throw new Error(data.message || 'Não foi possível inserir o exercício.');
                        });
                    }

                    pickerAdded++;
                    button.classList.remove('btn-primary');
                    button.classList.add('btn-outline');
                    button.innerHTML = '<i class="fas fa-check"></i>';
                    button.title = 'Inserido';
                }).catch(function (error) {
                    button.disabled = false;
                    button.innerHTML = original;
                    button.title = 'Inserir exercício';
                    alert(error.message);
                });
            });
        });

        window.openExerciseConfigModal = function (exercise) {
            exerciseForm.reset();
            exerciseForm.action = routes.exerciseUpdate.replace('__ID__', exercise.id);
            document.getElementById('exercise_id').value = exercise.exerciseId ?? '';
            configName.textContent = exercise.name ?? '';
            configMeta.textContent = (groupLabels[exercise.muscleGroup] ?? exercise.muscleGroup ?? '')
                + (exercise.equipment ? ' · ' + (equipmentLabels[exercise.equipment] ?? exercise.equipment) : '');

            applyLoadMode(
                exercise.equipment,
                exercise.muscleGroup
            );

            document.getElementById('exercise_sets').value = exercise.sets ?? 3;
            document.getElementById('exercise_reps').value = exercise.reps ?? 10;
            document.getElementById('exercise_rest').value = exercise.rest ?? 60;
            loadInput.value = exercise.load ?? '';
            formatMaskedInput(loadInput);
            document.getElementById('exercise_notes').value = exercise.notes ?? '';
            showExerciseImage(exercise.image);

            configModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeExerciseConfig = function () {
            configModal.classList.add('hidden');
            document.body.style.overflow = '';
        };

        function applyLoadMode(equipment, muscleGroup) {
            if (!loadInput || !loadLabel) {
                return;
            }

            const isCardio = equipment === 'cardio' || muscleGroup === 'cardio';
            const isNone = equipment === 'none';

            loadInput.dataset.timeMask = '';
            loadInput.dataset.decimalMask = '3';
            loadInput.disabled = false;
            loadInput.maxLength = 80;
            loadInput.placeholder = '0,000';
            loadInput.inputMode = 'decimal';
            loadLabel.textContent = 'Carga';

            if (isNone) {
                loadInput.value = '';
                loadInput.disabled = true;
                loadInput.placeholder = 'Não se aplica';
                loadLabel.textContent = 'Carga (não se aplica)';
                return;
            }

            if (isCardio) {
                loadInput.value = '';
                loadInput.dataset.timeMask = '1';
                loadInput.maxLength = 5;
                loadInput.placeholder = '00:00';
                loadInput.inputMode = 'numeric';
                loadLabel.textContent = 'Tempo (mm:ss)';
            }
        }

        function formatMaskedInput(input) {
            if (!input) {
                return;
            }

            let digits = input.value.replace(/\D/g, '');

            if (input.dataset.timeMask === '1') {
                if (!digits) {
                    input.value = '';
                    return;
                }

                digits = digits.slice(0, 4);

                if (digits.length <= 2) {
                    input.value = digits;
                    return;
                }

                const seconds = digits.slice(-2);
                const minutes = digits.slice(0, -2);

                input.value = minutes + ':' + seconds;

                return;
            }

            if (!input.dataset.decimalMask) {
                return;
            }

            const decimals = Number(input.dataset.decimalMask || 3);
            let value = input.value.replace(/\./g, ',').replace(/[^\d,]/g, '');
            const firstComma = value.indexOf(',');

            if (firstComma !== -1) {
                const integerPart = value.slice(0, firstComma);
                const decimalPart = value.slice(firstComma + 1).replace(/,/g, '').slice(0, decimals);

                value = integerPart + ',' + decimalPart;
            }

            input.value = value;
        }

        function showExerciseImage(url) {
            if (url) {
                imagePreview.src = url;
                imagePreview.classList.remove('hidden');
                imagePlaceholder.classList.add('hidden');
            } else {
                imagePreview.src = '';
                imagePreview.classList.add('hidden');
                imagePlaceholder.classList.remove('hidden');
            }
        }

        document.querySelectorAll('[data-edit-session]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.openSessionModal(JSON.parse(button.dataset.editSession));
            });
        });

        document.querySelectorAll('[data-edit-exercise]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.openExerciseConfigModal(JSON.parse(button.dataset.exercise));
            });
        });

        function submitExerciseOrder(list) {
            const ids = Array.from(list.querySelectorAll('[data-exercise-row]'))
                .map((row) => Number(row.dataset.id));

            list.querySelectorAll('[data-exercise-row]').forEach(function (row, index) {
                const label = row.querySelector('[data-order-label]');
                if (label) {
                    label.textContent = (index + 1) + '.';
                }
            });

            fetch(routes.exerciseReorder.replace('__ID__', list.dataset.sessionId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ ids }),
            }).catch(function () {
                window.location.reload();
            });
        }

        function updateMoveButtons(list) {
            const rows = Array.from(list.querySelectorAll('[data-exercise-row]'));

            rows.forEach(function (row, index) {
                const up = row.querySelector('[data-move-up]');
                const down = row.querySelector('[data-move-down]');

                if (up) {
                    up.disabled = index === 0;
                }

                if (down) {
                    down.disabled = index === rows.length - 1;
                }
            });
        }

        document.querySelectorAll('[data-toggle-reorder]').forEach(function (button) {
            button.addEventListener('click', function () {
                const card = document.querySelector('[data-session-card="' + button.dataset.toggleReorder + '"]');

                if (!card) {
                    return;
                }

                const list = card.querySelector('[data-exercise-list]');

                if (!list) {
                    return;
                }

                const active = card.dataset.reorderMode === '1';
                card.dataset.reorderMode = active ? '0' : '1';

                card.querySelectorAll('[data-reorder-controls]').forEach(function (controls) {
                    controls.classList.toggle('hidden', active);
                    controls.classList.toggle('flex', !active);
                });

                button.classList.toggle('bg-surface', !active);
                button.classList.toggle('text-cyan', !active);
                button.classList.toggle('text-text-secondary', active);

                updateMoveButtons(list);
            });
        });

        document.querySelectorAll('[data-move-up]').forEach(function (button) {
            button.addEventListener('click', function () {
                const row = button.closest('[data-exercise-row]');
                const list = row?.parentElement;
                const previous = row?.previousElementSibling;

                if (!row || !list || !previous || !previous.matches('[data-exercise-row]')) {
                    return;
                }

                list.insertBefore(row, previous);
                updateMoveButtons(list);
                submitExerciseOrder(list);
            });
        });

        document.querySelectorAll('[data-move-down]').forEach(function (button) {
            button.addEventListener('click', function () {
                const row = button.closest('[data-exercise-row]');
                const list = row?.parentElement;
                const next = row?.nextElementSibling;

                if (!row || !list || !next || !next.matches('[data-exercise-row]')) {
                    return;
                }

                list.insertBefore(next, row);
                updateMoveButtons(list);
                submitExerciseOrder(list);
            });
        });

        // Desktop drag and drop ordering within each session.
        document.querySelectorAll('[data-exercise-list]').forEach(function (list) {
            let dragged = null;

            list.addEventListener('dragstart', function (event) {
                dragged = event.target.closest('[data-exercise-row]');
                if (dragged) {
                    event.dataTransfer.effectAllowed = 'move';
                }
            });

            list.addEventListener('dragover', function (event) {
                event.preventDefault();
                const target = event.target.closest('[data-exercise-row]');
                if (!dragged || !target || target === dragged) {
                    return;
                }

                const rect = target.getBoundingClientRect();
                const after = (event.clientY - rect.top) > rect.height / 2;
                list.insertBefore(dragged, after ? target.nextSibling : target);
            });

            list.addEventListener('drop', function (event) {
                event.preventDefault();
                submitExerciseOrder(list);
            });
        });

        // Touch ordering: hold the handle for one second to start dragging.
        document.querySelectorAll('[data-exercise-list]').forEach(function (list) {
            list.querySelectorAll('[data-grip]').forEach(function (grip) {
                let pressTimer = null;
                let dragging = null;
                let pointerId = null;

                function activate(event) {
                    dragging = grip.closest('[data-exercise-row]');

                    if (!dragging) {
                        return;
                    }

                    pointerId = event.pointerId;
                    dragging.classList.add('ring-2', 'ring-cyan', 'opacity-80', 'relative', 'z-10');

                    try {
                        grip.setPointerCapture(event.pointerId);
                    } catch (error) {
                        // Ignore unsupported pointer capture.
                    }

                    if (navigator.vibrate) {
                        navigator.vibrate(30);
                    }
                }

                grip.addEventListener('pointerdown', function (event) {
                    if (event.pointerType === 'mouse') {
                        return;
                    }

                    pressTimer = setTimeout(function () {
                        activate(event);
                    }, 1000);
                });

                grip.addEventListener('contextmenu', function (event) {
                    event.preventDefault();
                });

                ['pointerup', 'pointercancel'].forEach(function (name) {
                    grip.addEventListener(name, function () {
                        clearTimeout(pressTimer);

                        if (!dragging) {
                            return;
                        }

                        dragging.classList.remove('ring-2', 'ring-cyan', 'opacity-80', 'relative', 'z-10');
                        dragging = null;
                        pointerId = null;
                        submitExerciseOrder(list);
                    });
                });

                grip.addEventListener('pointermove', function (event) {
                    if (!dragging || event.pointerId !== pointerId) {
                        return;
                    }

                    event.preventDefault();

                    const target = document.elementFromPoint(event.clientX, event.clientY)
                        ?.closest('[data-exercise-row]');

                    if (target && target !== dragging && target.parentElement === list) {
                        const rect = target.getBoundingClientRect();
                        const after = event.clientY > rect.top + rect.height / 2;
                        list.insertBefore(dragging, after ? target.nextSibling : target);
                    }
                });
            });
        });

        // Mask for decimal loads and cardio time fields.
        document.querySelectorAll('[data-decimal-mask], #exercise_load').forEach(function (input) {
            input.addEventListener('input', function () {
                formatMaskedInput(input);
            });
            input.addEventListener('blur', function () {
                formatMaskedInput(input);
            });
        });

        @if ($canManageVisibility && $visibilityUpdate)
            window.togglePlanVisibility = function () {
                fetch(@json($visibilityUpdate), {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                }).then(function () {
                    window.location.reload();
                });
            };
        @endif

        sessionModal?.addEventListener('click', function (event) {
            if (event.target === sessionModal) {
                closeSessionModal();
            }
        });

        pickerModal?.addEventListener('click', function (event) {
            if (event.target === pickerModal) {
                closeExercisePicker();
            }
        });

        configModal?.addEventListener('click', function (event) {
            if (event.target === configModal) {
                closeExerciseConfig();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            if (!pickerModal.classList.contains('hidden')) {
                closeExercisePicker();
            } else if (!configModal.classList.contains('hidden')) {
                closeExerciseConfig();
            }
        });
    })();

</script>
@endsection
