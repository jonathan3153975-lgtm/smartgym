{{-- SaaS Plan Modal (Create/Edit) --}}
<div id="planModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="planModalTitle">
    <div class="modal-content">
        <div class="flex items-center justify-between mb-6">
            <h2 id="planModalTitle" class="text-xl font-bold">Novo plano</h2>
            <button type="button"
                    onclick="closePlanModal()"
                    class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="planForm" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="planFormMethod" value="POST">

            <div>
                <label for="plan_name" class="label-field">Nome do plano <span class="text-red">*</span></label>
                <input type="text"
                       id="plan_name"
                       name="name"
                       required
                       maxlength="255"
                       class="input-field"
                       placeholder="Ex: Enterprise">
            </div>

            <div>
                <label for="plan_description" class="label-field">Descrição</label>
                <textarea id="plan_description"
                          name="description"
                          rows="4"
                          maxlength="2000"
                          class="input-field"
                          placeholder="Descreva o plano (opcional)"></textarea>
            </div>

            <div>
                <label for="plan_status" class="label-field">Status <span class="text-red">*</span></label>
                <select id="plan_status" name="status" required class="input-field">
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                </select>
            </div>

            <p id="planFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

            <div class="flex justify-end gap-3 pt-4 border-t border-custom">
                <button type="button" onclick="closePlanModal()" class="btn-outline">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary" id="planSubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    <span id="planSubmitBtnText">Salvar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('planModal');
        const form = document.getElementById('planForm');
        const title = document.getElementById('planModalTitle');
        const methodInput = document.getElementById('planFormMethod');
        const submitText = document.getElementById('planSubmitBtnText');
        const errorBox = document.getElementById('planFormError');
        const nameInput = document.getElementById('plan_name');
        const descriptionInput = document.getElementById('plan_description');
        const statusInput = document.getElementById('plan_status');

        if (!modal || !form) {
            return;
        }

        let currentPlanId = null;

        window.openPlanModal = function (planId = null) {
            form.reset();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            currentPlanId = planId;

            if (planId) {
                title.textContent = 'Editar plano';
                submitText.textContent = 'Atualizar';
                methodInput.value = 'PUT';
                form.action = `{{ route('admin.plans.update', ':id') }}`.replace(':id', planId);

                fetch(`{{ route('admin.plans.show', ':id') }}`.replace(':id', planId), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Não foi possível carregar os dados do plano.');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        nameInput.value = data.name ?? '';
                        descriptionInput.value = data.description ?? '';
                        statusInput.value = data.status ?? 'active';
                    })
                    .catch(function (error) {
                        alertError('Erro', error.message);
                    });
            } else {
                title.textContent = 'Novo plano';
                submitText.textContent = 'Salvar';
                methodInput.value = 'POST';
                form.action = '{{ route('admin.plans.store') }}';
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                nameInput.focus();
            }, 100);
        };

        window.closePlanModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            currentPlanId = null;
        };

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            errorBox.classList.add('hidden');

            const submitBtn = document.getElementById('planSubmitBtn');
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

                    alertSuccess(currentPlanId ? 'Atualizado!' : 'Cadastrado!', result.data.message);
                    window.closePlanModal();

                    if (typeof window.refreshPlansTable === 'function') {
                        window.refreshPlansTable();
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
                window.closePlanModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closePlanModal();
            }
        });
    })();
</script>
