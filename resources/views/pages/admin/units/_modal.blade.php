@php
    $contextNetwork = $network ?? null;
    $unitNetworkOptions = $contextNetwork ? collect([$contextNetwork]) : \App\Support\TenantContext::current()->networksQuery()->get(['id', 'name']);
    $selectedUnitNetworkId = old('network_id', $contextNetwork?->id ?? ($unitNetworkOptions->count() === 1 ? $unitNetworkOptions->first()->id : null));
    $unitRegionOptions = $contextNetwork
        ? $contextNetwork->regions()->orderBy('name')->get()
        : \App\Support\TenantContext::current()->regionsQuery()->with('network')->get();
@endphp

{{-- Unit Modal (create/edit). Pass $network/$region to lock the context. --}}
<div id="unitModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="unitModalTitle">
    <div class="modal-content modal-content-lg">
        <div class="flex items-center justify-between mb-6">
            <h2 id="unitModalTitle" class="text-xl font-bold">Nova unidade</h2>
            <button type="button"
                    onclick="closeUnitModal()"
                    class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="unitForm" method="POST" action="{{ route('admin.units.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" id="unitFormMethod" value="POST">

            {{-- Identification --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Identificação</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="unit_network_id" class="label-field">Rede <span class="text-red">*</span></label>
                        <select id="unit_network_id" name="network_id" required class="input-field">
                            <option value="">Selecione uma rede</option>
                            @foreach ($unitNetworkOptions as $networkOption)
                                <option value="{{ $networkOption->id }}" @selected((string) $selectedUnitNetworkId === (string) $networkOption->id)>
                                    {{ $networkOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="unit_region_id" class="label-field">Região</label>
                        <select id="unit_region_id" name="region_id" class="input-field">
                            <option value="">Sem região</option>
                            @foreach ($unitRegionOptions as $regionOption)
                                <option value="{{ $regionOption->id }}"
                                        data-network-id="{{ $regionOption->network_id }}"
                                        @selected((string) old('region_id', ($region ?? null)?->id ?? '') === (string) $regionOption->id)>
                                    {{ $regionOption->name }}{{ $contextNetwork ? '' : ' - '.$regionOption->network?->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="unit_name" class="label-field">Nome da unidade <span class="text-red">*</span></label>
                        <input type="text"
                               id="unit_name"
                               name="name"
                               required
                               maxlength="255"
                               class="input-field"
                               placeholder="Ex: SmartGym Matriz">
                    </div>

                    <div>
                        <label for="unit_identifier" class="label-field">Código da unidade <span class="text-red">*</span></label>
                        <input type="text"
                               id="unit_identifier"
                               name="identifier"
                               required
                               maxlength="50"
                               class="input-field uppercase"
                               placeholder="UNID001">
                        <p class="text-xs text-text-secondary mt-1">Código único da unidade na plataforma.</p>
                    </div>

                    <div>
                        <label for="unit_status" class="label-field">Status <span class="text-red">*</span></label>
                        <select id="unit_status" name="status" required class="input-field">
                            <option value="active">Ativa</option>
                            <option value="inactive">Inativa</option>
                            <option value="maintenance">Em manutenção</option>
                        </select>
                    </div>
                </div>
            </section>

            {{-- Address --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Endereço</h3>

                <div class="grid grid-cols-1 sm:grid-cols-6 gap-4" data-cep-scope>
                    <div class="sm:col-span-2">
                        <label for="unit_zip_code" class="label-field">CEP</label>
                        <input type="text"
                               id="unit_zip_code"
                               name="zip_code"
                               maxlength="9"
                               class="input-field"
                               placeholder="00000-000"
                               data-cep>
                    </div>

                    <div class="sm:col-span-3">
                        <label for="unit_street" class="label-field">Rua / Logradouro</label>
                        <input type="text"
                               id="unit_street"
                               name="street"
                               maxlength="255"
                               class="input-field"
                               placeholder="Av. Principal"
                               data-cep-field="street">
                    </div>

                    <div class="sm:col-span-1">
                        <label for="unit_number" class="label-field">Número</label>
                        <input type="text"
                               id="unit_number"
                               name="number"
                               maxlength="20"
                               class="input-field"
                               placeholder="1000"
                               data-cep-field="number">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="unit_complement" class="label-field">Complemento</label>
                        <input type="text"
                               id="unit_complement"
                               name="complement"
                               maxlength="255"
                               class="input-field"
                               placeholder="Sala 1">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="unit_neighborhood" class="label-field">Bairro</label>
                        <input type="text"
                               id="unit_neighborhood"
                               name="neighborhood"
                               maxlength="255"
                               class="input-field"
                               placeholder="Centro"
                               data-cep-field="neighborhood">
                    </div>

                    <div class="sm:col-span-1">
                        <label for="unit_city" class="label-field">Cidade</label>
                        <input type="text"
                               id="unit_city"
                               name="city"
                               maxlength="255"
                               class="input-field"
                               placeholder="Curitiba"
                               data-cep-field="city">
                    </div>

                    <div class="sm:col-span-1">
                        <label for="unit_state" class="label-field">UF</label>
                        <input type="text"
                               id="unit_state"
                               name="state"
                               maxlength="2"
                               class="input-field uppercase"
                               placeholder="PR"
                               data-cep-field="state">
                    </div>
                </div>
            </section>

            {{-- Contact --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Contato</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="unit_phone" class="label-field">Telefone</label>
                        <input type="text"
                               id="unit_phone"
                               name="phone"
                               maxlength="20"
                               class="input-field"
                               placeholder="(00) 00000-0000">
                    </div>

                    <div>
                        <label for="unit_email" class="label-field">E-mail</label>
                        <input type="email"
                               id="unit_email"
                               name="email"
                               maxlength="255"
                               class="input-field"
                               placeholder="unidade@rede.com.br">
                    </div>
                </div>
            </section>

            <p id="unitFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: rgba(255, 56, 100, 0.1); color: var(--color-red);"></p>

            <div class="flex justify-end gap-3 pt-4 border-t border-custom">
                <button type="button" onclick="closeUnitModal()" class="btn-outline">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary" id="unitSubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    <span id="unitSubmitBtnText">Salvar unidade</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('unitModal');
        const form = document.getElementById('unitForm');
        const title = document.getElementById('unitModalTitle');
        const methodInput = document.getElementById('unitFormMethod');
        const submitText = document.getElementById('unitSubmitBtnText');
        const errorBox = document.getElementById('unitFormError');
        const submitBtn = document.getElementById('unitSubmitBtn');
        const networkSelect = document.getElementById('unit_network_id');
        const regionSelect = document.getElementById('unit_region_id');
        const regionOptions = Array.from(regionSelect.querySelectorAll('option[data-network-id]'));

        if (!modal || !form) {
            return;
        }

        const fields = {
            network_id: networkSelect,
            region_id: regionSelect,
            name: document.getElementById('unit_name'),
            identifier: document.getElementById('unit_identifier'),
            status: document.getElementById('unit_status'),
            zip_code: document.getElementById('unit_zip_code'),
            street: document.getElementById('unit_street'),
            number: document.getElementById('unit_number'),
            complement: document.getElementById('unit_complement'),
            neighborhood: document.getElementById('unit_neighborhood'),
            city: document.getElementById('unit_city'),
            state: document.getElementById('unit_state'),
            phone: document.getElementById('unit_phone'),
            email: document.getElementById('unit_email'),
        };

        let currentUnitId = null;

        function filterRegionsByNetwork() {
            const networkId = networkSelect.value;
            let hasSelectedRegion = false;

            regionOptions.forEach(function (option) {
                const matches = !networkId || option.dataset.networkId === networkId;
                option.hidden = !matches;
                option.disabled = !matches;

                if (matches && option.selected) {
                    hasSelectedRegion = true;
                }
            });

            if (!hasSelectedRegion) {
                regionSelect.value = '';
            }
        }

        networkSelect.addEventListener('change', filterRegionsByNetwork);

        window.openUnitModal = function (unitId = null) {
            form.reset();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            currentUnitId = unitId;
            filterRegionsByNetwork();

            if (unitId) {
                title.textContent = 'Editar unidade';
                submitText.textContent = 'Atualizar';
                methodInput.value = 'PUT';
                form.action = `{{ route('admin.units.update', ':id') }}`.replace(':id', unitId);

                fetch(`{{ route('admin.units.show', ':id') }}`.replace(':id', unitId), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Não foi possível carregar os dados da unidade.');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        Object.keys(fields).forEach(function (key) {
                            if (fields[key]) {
                                fields[key].value = data[key] ?? '';
                            }
                        });

                        if (!fields.status.value) {
                            fields.status.value = 'active';
                        }

                        filterRegionsByNetwork();
                        fields.region_id.value = data.region_id ?? '';
                    })
                    .catch(function (error) {
                        alertError('Erro', error.message);
                    });
            } else {
                title.textContent = 'Nova unidade';
                submitText.textContent = 'Salvar unidade';
                methodInput.value = 'POST';
                form.action = '{{ route('admin.units.store') }}';
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                document.getElementById('unit_name').focus();
            }, 100);
        };

        window.closeUnitModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            currentUnitId = null;
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

                        throw new Error(result.data.message || 'Não foi possível salvar a unidade.');
                    }

                    alertSuccess(currentUnitId ? 'Atualizada!' : 'Cadastrada!', result.data.message);
                    window.closeUnitModal();

                    if (typeof window.afterUnitSaved === 'function') {
                        window.afterUnitSaved(result.data.data);
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

        // Masks
        function applyMask(input, formatter) {
            input.addEventListener('input', function () {
                input.value = formatter(input.value.replace(/\D/g, ''));
            });
        }

        applyMask(document.getElementById('unit_phone'), function (digits) {
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
                window.closeUnitModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closeUnitModal();
            }
        });
    })();
</script>
