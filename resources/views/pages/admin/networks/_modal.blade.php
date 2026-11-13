{{-- Network Modal (Create/Edit) --}}
<div id="networkModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="networkModalTitle">
    <div class="modal-content modal-content-lg">
        <div class="flex items-center justify-between mb-6">
            <h2 id="networkModalTitle" class="text-xl font-bold">Nova rede</h2>
            <button type="button"
                    onclick="closeNetworkModal()"
                    class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="networkForm" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" id="networkFormMethod" value="POST">

            {{-- Identification --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Identificação</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="network_name" class="label-field">Nome da rede <span class="text-red">*</span></label>
                        <input type="text"
                               id="network_name"
                               name="name"
                               required
                               maxlength="255"
                               class="input-field"
                               placeholder="Ex: SmartGym Brasil">
                    </div>

                    <div>
                        <label for="network_slug" class="label-field">Slug</label>
                        <input type="text"
                               id="network_slug"
                               name="slug"
                               maxlength="255"
                               class="input-field"
                               placeholder="smartgym-brasil">
                        <p class="text-xs text-text-secondary mt-1">Gerado automaticamente a partir do nome.</p>
                    </div>

                    <div>
                        <label for="network_document" class="label-field">CNPJ</label>
                        <input type="text"
                               id="network_document"
                               name="document"
                               maxlength="18"
                               class="input-field"
                               placeholder="00.000.000/0000-00">
                    </div>

                    <div>
                        <label for="network_plan_id" class="label-field">Plano SaaS</label>
                        <select id="network_plan_id" name="plan_id" class="input-field">
                            <option value="">Selecione um plano (opcional)</option>
                            @foreach (\App\Models\SaasPlan::where('status', 'active')->orderBy('name')->get() as $plan)
                                <option value="{{ $plan->id }}">
                                    {{ $plan->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            {{-- Address --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Endereço</h3>

                <div class="grid grid-cols-1 sm:grid-cols-6 gap-4" data-cep-scope>
                    <div class="sm:col-span-2">
                        <label for="network_zip_code" class="label-field">CEP</label>
                        <input type="text"
                               id="network_zip_code"
                               name="zip_code"
                               maxlength="9"
                               class="input-field"
                               placeholder="00000-000"
                               data-cep>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="network_street" class="label-field">Rua / Logradouro</label>
                        <input type="text"
                               id="network_street"
                               name="street"
                               maxlength="255"
                               class="input-field"
                               placeholder="Av. Principal"
                               data-cep-field="street">
                    </div>

                    <div class="sm:col-span-1">
                        <label for="network_number" class="label-field">Número</label>
                        <input type="text"
                               id="network_number"
                               name="number"
                               maxlength="20"
                               class="input-field"
                               placeholder="1000"
                               data-cep-field="number">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="network_complement" class="label-field">Complemento</label>
                        <input type="text"
                               id="network_complement"
                               name="complement"
                               maxlength="255"
                               class="input-field"
                               placeholder="Sala 1">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="network_neighborhood" class="label-field">Bairro</label>
                        <input type="text"
                               id="network_neighborhood"
                               name="neighborhood"
                               maxlength="255"
                               class="input-field"
                               placeholder="Centro"
                               data-cep-field="neighborhood">
                    </div>

                    <div class="sm:col-span-1">
                        <label for="network_city" class="label-field">Cidade</label>
                        <input type="text"
                               id="network_city"
                               name="city"
                               maxlength="255"
                               class="input-field"
                               placeholder="Curitiba"
                               data-cep-field="city">
                    </div>

                    <div class="sm:col-span-1">
                        <label for="network_state" class="label-field">UF</label>
                        <input type="text"
                               id="network_state"
                               name="state"
                               maxlength="2"
                               class="input-field uppercase"
                               placeholder="PR"
                               data-cep-field="state">
                    </div>
                </div>
            </section>

            {{-- Responsible --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Responsável</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="network_responsible_name" class="label-field">Nome do responsável</label>
                        <input type="text"
                               id="network_responsible_name"
                               name="responsible_name"
                               maxlength="255"
                               class="input-field"
                               placeholder="Nome completo">
                    </div>

                    <div>
                        <label for="network_responsible_cpf" class="label-field">CPF do responsável</label>
                        <input type="text"
                               id="network_responsible_cpf"
                               name="responsible_cpf"
                               maxlength="14"
                               class="input-field"
                               placeholder="000.000.000-00">
                    </div>

                    <div>
                        <label for="network_responsible_email" class="label-field">E-mail do responsável</label>
                        <input type="email"
                               id="network_responsible_email"
                               name="responsible_email"
                               maxlength="255"
                               class="input-field"
                               placeholder="responsavel@rede.com.br">
                    </div>

                    <div>
                        <label for="network_responsible_phone" class="label-field">Telefone do responsável</label>
                        <input type="text"
                               id="network_responsible_phone"
                               name="responsible_phone"
                               maxlength="20"
                               class="input-field"
                               placeholder="(00) 00000-0000"
                               data-phone>
                    </div>
                </div>
            </section>

            <p id="networkFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

            <div class="flex justify-end gap-3 pt-4 border-t border-custom">
                <button type="button"
                        onclick="closeNetworkModal()"
                        class="btn-outline">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary" id="networkSubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    <span id="networkSubmitBtnText">Salvar</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('networkModal');
        const form = document.getElementById('networkForm');
        const title = document.getElementById('networkModalTitle');
        const methodInput = document.getElementById('networkFormMethod');
        const submitText = document.getElementById('networkSubmitBtnText');
        const errorBox = document.getElementById('networkFormError');
        const fields = {
            name: document.getElementById('network_name'),
            slug: document.getElementById('network_slug'),
            document: document.getElementById('network_document'),
            plan_id: document.getElementById('network_plan_id'),
            zip_code: document.getElementById('network_zip_code'),
            street: document.getElementById('network_street'),
            number: document.getElementById('network_number'),
            complement: document.getElementById('network_complement'),
            neighborhood: document.getElementById('network_neighborhood'),
            city: document.getElementById('network_city'),
            state: document.getElementById('network_state'),
            responsible_name: document.getElementById('network_responsible_name'),
            responsible_cpf: document.getElementById('network_responsible_cpf'),
            responsible_email: document.getElementById('network_responsible_email'),
            responsible_phone: document.getElementById('network_responsible_phone'),
        };

        let currentNetworkId = null;
        let slugTouched = false;

        window.openNetworkModal = function (networkId = null) {
            form.reset();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            currentNetworkId = networkId;
            slugTouched = false;

            document.querySelectorAll('#network_plan_id option[data-current-plan]').forEach(function (option) {
                option.remove();
            });

            if (networkId) {
                title.textContent = 'Editar rede';
                submitText.textContent = 'Atualizar';
                methodInput.value = 'PUT';
                form.action = `{{ route('admin.networks.update', ':id') }}`.replace(':id', networkId);

                fetch(`{{ route('admin.networks.show', ':id') }}`.replace(':id', networkId), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Não foi possível carregar os dados da rede.');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        populateForm(data);
                    })
                    .catch(function (error) {
                        alertError('Erro', error.message);
                    });
            } else {
                title.textContent = 'Nova rede';
                submitText.textContent = 'Salvar';
                methodInput.value = 'POST';
                form.action = '{{ route('admin.networks.store') }}';
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                fields.name.focus();
            }, 100);
        };

        window.closeNetworkModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            currentNetworkId = null;
        };

        function populateForm(data) {
            Object.keys(fields).forEach(function (key) {
                const field = fields[key];

                if (!field) {
                    return;
                }

                if (key === 'plan_id') {
                    field.value = data.plan_id ?? '';

                    // Keep an inactive plan selected when editing an existing network.
                    if (data.plan_id && field.value !== String(data.plan_id)) {
                        const option = new Option(
                            `${data.plan_name ?? 'Plano atual'} (inativo)`,
                            data.plan_id
                        );
                        option.dataset.currentPlan = 'true';
                        field.appendChild(option);
                        field.value = String(data.plan_id);
                    }
                } else {
                    field.value = data[key] ?? '';
                }
            });

            slugTouched = Boolean(data.slug);
        }

        function showError(message) {
            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            errorBox.classList.add('hidden');

            const submitBtn = document.getElementById('networkSubmitBtn');
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

                        throw new Error(result.data.message || 'Não foi possível salvar a rede.');
                    }

                    alertSuccess(currentNetworkId ? 'Atualizado!' : 'Cadastrada!', result.data.message);
                    window.closeNetworkModal();

                    if (typeof window.refreshNetworksTable === 'function') {
                        window.refreshNetworksTable();
                    } else {
                        setTimeout(function () {
                            window.location.reload();
                        }, 600);
                    }
                })
                .catch(function (error) {
                    showError(error.message);
                })
                .finally(function () {
                    submitBtn.disabled = false;
                });
        });

        // Slug automatic generation
        fields.name.addEventListener('blur', function () {
            if (slugTouched || fields.slug.value) {
                return;
            }

            fields.slug.value = this.value
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
        });

        fields.slug.addEventListener('input', function () {
            slugTouched = true;
        });

        // Masks
        function applyMask(input, formatter) {
            input.addEventListener('input', function () {
                input.value = formatter(input.value.replace(/\D/g, ''));
            });
        }

        applyMask(fields.document, function (digits) {
            if (digits.length > 14) {
                return digits.slice(0, 14);
            }

            return digits
                .replace(/^(\d{2})(\d)/, '$1.$2')
                .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2')
                .replace(/(\d{4})(\d)/, '$1-$2');
        });

        applyMask(fields.responsible_cpf, function (digits) {
            return digits
                .slice(0, 11)
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        });

        applyMask(fields.responsible_phone, function (digits) {
            digits = digits.slice(0, 11);

            if (digits.length <= 10) {
                return digits
                    .replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
            }

            return digits
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{5})(\d)/, '$1-$2');
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                window.closeNetworkModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closeNetworkModal();
            }
        });
    })();
</script>
