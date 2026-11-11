{{-- SaaS Plan Parameters Modal (list + create/edit/delete) --}}
<div id="planParametersModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="planParametersModalTitle">
    <div class="modal-content modal-content-lg">
        <div class="flex items-center justify-between mb-2">
            <h2 id="planParametersModalTitle" class="text-xl font-bold">Parâmetros do plano</h2>
            <button type="button"
                    onclick="closePlanParametersModal()"
                    class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <p id="planParametersSubtitle" class="text-sm text-text-secondary mb-5"></p>

        <form id="parameterForm" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="parameterFormMethod" value="POST">
            <input type="hidden" name="parameter_id" id="parameter_id">

            <div class="grid grid-cols-1 sm:grid-cols-[1fr_180px] gap-4">
                <div>
                    <label for="parameter_description" class="label-field">Descrição <span class="text-red">*</span></label>
                    <input type="text"
                           id="parameter_description"
                           name="description"
                           required
                           maxlength="255"
                           class="input-field"
                           placeholder="Ex: Taxa de adesão">
                </div>

                <div>
                    <label for="parameter_value" class="label-field">Valor <span class="text-red">*</span></label>
                    <input type="text"
                           id="parameter_value"
                           name="value"
                           required
                           inputmode="decimal"
                           class="input-field text-right"
                           placeholder="0,00">
                </div>
            </div>

            <p id="parameterFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

            <div class="flex justify-end gap-3">
                <button type="button"
                        id="parameterCancelBtn"
                        onclick="cancelParameterEdit()"
                        class="btn-outline hidden">
                    Cancelar edição
                </button>
                <button type="submit" class="btn-primary" id="parameterSubmitBtn">
                    <i class="fas fa-plus mr-2"></i>
                    <span id="parameterSubmitBtnText">Adicionar</span>
                </button>
            </div>
        </form>

        <div class="mt-6 border-t border-custom pt-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">
                    Parâmetros cadastrados
                </h3>
                <span id="planParametersTotal" class="text-sm text-text-secondary"></span>
            </div>

            <div id="planParametersList" class="space-y-2">
                <p class="text-sm text-text-secondary">Carregando parâmetros...</p>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('planParametersModal');
        const form = document.getElementById('parameterForm');
        const title = document.getElementById('planParametersModalTitle');
        const subtitle = document.getElementById('planParametersSubtitle');
        const list = document.getElementById('planParametersList');
        const total = document.getElementById('planParametersTotal');
        const errorBox = document.getElementById('parameterFormError');
        const descriptionInput = document.getElementById('parameter_description');
        const valueInput = document.getElementById('parameter_value');
        const parameterIdInput = document.getElementById('parameter_id');
        const methodInput = document.getElementById('parameterFormMethod');
        const submitText = document.getElementById('parameterSubmitBtnText');
        const submitIcon = document.querySelector('#parameterSubmitBtn i');
        const cancelBtn = document.getElementById('parameterCancelBtn');
        const submitBtn = document.getElementById('parameterSubmitBtn');

        if (!modal || !form) {
            return;
        }

        let currentPlanId = null;
        let currentPlanName = '';
        let editingParameterId = null;

        function formatCurrency(value) {
            return Number(value || 0).toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL',
            });
        }

        function maskCurrency(value) {
            const digits = value.replace(/\D/g, '').slice(0, 12);

            if (!digits) {
                return '';
            }

            const normalized = (parseInt(digits, 10) / 100).toFixed(2);

            return normalized
                .replace('.', ',')
                .replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';
            return div.innerHTML;
        }

        valueInput.addEventListener('input', function () {
            this.value = maskCurrency(this.value);
        });

        window.openPlanParametersModal = function (planId, planName) {
            currentPlanId = planId;
            currentPlanName = planName || '';
            title.textContent = 'Parâmetros do plano';
            subtitle.textContent = currentPlanName;

            resetParameterForm();
            errorBox.classList.add('hidden');

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            loadPlanParameters();
        };

        window.closePlanParametersModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            currentPlanId = null;
            currentPlanName = '';
            resetParameterForm();
        };

        function parameterEndpoint(parameterId = null) {
            const base = `{{ url('admin/plans') }}/${currentPlanId}/parameters`;

            return parameterId ? `${base}/${parameterId}` : base;
        }

        function loadPlanParameters() {
            list.innerHTML = '<p class="text-sm text-text-secondary">Carregando parâmetros...</p>';
            total.textContent = '';

            fetch(`{{ route('admin.plans.show', ':id') }}`.replace(':id', currentPlanId), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Não foi possível carregar os parâmetros do plano.');
                    }
                    return response.json();
                })
                .then(function (data) {
                    renderParameters(data.parameters || []);
                })
                .catch(function (error) {
                    list.innerHTML = '';
                    const message = document.createElement('p');
                    message.className = 'text-sm text-red';
                    message.textContent = error.message;
                    list.appendChild(message);
                });
        }

        function renderParameters(parameters) {
            list.innerHTML = '';

            if (!parameters.length) {
                list.innerHTML = '<p class="text-sm text-text-secondary">Nenhum parâmetro cadastrado para este plano.</p>';
                total.textContent = '';
                return;
            }

            const sum = parameters.reduce(function (carry, parameter) {
                return carry + Number(parameter.value || 0);
            }, 0);

            total.textContent = `Total: ${formatCurrency(sum)}`;

            parameters.forEach(function (parameter) {
                const row = document.createElement('div');
                row.className = 'flex items-center justify-between gap-3 rounded-lg border border-custom bg-bg-secondary px-4 py-3';
                row.innerHTML = `
                    <div class="min-w-0">
                        <p class="font-medium truncate">${escapeHtml(parameter.description)}</p>
                        <p class="text-sm text-text-secondary">${formatCurrency(parameter.value)}</p>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button type="button"
                                class="edit-parameter p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                data-parameter-id="${parameter.id}"
                                data-parameter-description="${escapeHtml(parameter.description)}"
                                data-parameter-value="${parameter.value}"
                                title="Editar parâmetro">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button type="button"
                                class="delete-parameter p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                                data-parameter-id="${parameter.id}"
                                data-parameter-description="${escapeHtml(parameter.description)}"
                                title="Excluir parâmetro">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
                list.appendChild(row);
            });
        }

        list.addEventListener('click', function (event) {
            const editButton = event.target.closest('.edit-parameter');

            if (editButton) {
                startParameterEdit(
                    Number(editButton.dataset.parameterId),
                    editButton.dataset.parameterDescription,
                    editButton.dataset.parameterValue
                );
                return;
            }

            const deleteButton = event.target.closest('.delete-parameter');

            if (deleteButton) {
                deleteParameter(
                    Number(deleteButton.dataset.parameterId),
                    deleteButton.dataset.parameterDescription
                );
            }
        });

        function startParameterEdit(parameterId, description, value) {
            editingParameterId = parameterId;
            parameterIdInput.value = parameterId;
            methodInput.value = 'PUT';
            descriptionInput.value = description;
            valueInput.value = maskCurrency(String(value ?? '').replace('.', ','));
            submitText.textContent = 'Atualizar';
            submitIcon.className = 'fas fa-save mr-2';
            cancelBtn.classList.remove('hidden');
            errorBox.classList.add('hidden');
            descriptionInput.focus();
        }

        window.cancelParameterEdit = function () {
            resetParameterForm();
        };

        function resetParameterForm() {
            form.reset();
            editingParameterId = null;
            parameterIdInput.value = '';
            methodInput.value = 'POST';
            submitText.textContent = 'Adicionar';
            submitIcon.className = 'fas fa-plus mr-2';
            cancelBtn.classList.add('hidden');
            errorBox.classList.add('hidden');
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            errorBox.classList.add('hidden');
            submitBtn.disabled = true;

            fetch(parameterEndpoint(editingParameterId), {
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

                        throw new Error(result.data.message || 'Não foi possível salvar o parâmetro.');
                    }

                    alertSuccess(editingParameterId ? 'Atualizado!' : 'Adicionado!', result.data.message);
                    resetParameterForm();
                    loadPlanParameters();

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

        function deleteParameter(parameterId, description) {
            alertConfirm(
                'Excluir parâmetro',
                `Deseja excluir o parâmetro "${description}"?`,
                function () {
                    fetch(parameterEndpoint(parameterId), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': window.SmartGym.csrfToken,
                            'Accept': 'application/json',
                        },
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                return { ok: response.ok, data: data };
                            });
                        })
                        .then(function (result) {
                            if (!result.ok) {
                                throw new Error(result.data.message || 'Não foi possível excluir o parâmetro.');
                            }

                            alertSuccess('Excluído!', result.data.message);
                            loadPlanParameters();

                            if (typeof window.refreshPlansTable === 'function') {
                                window.refreshPlansTable();
                            }
                        })
                        .catch(function (error) {
                            alertError('Erro', error.message);
                        });
                }
            );
        }

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                window.closePlanParametersModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closePlanParametersModal();
            }
        });
    })();
</script>
