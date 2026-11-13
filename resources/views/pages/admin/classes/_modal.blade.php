{{-- Class create/edit modal --}}
<div id="classModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="classModalTitle">
    <div class="modal-content modal-content-lg">
        <div class="mb-6 flex items-center justify-between">
            <h2 id="classModalTitle" class="text-xl font-bold">Nova turma</h2>
            <button type="button" onclick="closeClassModal()"
                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="classForm" method="POST" action="{{ route('admin.classes.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" id="classFormMethod" value="POST">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="class_name" class="label-field">Nome da turma <span class="text-red">*</span></label>
                    <input type="text" id="class_name" name="name" required maxlength="255" class="input-field"
                           placeholder="Ex.: Musculação Manhã">
                </div>

                <div>
                    <label for="class_unit_id" class="label-field">Unidade <span class="text-red">*</span></label>
                    <select id="class_unit_id" name="unit_id" required class="input-field">
                        <option value="">Selecione a unidade</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected($units->count() === 1)>{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="class_modality_id" class="label-field">Modalidade</label>
                    <select id="class_modality_id" name="modality_id" class="input-field">
                        <option value="">Sem modalidade</option>
                        @foreach ($modalities as $modality)
                            <option value="{{ $modality['id'] }}" data-unit-ids="{{ implode(',', $modality['unit_ids']) }}">
                                {{ $modality['name'] }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-text-secondary">Lista as modalidades disponíveis na unidade selecionada.</p>
                </div>

                <div>
                    <label for="class_teacher_id" class="label-field">Professor</label>
                    <select id="class_teacher_id" name="teacher_id" class="input-field">
                        <option value="">Sem professor</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher['id'] }}" data-unit-id="{{ $teacher['unit_id'] }}">
                                {{ $teacher['name'] }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-text-secondary">Lista os usuários vinculados à unidade selecionada.</p>
                </div>

                <div>
                    <label for="class_weekday" class="label-field">Dia da semana <span class="text-red">*</span></label>
                    <select id="class_weekday" name="weekday" required class="input-field">
                        @foreach (\App\Models\SchoolClass::WEEKDAYS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="class_start_time" class="label-field">Início <span class="text-red">*</span></label>
                        <input type="time" id="class_start_time" name="start_time" required class="input-field">
                    </div>
                    <div>
                        <label for="class_end_time" class="label-field">Fim</label>
                        <input type="time" id="class_end_time" name="end_time" class="input-field">
                    </div>
                </div>

                <div>
                    <label for="class_vacancies" class="label-field">Número de vagas <span class="text-red">*</span></label>
                    <input type="number" id="class_vacancies" name="vacancies" min="1" max="9999" step="1"
                           value="10" required class="input-field">
                    <p class="mt-1 text-xs text-text-secondary">Quantidade de alunos que a turma comporta.</p>
                </div>

                <div>
                    <label for="class_drop_in_price" class="label-field">Valor da aula avulsa</label>
                    <input type="number" id="class_drop_in_price" name="drop_in_price" min="0" step="0.01"
                           class="input-field" placeholder="0,00">
                    <p class="mt-1 text-xs text-text-secondary">Deixe em branco para não vender aula avulsa.</p>
                </div>

                <div>
                    <label for="class_status" class="label-field">Status <span class="text-red">*</span></label>
                    <select id="class_status" name="status" required class="input-field">
                        <option value="active">Ativa</option>
                        <option value="inactive">Inativa</option>
                    </select>
                </div>
            </div>

            <p id="classFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

            <div class="flex justify-end gap-3 border-t border-custom pt-4">
                <button type="button" onclick="closeClassModal()" class="btn-outline">Cancelar</button>
                <button type="submit" class="btn-primary" id="classSubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    <span id="classSubmitBtnText">Salvar turma</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('classModal');
        const form = document.getElementById('classForm');
        const title = document.getElementById('classModalTitle');
        const methodInput = document.getElementById('classFormMethod');
        const submitText = document.getElementById('classSubmitBtnText');
        const submitBtn = document.getElementById('classSubmitBtn');
        const errorBox = document.getElementById('classFormError');
        const unitSelect = document.getElementById('class_unit_id');
        const teacherSelect = document.getElementById('class_teacher_id');
        const teacherOptions = Array.from(teacherSelect.querySelectorAll('option[data-unit-id]'));
        const modalitySelect = document.getElementById('class_modality_id');
        const modalityOptions = Array.from(modalitySelect.querySelectorAll('option[data-unit-ids]'));

        if (!modal || !form) {
            return;
        }

        const fields = {
            name: document.getElementById('class_name'),
            weekday: document.getElementById('class_weekday'),
            start_time: document.getElementById('class_start_time'),
            end_time: document.getElementById('class_end_time'),
            vacancies: document.getElementById('class_vacancies'),
            drop_in_price: document.getElementById('class_drop_in_price'),
            status: document.getElementById('class_status'),
        };

        let currentId = null;

        function filterTeachers() {
            const unitId = unitSelect.value;
            let hasSelected = false;

            teacherOptions.forEach(function (option) {
                const matches = Boolean(unitId) && option.dataset.unitId === unitId;
                option.hidden = !matches;
                option.disabled = !matches;

                if (matches && option.selected) {
                    hasSelected = true;
                }
            });

            if (!hasSelected) {
                teacherSelect.value = '';
            }
        }

        function filterModalities() {
            const unitId = unitSelect.value;

            modalityOptions.forEach(function (option) {
                const unitIds = (option.dataset.unitIds || '').split(',').filter(Boolean);
                const matches = Boolean(unitId) && unitIds.indexOf(unitId) !== -1;
                option.hidden = !matches;
                option.disabled = !matches;
            });

            if (modalitySelect.value && modalitySelect.selectedOptions[0]?.disabled) {
                modalitySelect.value = '';
            }
        }

        function filterUnitOptions() {
            filterTeachers();
            filterModalities();
        }

        unitSelect.addEventListener('change', filterUnitOptions);

        window.openClassModal = function (id = null) {
            form.reset();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            currentId = id;
            filterUnitOptions();

            if (id) {
                title.textContent = 'Editar turma';
                submitText.textContent = 'Atualizar';
                methodInput.value = 'PUT';
                form.action = `{{ route('admin.classes.update', ':id') }}`.replace(':id', id);

                fetch(`{{ route('admin.classes.edit-data', ':id') }}`.replace(':id', id), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Não foi possível carregar a turma.');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        Object.entries(fields).forEach(function ([key, input]) {
                            if (input) {
                                input.value = data[key] ?? '';
                            }
                        });

                        unitSelect.value = data.unit_id ?? '';
                        filterUnitOptions();
                        modalitySelect.value = data.modality_id ?? '';
                        teacherSelect.value = data.teacher_id ?? '';
                    })
                    .catch(function (error) {
                        alertError('Erro', error.message);
                    });
            } else {
                title.textContent = 'Nova turma';
                submitText.textContent = 'Salvar turma';
                methodInput.value = 'POST';
                form.action = '{{ route('admin.classes.store') }}';
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                fields.name.focus();
            }, 100);
        };

        window.closeClassModal = function () {
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

                        throw new Error(result.data.message || 'Não foi possível salvar a turma.');
                    }

                    alertSuccess(currentId ? 'Atualizada!' : 'Cadastrada!', result.data.message);
                    window.closeClassModal();

                    if (typeof window.afterClassSaved === 'function') {
                        window.afterClassSaved(result.data.data);
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
                window.closeClassModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closeClassModal();
            }
        });
    })();
</script>
