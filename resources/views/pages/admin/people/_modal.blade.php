@php
    $personTenant = \App\Support\TenantContext::current();
    $personUnitOptions = $personTenant->unitsQuery()->get(['id', 'name']);
    $personNetworkOptions = $personTenant->isPlatform()
        ? \App\Models\Network::orderBy('name')->get(['id', 'name'])
        : collect();
@endphp

{{-- Person Modal (create/edit) --}}
<div id="personModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="personModalTitle">
    <div class="modal-content modal-content-lg">
        <div class="mb-6 flex items-center justify-between">
            <h2 id="personModalTitle" class="text-xl font-bold">Nova pessoa</h2>
            <button type="button"
                    onclick="closePersonModal()"
                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="personForm" method="POST" action="{{ route('admin.people.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" id="personFormMethod" value="POST">

            {{-- Identification --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Identificação</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="person_type" class="label-field">Tipo de pessoa <span class="text-red">*</span></label>
                        <select id="person_type" name="person_type" required class="input-field">
                            <option value="individual">Física</option>
                            <option value="company">Jurídica</option>
                        </select>
                    </div>

                    <div>
                        <label for="person_category" class="label-field">Categoria <span class="text-red">*</span></label>
                        <select id="person_category" name="category" required class="input-field">
                            <option value="client">Cliente</option>
                            <option value="staff">Equipe</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="person_name" class="label-field">
                            <span id="personNameLabel">Nome completo</span> <span class="text-red">*</span>
                        </label>
                        <input type="text" id="person_name" name="name" required maxlength="255" class="input-field"
                               placeholder="Nome completo da pessoa">
                    </div>

                    <div data-person-type-field="company">
                        <label for="person_trade_name" class="label-field">Nome fantasia</label>
                        <input type="text" id="person_trade_name" name="trade_name" maxlength="255" class="input-field"
                               placeholder="Nome comercial">
                    </div>

                    <div data-person-type-field="company">
                        <label for="person_state_registration" class="label-field">Inscrição estadual</label>
                        <input type="text" id="person_state_registration" name="state_registration" maxlength="30" class="input-field"
                               placeholder="Isento ou número">
                    </div>

                    <div>
                        <label for="person_document" class="label-field">
                            <span id="personDocumentLabel">CPF</span> <span class="text-red">*</span>
                        </label>
                        <input type="text" id="person_document" name="document" required maxlength="14" class="input-field"
                               placeholder="000.000.000-00" inputmode="numeric" autocomplete="off">
                    </div>

                    <div data-person-type-field="individual">
                        <label for="person_birth_date" class="label-field">Data de nascimento</label>
                        <input type="date" id="person_birth_date" name="birth_date" class="input-field">
                    </div>

                    <div data-person-type-field="company">
                        <label for="person_legal_representative_name" class="label-field">Responsável legal</label>
                        <input type="text" id="person_legal_representative_name" name="legal_representative_name" maxlength="255" class="input-field"
                               placeholder="Nome do responsável">
                    </div>

                    <div data-person-type-field="company">
                        <label for="person_legal_representative_document" class="label-field">CPF do responsável legal</label>
                        <input type="text" id="person_legal_representative_document" name="legal_representative_document" maxlength="14" class="input-field"
                               placeholder="000.000.000-00" inputmode="numeric" autocomplete="off">
                    </div>
                </div>
            </section>

            {{-- Contact --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Contato</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="person_email" class="label-field">E-mail</label>
                        <input type="email" id="person_email" name="email" maxlength="255" class="input-field"
                               placeholder="pessoa@exemplo.com">
                    </div>

                    <div>
                        <label for="person_phone" class="label-field">Telefone</label>
                        <input type="text" id="person_phone" name="phone" maxlength="20" class="input-field"
                               placeholder="(00) 0000-0000">
                    </div>

                    <div>
                        <label for="person_whatsapp" class="label-field">WhatsApp</label>
                        <input type="text" id="person_whatsapp" name="whatsapp" maxlength="20" class="input-field"
                               placeholder="(00) 00000-0000">
                    </div>
                </div>
            </section>

            {{-- Address --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Endereço</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-6" data-cep-scope>
                    <div class="sm:col-span-2">
                        <label for="person_zip_code" class="label-field">CEP</label>
                        <input type="text" id="person_zip_code" name="zip_code" maxlength="9" class="input-field"
                               placeholder="00000-000" data-cep>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="person_street" class="label-field">Rua / Logradouro</label>
                        <input type="text" id="person_street" name="street" maxlength="255" class="input-field"
                               placeholder="Av. Principal" data-cep-field="street">
                    </div>

                    <div class="sm:col-span-1">
                        <label for="person_number" class="label-field">Número</label>
                        <input type="text" id="person_number" name="number" maxlength="20" class="input-field"
                               placeholder="123" data-cep-field="number">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="person_complement" class="label-field">Complemento</label>
                        <input type="text" id="person_complement" name="complement" maxlength="255" class="input-field"
                               placeholder="Sala 1">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="person_neighborhood" class="label-field">Bairro</label>
                        <input type="text" id="person_neighborhood" name="neighborhood" maxlength="255" class="input-field"
                               placeholder="Centro" data-cep-field="neighborhood">
                    </div>

                    <div class="sm:col-span-1">
                        <label for="person_city" class="label-field">Cidade</label>
                        <input type="text" id="person_city" name="city" maxlength="255" class="input-field"
                               placeholder="Curitiba" data-cep-field="city">
                    </div>

                    <div class="sm:col-span-1">
                        <label for="person_state" class="label-field">UF</label>
                        <input type="text" id="person_state" name="state" maxlength="2" class="input-field uppercase"
                               placeholder="PR" data-cep-field="state">
                    </div>
                </div>
            </section>

            {{-- Organization --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Organização</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @if ($personTenant->isPlatform())
                        <div class="sm:col-span-2">
                            <label for="person_network_id" class="label-field">Rede</label>
                            <select id="person_network_id" name="network_id" class="input-field">
                                <option value="">Sem rede (cadastro da plataforma)</option>
                                @foreach ($personNetworkOptions as $networkOption)
                                    <option value="{{ $networkOption->id }}">{{ $networkOption->name }}</option>
                                @endforeach
                            </select>
                            <p class="mt-1 text-xs text-text-secondary">
                                Define a rede proprietária do cadastro. Se uma unidade for selecionada, a rede é inferida.
                            </p>
                        </div>
                    @endif

                    <div>
                        <label for="person_unit_id" class="label-field">Unidade de referência</label>
                        <select id="person_unit_id" name="unit_id" class="input-field">
                            <option value="">Sem unidade</option>
                            @foreach ($personUnitOptions as $unitOption)
                                <option value="{{ $unitOption->id }}" @selected($personUnitOptions->count() === 1)>{{ $unitOption->name }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-text-secondary">
                            Obrigatória para clientes quando a rede exige unidade principal.
                        </p>
                    </div>

                    <div>
                        <label for="person_status" class="label-field">Status <span class="text-red">*</span></label>
                        <select id="person_status" name="status" required class="input-field">
                            <option value="active">Ativa</option>
                            <option value="inactive">Inativa</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="person_notes" class="label-field">Observações</label>
                        <textarea id="person_notes" name="notes" rows="3" maxlength="2000" class="input-field"
                                  placeholder="Informações administrativas sobre a pessoa"></textarea>
                    </div>
                </div>
            </section>

            <p id="personFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

            <div class="flex justify-end gap-3 border-t border-custom pt-4">
                <button type="button" onclick="closePersonModal()" class="btn-outline">Cancelar</button>
                <button type="submit" class="btn-primary" id="personSubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    <span id="personSubmitBtnText">Salvar pessoa</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('personModal');
        const form = document.getElementById('personForm');
        const title = document.getElementById('personModalTitle');
        const methodInput = document.getElementById('personFormMethod');
        const submitText = document.getElementById('personSubmitBtnText');
        const submitBtn = document.getElementById('personSubmitBtn');
        const errorBox = document.getElementById('personFormError');
        const typeSelect = document.getElementById('person_type');
        const nameLabel = document.getElementById('personNameLabel');
        const documentLabel = document.getElementById('personDocumentLabel');
        const documentInput = document.getElementById('person_document');
        const legalRepresentativeDocument = document.getElementById('person_legal_representative_document');

        if (!modal || !form) {
            return;
        }

        const fields = {
            network_id: document.getElementById('person_network_id'),
            person_type: typeSelect,
            category: document.getElementById('person_category'),
            name: document.getElementById('person_name'),
            trade_name: document.getElementById('person_trade_name'),
            state_registration: document.getElementById('person_state_registration'),
            document: documentInput,
            birth_date: document.getElementById('person_birth_date'),
            legal_representative_name: document.getElementById('person_legal_representative_name'),
            legal_representative_document: legalRepresentativeDocument,
            email: document.getElementById('person_email'),
            phone: document.getElementById('person_phone'),
            whatsapp: document.getElementById('person_whatsapp'),
            zip_code: document.getElementById('person_zip_code'),
            street: document.getElementById('person_street'),
            number: document.getElementById('person_number'),
            complement: document.getElementById('person_complement'),
            neighborhood: document.getElementById('person_neighborhood'),
            city: document.getElementById('person_city'),
            state: document.getElementById('person_state'),
            unit_id: document.getElementById('person_unit_id'),
            status: document.getElementById('person_status'),
            notes: document.getElementById('person_notes'),
        };

        let currentPersonId = null;

        function onlyDigits(value) {
            return String(value ?? '').replace(/\D/g, '');
        }

        function formatCpf(value) {
            return onlyDigits(value)
                .slice(0, 11)
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }

        function formatCnpj(value) {
            return onlyDigits(value)
                .slice(0, 14)
                .replace(/^(\d{2})(\d)/, '$1.$2')
                .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                .replace(/\.(\d{3})(\d)/, '.$1/$2')
                .replace(/(\d{4})(\d)/, '$1-$2');
        }

        function applyPersonType(type) {
            const isCompany = type === 'company';

            form.querySelectorAll('[data-person-type-field]').forEach(function (field) {
                const visible = (field.dataset.personTypeField === 'company') === isCompany;

                field.classList.toggle('hidden', !visible);
                field.querySelectorAll('input, select, textarea').forEach(function (input) {
                    input.disabled = !visible;
                });
            });

            nameLabel.textContent = isCompany ? 'Razão social' : 'Nome completo';
            fields.name.placeholder = isCompany ? 'Razão social da empresa' : 'Nome completo da pessoa';
            documentLabel.textContent = isCompany ? 'CNPJ' : 'CPF';
            documentInput.maxLength = isCompany ? 18 : 14;
            documentInput.placeholder = isCompany ? '00.000.000/0000-00' : '000.000.000-00';
        }

        typeSelect.addEventListener('change', function () {
            applyPersonType(typeSelect.value);
        });

        documentInput.addEventListener('input', function () {
            documentInput.value = typeSelect.value === 'company'
                ? formatCnpj(documentInput.value)
                : formatCpf(documentInput.value);
        });

        legalRepresentativeDocument?.addEventListener('input', function () {
            legalRepresentativeDocument.value = formatCpf(legalRepresentativeDocument.value);
        });

        window.openPersonModal = function (personId = null) {
            form.reset();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            currentPersonId = personId;

            applyPersonType(typeSelect.value || 'individual');

            if (personId) {
                title.textContent = 'Editar pessoa';
                submitText.textContent = 'Atualizar';
                methodInput.value = 'PUT';
                form.action = `{{ route('admin.people.update', ':id') }}`.replace(':id', personId);

                fetch(`{{ route('admin.people.edit-data', ':id') }}`.replace(':id', personId), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Não foi possível carregar os dados da pessoa.');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        Object.entries(fields).forEach(function ([key, input]) {
                            if (!input) {
                                return;
                            }

                            input.value = data[key] ?? '';
                        });

                        applyPersonType(data.person_type || 'individual');
                    })
                    .catch(function (error) {
                        alertError('Erro', error.message);
                    });
            } else {
                title.textContent = 'Nova pessoa';
                submitText.textContent = 'Salvar pessoa';
                methodInput.value = 'POST';
                form.action = '{{ route('admin.people.store') }}';
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                fields.name.focus();
            }, 100);
        };

        window.closePersonModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            currentPersonId = null;
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

                        throw new Error(result.data.message || 'Não foi possível salvar a pessoa.');
                    }

                    alertSuccess(currentPersonId ? 'Atualizada!' : 'Cadastrada!', result.data.message);
                    window.closePersonModal();

                    if (typeof window.afterPersonSaved === 'function') {
                        window.afterPersonSaved(result.data.data);
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
                window.closePersonModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closePersonModal();
            }
        });
    })();
</script>
