{{-- Exercise create/edit modal --}}
<div id="exerciseModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="exerciseModalTitle">
    <div class="modal-content modal-content-lg">
        <div class="mb-6 flex items-center justify-between">
            <h2 id="exerciseModalTitle" class="text-xl font-bold">Novo exercício</h2>
            <button type="button" onclick="closeExerciseModal()"
                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="exerciseForm" method="POST" action="{{ route('admin.exercises.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" id="exerciseFormMethod" value="POST">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="exercise_name" class="label-field">Nome do exercício <span class="text-red">*</span></label>
                    <input type="text" id="exercise_name" name="name" required maxlength="255" class="input-field"
                           placeholder="Ex.: Supino reto">
                </div>

                <div>
                    <label for="exercise_muscle_group" class="label-field">Grupo muscular <span class="text-red">*</span></label>
                    <select id="exercise_muscle_group" name="muscle_group" required class="input-field">
                        <option value="">Selecione</option>
                        @foreach (\App\Models\Exercise::MUSCLE_GROUPS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="exercise_equipment" class="label-field">Equipamento</label>
                    <select id="exercise_equipment" name="equipment" class="input-field">
                        <option value="">Selecione</option>
                        @foreach (\App\Models\Exercise::EQUIPMENT_OPTIONS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label for="exercise_instructions" class="label-field">Instruções / observações</label>
                    <textarea id="exercise_instructions" name="instructions" rows="3" maxlength="2000" class="input-field"
                              placeholder="Execução, cuidados e dicas para o exercício."></textarea>
                </div>

                <div class="sm:col-span-2">
                    <label for="exercise_image" class="label-field">Imagem ou GIF de referência</label>
                    <input type="file" id="exercise_image" name="image"
                           accept="image/png,image/jpeg,image/webp,image/gif"
                           class="input-field file:mr-3 file:rounded-md file:border-0 file:bg-cyan/10 file:px-3 file:py-1 file:text-sm file:font-medium file:text-cyan">
                    <p class="mt-1 text-xs text-text-secondary">
                        A imagem deve ser quadrada (1:1), em PNG, JPG, WebP ou GIF de até 4 MB. Ela aparece no painel e no portal do aluno.
                    </p>

                    <div id="exerciseImagePreviewWrapper" class="mt-3 hidden">
                        <div class="aspect-square w-40 overflow-hidden rounded-xl border border-custom bg-bg-secondary">
                            <img id="exerciseImagePreview" src="" alt="Prévia do exercício"
                                 class="h-full w-full object-cover">
                        </div>
                        <label class="mt-2 flex cursor-pointer items-center gap-2 text-sm text-red">
                            <input type="checkbox" name="remove_image" value="1">
                            Remover imagem atual
                        </label>
                    </div>
                </div>

                <div>
                    <label for="exercise_status" class="label-field">Status <span class="text-red">*</span></label>
                    <select id="exercise_status" name="status" required class="input-field">
                        <option value="active">Ativo</option>
                        <option value="inactive">Inativo</option>
                    </select>
                </div>
            </div>

            <p id="exerciseFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

            <div class="flex justify-end gap-3 border-t border-custom pt-4">
                <button type="button" onclick="closeExerciseModal()" class="btn-outline">Cancelar</button>
                <button type="submit" class="btn-primary" id="exerciseSubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    <span id="exerciseSubmitBtnText">Salvar exercício</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('exerciseModal');
        const form = document.getElementById('exerciseForm');
        const title = document.getElementById('exerciseModalTitle');
        const methodInput = document.getElementById('exerciseFormMethod');
        const submitText = document.getElementById('exerciseSubmitBtnText');
        const submitBtn = document.getElementById('exerciseSubmitBtn');
        const errorBox = document.getElementById('exerciseFormError');

        if (!modal || !form) {
            return;
        }

        const fields = {
            name: document.getElementById('exercise_name'),
            muscle_group: document.getElementById('exercise_muscle_group'),
            equipment: document.getElementById('exercise_equipment'),
            instructions: document.getElementById('exercise_instructions'),
            status: document.getElementById('exercise_status'),
        };

        const imageInput = document.getElementById('exercise_image');
        const imagePreview = document.getElementById('exerciseImagePreview');
        const imagePreviewWrapper = document.getElementById('exerciseImagePreviewWrapper');

        function showExerciseImage(url) {
            if (!imagePreview || !imagePreviewWrapper) {
                return;
            }

            if (url) {
                imagePreview.src = url;
                imagePreviewWrapper.classList.remove('hidden');
            } else {
                imagePreview.src = '';
                imagePreviewWrapper.classList.add('hidden');
            }
        }

        imageInput?.addEventListener('change', function () {
            const file = this.files && this.files[0];

            if (file) {
                showExerciseImage(URL.createObjectURL(file));
            }
        });

        let currentId = null;

        window.openExerciseModal = function (id = null) {
            form.reset();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            showExerciseImage(null);
            currentId = id;

            if (id) {
                title.textContent = 'Editar exercício';
                submitText.textContent = 'Atualizar';
                methodInput.value = 'PUT';
                form.action = `{{ route('admin.exercises.update', ':id') }}`.replace(':id', id);

                fetch(`{{ route('admin.exercises.edit-data', ':id') }}`.replace(':id', id), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Não foi possível carregar o exercício.');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        Object.entries(fields).forEach(function ([key, input]) {
                            if (input) {
                                input.value = data[key] ?? '';
                            }
                        });

                        showExerciseImage(data.image_url);
                    })
                    .catch(function (error) {
                        alertError('Erro', error.message);
                    });
            } else {
                title.textContent = 'Novo exercício';
                submitText.textContent = 'Salvar exercício';
                methodInput.value = 'POST';
                form.action = '{{ route('admin.exercises.store') }}';
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                fields.name.focus();
            }, 100);
        };

        window.closeExerciseModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            currentId = null;
        };

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            errorBox.classList.add('hidden');
            submitBtn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, status: response.status, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.ok) {
                        if (result.status === 422 && result.data.errors) {
                            throw new Error(Object.values(result.data.errors).flat().join(' '));
                        }

                        throw new Error(result.data.message || 'Não foi possível salvar o exercício.');
                    }

                    alertSuccess(currentId ? 'Atualizado!' : 'Cadastrado!', result.data.message);
                    window.closeExerciseModal();

                    if (typeof window.afterExerciseSaved === 'function') {
                        window.afterExerciseSaved(result.data.data);
                    } else {
                        setTimeout(function () {
                            window.location.reload();
                        }, 600);
                    }
                })
                .catch(function (error) {
                    errorBox.textContent = error.message;
                    errorBox.classList.remove('hidden');
                })
                .finally(function () {
                    submitBtn.disabled = false;
                });
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                window.closeExerciseModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closeExerciseModal();
            }
        });
    })();
</script>
