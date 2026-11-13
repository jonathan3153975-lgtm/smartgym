@extends($layout)

@section('title', 'Montar treino - '.$workoutPlan->name)

@section('content')
<div class="container mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ $builderRoutes['back'] }}" class="text-sm text-text-secondary transition-colors hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para treinos
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

            @if ($canManageVisibility && $visibilityUpdate)
                <button type="button" onclick="togglePlanVisibility()" class="btn-outline">
                    <i class="fas fa-eye mr-2"></i>
                    {{ $workoutPlan->isPublic() ? 'Tornar privado' : 'Tornar público' }}
                </button>
            @endif
        </div>
    </div>

    @if ($workoutPlan->notes)
        <div class="card whitespace-pre-line text-sm text-text-secondary">{{ $workoutPlan->notes }}</div>
    @endif

    <div class="flex justify-end">
        <button type="button" onclick="openSessionModal()" class="btn-primary">
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
                <section class="card space-y-4" data-session-card="{{ $session->id }}">
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

                        <div class="flex w-full flex-col gap-2 md:col-span-4">
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button"
                                        onclick="document.getElementById('session-exercises-{{ $session->id }}').classList.toggle('hidden')"
                                        class="btn-outline w-full justify-center">
                                    <i class="fas fa-eye mr-2"></i>
                                    Visualizar
                                </button>
                                <button type="button"
                                        data-edit-session="{{ json_encode([
                                            'id' => $session->id,
                                            'name' => $session->name,
                                            'notes' => $session->notes,
                                        ]) }}"
                                        class="btn-outline w-full justify-center">
                                    <i class="fas fa-edit mr-2"></i>
                                    Editar
                                </button>
                            </div>
                            <button type="button" onclick="openExerciseModal({{ $session->id }})" class="btn-primary w-full justify-center">
                                <i class="fas fa-plus mr-2"></i>
                                Inserir exercícios
                            </button>
                        </div>
                    </div>

                    <div id="session-exercises-{{ $session->id }}" class="space-y-2" data-exercise-list data-session-id="{{ $session->id }}">
                        @forelse ($session->exercises as $workoutExercise)
                            <div class="flex items-start gap-3 rounded-xl border border-custom bg-bg-secondary p-3"
                                 draggable="true" data-exercise-row data-id="{{ $workoutExercise->id }}">
                                <button type="button" class="mt-1 cursor-grab text-text-secondary" title="Arrastar para ordenar">
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
                                        <span class="badge badge-info ml-1">{{ $workoutExercise->muscle_group_label }}</span>
                                    </p>
                                    <p class="mt-1 text-xs text-text-secondary">
                                        {{ $workoutExercise->sets }} séries
                                        &middot; {{ $workoutExercise->reps }} repetições
                                        &middot; descanso {{ $workoutExercise->rest_seconds }}s
                                        @if ($workoutExercise->suggested_load)
                                            &middot; carga: {{ $workoutExercise->suggested_load }}
                                        @endif
                                    </p>
                                    @if ($workoutExercise->notes)
                                        <p class="mt-1 text-xs text-text-secondary">{{ $workoutExercise->notes }}</p>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1">
                                    <button type="button"
                                            data-edit-exercise
                                            data-exercise="{{ json_encode([
                                                'id' => $workoutExercise->id,
                                                'sessionId' => $session->id,
                                                'exerciseId' => $workoutExercise->exercise_id,
                                                'name' => $workoutExercise->exercise_name,
                                                'muscleGroup' => $workoutExercise->muscle_group,
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

{{-- Exercise modal --}}
<div id="exerciseModal" class="modal-overlay hidden" role="dialog" aria-modal="true">
    <div class="modal-content modal-content-lg">
        <div class="mb-5 flex items-center justify-between">
            <h2 id="exerciseModalTitle" class="text-xl font-bold">Inserir exercício</h2>
            <button type="button" onclick="closeExerciseModal()" class="rounded-lg p-2 text-text-secondary hover:bg-surface">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="exerciseForm" method="POST" action="" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="exerciseFormMethod" value="POST">

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label for="exercise_equipment_filter" class="label-field">Equipamento</label>
                    <select id="exercise_equipment_filter" class="input-field">
                        <option value="">Todos</option>
                        @foreach (\App\Models\Exercise::EQUIPMENT_OPTIONS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="exercise_group_filter" class="label-field">Grupo muscular</label>
                    <select id="exercise_group_filter" class="input-field">
                        <option value="">Todos</option>
                        @foreach (\App\Models\Exercise::MUSCLE_GROUPS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="exercise_select" class="label-field">Exercício <span class="text-red">*</span></label>
                <select id="exercise_select" name="exercise_id" required class="input-field">
                    <option value="">Selecione o exercício</option>
                    @foreach ($exercises as $exercise)
                        <option value="{{ $exercise->id }}"
                                data-muscle-group="{{ $exercise->muscle_group }}"
                                data-equipment="{{ $exercise->equipment }}"
                                data-image="{{ $exercise->image_url }}">
                            {{ $exercise->name }}{{ $exercise->isActive() ? '' : ' (inativo)' }}
                        </option>
                    @endforeach
                </select>
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
                            <label for="exercise_load" class="label-field">Carga</label>
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
                <button type="button" onclick="closeExerciseModal()" class="btn-outline">Cancelar</button>
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
        const exerciseModal = document.getElementById('exerciseModal');
        const exerciseForm = document.getElementById('exerciseForm');
        const exerciseMethod = document.getElementById('exerciseFormMethod');
        const exerciseTitle = document.getElementById('exerciseModalTitle');
        const exerciseSelect = document.getElementById('exercise_select');
        const equipmentFilter = document.getElementById('exercise_equipment_filter');
        const groupFilter = document.getElementById('exercise_group_filter');
        const imagePreview = document.getElementById('exerciseImagePreview');
        const imagePlaceholder = document.getElementById('exerciseImagePlaceholder');
        const routes = @json($builderRoutes);
        const csrf = @json(csrf_token());

        let currentSessionId = null;

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

        window.openExerciseModal = function (sessionId, exercise = null) {
            exerciseForm.reset();
            exerciseTitle.textContent = exercise ? 'Editar exercício' : 'Inserir exercício';
            exerciseMethod.value = exercise ? 'PUT' : 'POST';
            exerciseForm.action = exercise
                ? routes.exerciseUpdate.replace('__ID__', exercise.id)
                : routes.exerciseStore.replace('__ID__', sessionId);

            equipmentFilter.value = '';
            groupFilter.value = '';
            filterBuilderPairs();
            filterExerciseOptions();

            if (exercise) {
                exerciseSelect.value = exercise.exerciseId ?? '';
                document.getElementById('exercise_sets').value = exercise.sets ?? 3;
                document.getElementById('exercise_reps').value = exercise.reps ?? 10;
                document.getElementById('exercise_rest').value = exercise.rest ?? 60;
                document.getElementById('exercise_load').value = exercise.load ?? '';
                document.getElementById('exercise_notes').value = exercise.notes ?? '';
                showExerciseImage(exercise.image);
            } else {
                exerciseSelect.value = '';
                document.getElementById('exercise_sets').value = 3;
                document.getElementById('exercise_reps').value = 10;
                document.getElementById('exercise_rest').value = 60;
                document.getElementById('exercise_load').value = '';
                document.getElementById('exercise_notes').value = '';
                showExerciseImage(null);
            }

            exerciseModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeExerciseModal = function () {
            exerciseModal.classList.add('hidden');
            document.body.style.overflow = '';
        };

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

        function filterExerciseOptions() {
            const equipment = equipmentFilter.value;
            const group = groupFilter.value;

            Array.from(exerciseSelect.options).forEach(function (option) {
                if (!option.value) {
                    return;
                }

                const matchesEquipment = !equipment || option.dataset.equipment === equipment;
                const matchesGroup = !group || option.dataset.muscleGroup === group;
                option.hidden = !(matchesEquipment && matchesGroup);
            });

            if (exerciseSelect.value && exerciseSelect.selectedOptions[0]?.hidden) {
                exerciseSelect.value = '';
                showExerciseImage(null);
            }
        }

        function filterBuilderPairs() {
            const equipment = equipmentFilter.value;
            const group = groupFilter.value;

            const pairs = Array.from(exerciseSelect.options)
                .filter((option) => option.value)
                .map((option) => ({ equipment: option.dataset.equipment, group: option.dataset.muscleGroup }));

            const equipmentForGroup = group
                ? new Set(pairs.filter((pair) => pair.group === group).map((pair) => pair.equipment))
                : null;
            const groupsForEquipment = equipment
                ? new Set(pairs.filter((pair) => pair.equipment === equipment).map((pair) => pair.group))
                : null;

            Array.from(equipmentFilter.options).forEach(function (option) {
                if (!option.value) {
                    return;
                }

                const matches = !equipmentForGroup || equipmentForGroup.has(option.value);
                option.hidden = !matches;
                option.disabled = !matches;
            });

            Array.from(groupFilter.options).forEach(function (option) {
                if (!option.value) {
                    return;
                }

                const matches = !groupsForEquipment || groupsForEquipment.has(option.value);
                option.hidden = !matches;
                option.disabled = !matches;
            });

            if (equipmentFilter.value && equipmentFilter.selectedOptions[0]?.disabled) {
                equipmentFilter.value = '';
            }

            if (groupFilter.value && groupFilter.selectedOptions[0]?.disabled) {
                groupFilter.value = '';
            }
        }

        function applyBuilderFilters() {
            filterBuilderPairs();
            filterExerciseOptions();
        }

        equipmentFilter?.addEventListener('change', applyBuilderFilters);
        groupFilter?.addEventListener('change', applyBuilderFilters);

        exerciseSelect?.addEventListener('change', function () {
            const option = this.selectedOptions[0];
            showExerciseImage(option?.dataset.image || null);
        });

        document.querySelectorAll('[data-edit-session]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.openSessionModal(JSON.parse(button.dataset.editSession));
            });
        });

        document.querySelectorAll('[data-edit-exercise]').forEach(function (button) {
            button.addEventListener('click', function () {
                const data = JSON.parse(button.dataset.exercise);
                window.openExerciseModal(data.sessionId, data);
            });
        });

        // Drag and drop ordering within each session.
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
            });
        });

        // Currency-like mask with 3 decimals (comma as separator).
        document.querySelectorAll('[data-decimal-mask]').forEach(function (input) {
            const decimals = Number(input.dataset.decimalMask || 3);

            function formatLoad() {
                let digits = input.value.replace(/\D/g, '');

                if (digits === '') {
                    input.value = '';
                    return;
                }

                digits = digits.slice(0, 9).padStart(decimals + 1, '0');

                const integerPart = Number(digits.slice(0, -decimals)).toLocaleString('pt-BR');
                input.value = integerPart + ',' + digits.slice(-decimals);
            }

            input.addEventListener('input', formatLoad);
            input.addEventListener('blur', formatLoad);
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

        exerciseModal?.addEventListener('click', function (event) {
            if (event.target === exerciseModal) {
                closeExerciseModal();
            }
        });
    })();
</script>
@endsection
