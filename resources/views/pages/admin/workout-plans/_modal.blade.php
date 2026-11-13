{{-- Workout plan create/edit modal --}}
<div id="workoutPlanModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="workoutPlanModalTitle">
    <div class="modal-content modal-content-lg">
        <div class="mb-6 flex items-center justify-between">
            <h2 id="workoutPlanModalTitle" class="text-xl font-bold">Novo treino</h2>
            <button type="button" onclick="closeWorkoutPlanModal()"
                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="workoutPlanForm" method="POST" action="{{ route('admin.workout-plans.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" id="workoutPlanFormMethod" value="POST">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="workout_plan_name" class="label-field">Nome do plano <span class="text-red">*</span></label>
                    <input type="text" id="workout_plan_name" name="name" required maxlength="255" class="input-field"
                           placeholder="Ex.: Hipertrofia - Fase 1">
                </div>

                <div class="sm:col-span-2">
                    <label for="workout_plan_objective" class="label-field">Objetivo / descrição curta</label>
                    <input type="text" id="workout_plan_objective" name="objective" maxlength="255" class="input-field"
                           placeholder="Ex.: ganho de massa muscular, condicionamento, reabilitação">
                </div>

                <div class="sm:col-span-2">
                    <label for="workout_plan_notes" class="label-field">Observações</label>
                    <textarea id="workout_plan_notes" name="notes" rows="3" maxlength="2000" class="input-field"
                              placeholder="Orientações gerais para quem utilizar o treino."></textarea>
                </div>

                <div>
                    <label for="workout_plan_status" class="label-field">Status <span class="text-red">*</span></label>
                    <select id="workout_plan_status" name="status" required class="input-field">
                        <option value="active">Ativo</option>
                        <option value="inactive">Inativo</option>
                    </select>
                </div>

                <div class="sm:col-span-2 rounded-xl border border-custom bg-bg-secondary p-3 text-xs text-text-secondary">
                    <i class="fas fa-globe mr-1 text-cyan"></i>
                    Treinos criados pela equipe são <strong>públicos</strong> e podem ser aplicados a qualquer aluno ou copiados por ele.
                </div>
            </div>

            <p id="workoutPlanFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

            <div class="flex justify-end gap-3 border-t border-custom pt-4">
                <button type="button" onclick="closeWorkoutPlanModal()" class="btn-outline">Cancelar</button>
                <button type="submit" class="btn-primary" id="workoutPlanSubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    <span id="workoutPlanSubmitBtnText">Salvar treino</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('workoutPlanModal');
        const form = document.getElementById('workoutPlanForm');
        const title = document.getElementById('workoutPlanModalTitle');
        const methodInput = document.getElementById('workoutPlanFormMethod');
        const submitText = document.getElementById('workoutPlanSubmitBtnText');
        const submitBtn = document.getElementById('workoutPlanSubmitBtn');
        const errorBox = document.getElementById('workoutPlanFormError');

        if (!modal || !form) {
            return;
        }

        const fields = {
            name: document.getElementById('workout_plan_name'),
            objective: document.getElementById('workout_plan_objective'),
            notes: document.getElementById('workout_plan_notes'),
            status: document.getElementById('workout_plan_status'),
        };

        let currentId = null;

        window.openWorkoutPlanModal = function (id = null) {
            form.reset();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            currentId = id;

            if (id) {
                title.textContent = 'Editar plano de treino';
                submitText.textContent = 'Atualizar';
                methodInput.value = 'PUT';
                form.action = `{{ route('admin.workout-plans.update', ':id') }}`.replace(':id', id);

                fetch(`{{ route('admin.workout-plans.edit-data', ':id') }}`.replace(':id', id), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Não foi possível carregar o plano.');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        Object.entries(fields).forEach(function ([key, input]) {
                            if (input) {
                                input.value = data[key] ?? '';
                            }
                        });
                    })
                    .catch(function (error) {
                        alertError('Erro', error.message);
                    });
            } else {
                title.textContent = 'Novo treino';
                submitText.textContent = 'Salvar treino';
                methodInput.value = 'POST';
                form.action = '{{ route('admin.workout-plans.store') }}';
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                fields.name.focus();
            }, 100);
        };

        window.closeWorkoutPlanModal = function () {
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

                        throw new Error(result.data.message || 'Não foi possível salvar o plano.');
                    }

                    alertSuccess(currentId ? 'Atualizado!' : 'Cadastrado!', result.data.message);
                    window.closeWorkoutPlanModal();

                    if (typeof window.afterWorkoutPlanSaved === 'function') {
                        window.afterWorkoutPlanSaved(result.data.data);
                    } else {
                        window.location.href = `{{ url('admin/workout-plans') }}/${result.data.data.id}`;
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
                window.closeWorkoutPlanModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closeWorkoutPlanModal();
            }
        });
    })();
</script>
