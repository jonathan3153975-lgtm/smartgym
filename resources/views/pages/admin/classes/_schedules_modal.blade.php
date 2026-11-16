<div id="classScheduleModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="classScheduleTitle">
    <div class="modal-content modal-content-lg">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Grade de horários</p>
                <h2 id="classScheduleTitle" class="mt-1 text-xl font-bold">Turma</h2>
                <p id="classScheduleSubtitle" class="mt-1 text-sm text-text-secondary"></p>
            </div>
            <button type="button" class="rounded-lg p-2 text-text-secondary hover:bg-surface" onclick="closeClassScheduleModal()" aria-label="Fechar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="classScheduleRows" class="space-y-3"></div>

        <button type="button" id="addClassScheduleRow" class="btn-outline mt-3">
            <i class="fas fa-plus mr-2"></i>
            Adicionar horário
        </button>

        <p id="classScheduleError" class="mt-3 hidden rounded-lg px-3 py-2 text-sm"
           style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

        <div class="mt-5 flex justify-end gap-3 border-t border-custom pt-4">
            <button type="button" class="btn-outline" onclick="closeClassScheduleModal()">Cancelar</button>
            <button type="button" id="saveClassSchedules" class="btn-primary">
                <i class="fas fa-save mr-2"></i>
                Salvar grade
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('classScheduleModal');
        const rowsContainer = document.getElementById('classScheduleRows');
        const errorBox = document.getElementById('classScheduleError');
        const title = document.getElementById('classScheduleTitle');
        const subtitle = document.getElementById('classScheduleSubtitle');
        const addButton = document.getElementById('addClassScheduleRow');
        const saveButton = document.getElementById('saveClassSchedules');

        const weekdays = @json(\App\Models\SchoolClass::WEEKDAYS, JSON_UNESCAPED_UNICODE);
        let currentClassId = null;
        let teachers = [];

        if (!modal) {
            return;
        }

        function teacherOptions(selectedId) {
            return '<option value="">Sem professor</option>'
                + teachers.map((teacher) => `<option value="${teacher.id}" ${Number(selectedId) === Number(teacher.id) ? 'selected' : ''}>${escapeHtml(teacher.name)}</option>`).join('');
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[char]));
        }

        function addHour(time) {
            if (! time) {
                return '';
            }

            const [hour, minute] = time.split(':').map(Number);
            const total = (hour * 60 + minute + 60) % (24 * 60);

            return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
        }

        function addRow(data = {}) {
            const row = document.createElement('div');
            row.className = 'relative rounded-xl border border-custom bg-bg-secondary p-3 pt-9';
            row.dataset.scheduleId = data.id ?? '';
            row.innerHTML = `
                <button type="button" class="remove-schedule-row absolute right-2 top-2 p-1 text-xs text-red transition-colors hover:text-red/80" title="Excluir horário">
                    <i class="fas fa-trash"></i>
                </button>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-12">
                    <div class="md:col-span-6">
                        <label class="label-field">Dia da semana</label>
                        <select data-field="weekday" class="input-field">
                            ${Object.entries(weekdays).map(([value, label]) => `<option value="${value}" ${Number(data.weekday) === Number(value) ? 'selected' : ''}>${escapeHtml(label)}</option>`).join('')}
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="label-field">Início</label>
                        <input type="time" data-field="start_time" value="${escapeHtml(data.start_time ?? '')}" class="input-field">
                    </div>
                    <div class="md:col-span-3">
                        <label class="label-field">Fim</label>
                        <input type="time" data-field="end_time" value="${escapeHtml(data.end_time ?? '')}" class="input-field">
                    </div>
                    <div class="md:col-span-8">
                        <label class="label-field">Professor</label>
                        <select data-field="person_id" class="input-field">${teacherOptions(data.person_id)}</select>
                    </div>
                    <div class="md:col-span-4">
                        <label class="label-field">Vagas</label>
                        <input type="number" min="1" max="9999" step="1" data-field="vacancies" value="${escapeHtml(data.vacancies ?? 10)}" class="input-field">
                    </div>
                </div>
            `;

            rowsContainer.appendChild(row);
        }

        rowsContainer.addEventListener('change', function (event) {
            if (event.target.matches('[data-field="start_time"]')) {
                const row = event.target.closest('[data-schedule-id]');
                const endInput = row?.querySelector('[data-field="end_time"]');

                if (endInput) {
                    endInput.value = addHour(event.target.value);
                }
            }
        });

        rowsContainer.addEventListener('click', function (event) {
            const remove = event.target.closest('.remove-schedule-row');

            if (remove) {
                remove.closest('[data-schedule-id]')?.remove();
            }
        });

        addButton.addEventListener('click', function () {
            addRow();
        });

        window.openClassScheduleModal = function (classId) {
            currentClassId = classId;
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            rowsContainer.innerHTML = '';
            title.textContent = 'Carregando...';
            subtitle.textContent = '';
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            fetch(`{{ url('admin/classes') }}/${classId}/schedules-data`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Não foi possível carregar a grade da turma.');
                    }

                    return response.json();
                })
                .then(function (data) {
                    title.textContent = data.class?.name ?? 'Turma';
                    subtitle.textContent = data.class?.unit_name ?? '';
                    teachers = Array.isArray(data.teachers) ? data.teachers : [];

                    (data.schedules ?? []).forEach((schedule) => addRow(schedule));

                    if (! data.schedules || data.schedules.length === 0) {
                        addRow();
                    }
                })
                .catch(function (error) {
                    errorBox.textContent = error.message;
                    errorBox.classList.remove('hidden');
                });
        };

        window.closeClassScheduleModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            currentClassId = null;
        };

        saveButton.addEventListener('click', function () {
            if (! currentClassId) {
                return;
            }

            const schedules = Array.from(rowsContainer.querySelectorAll('[data-schedule-id]')).map((row) => ({
                id: row.dataset.scheduleId ? Number(row.dataset.scheduleId) : null,
                weekday: Number(row.querySelector('[data-field="weekday"]').value),
                start_time: row.querySelector('[data-field="start_time"]').value,
                end_time: row.querySelector('[data-field="end_time"]').value || null,
                person_id: row.querySelector('[data-field="person_id"]').value
                    ? Number(row.querySelector('[data-field="person_id"]').value)
                    : null,
                vacancies: Number(row.querySelector('[data-field="vacancies"]').value),
            }));

            saveButton.disabled = true;
            errorBox.classList.add('hidden');

            fetch(`{{ url('admin/classes') }}/${currentClassId}/schedules`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.SmartGym.csrfToken,
                },
                body: JSON.stringify({ schedules }),
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (! result.ok) {
                        const errors = result.data.errors ? Object.values(result.data.errors).flat().join(' ') : '';
                        throw new Error(errors || result.data.message || 'Não foi possível salvar a grade.');
                    }

                    alertSuccess('Grade salva!', result.data.message);
                    window.closeClassScheduleModal();

                    if (typeof window.refreshClassesTable === 'function') {
                        window.refreshClassesTable();
                    } else {
                        window.location.reload();
                    }
                })
                .catch(function (error) {
                    errorBox.textContent = error.message;
                    errorBox.classList.remove('hidden');
                })
                .finally(function () {
                    saveButton.disabled = false;
                });
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                window.closeClassScheduleModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && ! modal.classList.contains('hidden')) {
                window.closeClassScheduleModal();
            }
        });
    })();
</script>
