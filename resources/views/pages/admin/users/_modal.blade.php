@php
    $tenant = \App\Support\TenantContext::current();
    $userNetworkOptions = $tenant->networksQuery()->get(['id', 'name']);
    $userRoleOptions = $tenant->roleOptionsQuery()->orderBy('level')->orderBy('display_name')->get(['id', 'display_name', 'level']);
    $userRegionOptions = $tenant->regionsQuery()->get(['id', 'name', 'network_id']);
    $userUnitOptions = $tenant->unitsQuery()->get(['id', 'name', 'network_id', 'region_id']);
    $tenantNetworkId = $tenant->networkId();
@endphp

{{-- User Modal (create/edit) --}}
<div id="userModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="userModalTitle">
    <div class="modal-content modal-content-lg">
        <div class="flex items-center justify-between mb-6">
            <h2 id="userModalTitle" class="text-xl font-bold">Novo usuário</h2>
            <button type="button"
                    onclick="closeUserModal()"
                    class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="userForm" method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" id="userFormMethod" value="POST">

            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Dados do usuário</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="user_name" class="label-field">Nome <span class="text-red">*</span></label>
                        <input type="text" id="user_name" name="name" required maxlength="255" class="input-field" placeholder="Nome completo">
                    </div>
                    <div>
                        <label for="user_email" class="label-field">E-mail <span class="text-red">*</span></label>
                        <input type="email" id="user_email" name="email" required maxlength="255" class="input-field" placeholder="usuario@rede.com.br">
                    </div>
                    <div>
                        <label for="user_password" class="label-field">
                            Senha <span id="userPasswordRequired" class="text-red">*</span>
                        </label>
                        <input type="password" id="user_password" name="password" class="input-field" autocomplete="new-password" placeholder="Mínimo de 8 caracteres">
                        <p id="userPasswordHint" class="hidden text-xs text-text-secondary mt-1">Deixe em branco para manter a senha atual.</p>
                    </div>
                    <div>
                        <label for="user_password_confirmation" class="label-field">Confirmar senha</label>
                        <input type="password" id="user_password_confirmation" name="password_confirmation" class="input-field" autocomplete="new-password" placeholder="Repita a senha">
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Vínculo organizacional</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="user_role_id" class="label-field">Perfil <span class="text-red">*</span></label>
                        <select id="user_role_id" name="role_id" required class="input-field">
                            <option value="">Selecione um perfil</option>
                            @foreach ($userRoleOptions as $roleOption)
                                <option value="{{ $roleOption->id }}">
                                    [{{ ucfirst($roleOption->level) }}] {{ $roleOption->display_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="user_vinculo_status" class="label-field">Status do vínculo <span class="text-red">*</span></label>
                        <select id="user_vinculo_status" name="vinculo_status" required class="input-field">
                            <option value="active">Ativo</option>
                            <option value="inactive">Inativo</option>
                        </select>
                    </div>

                    <div>
                        <label for="user_network_id" class="label-field">Rede</label>
                        <select id="user_network_id" name="network_id" class="input-field">
                            <option value="">
                                {{ $tenantNetworkId ? 'Selecione a rede' : 'Sem rede (perfil de plataforma)' }}
                            </option>
                            @foreach ($userNetworkOptions as $networkOption)
                                <option value="{{ $networkOption->id }}" @selected($tenantNetworkId === $networkOption->id)>
                                    {{ $networkOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="user_region_id" class="label-field">Região</label>
                        <select id="user_region_id" name="region_id" class="input-field">
                            <option value="">Sem região</option>
                            @foreach ($userRegionOptions as $regionOption)
                                <option value="{{ $regionOption->id }}" data-network-id="{{ $regionOption->network_id }}">
                                    {{ $regionOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="user_unit_id" class="label-field">Unidade</label>
                        <select id="user_unit_id" name="unit_id" class="input-field">
                            <option value="">Sem unidade</option>
                            @foreach ($userUnitOptions as $unitOption)
                                <option value="{{ $unitOption->id }}"
                                        data-network-id="{{ $unitOption->network_id }}"
                                        data-region-id="{{ $unitOption->region_id }}">
                                    {{ $unitOption->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>

            <p id="userFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: rgba(255, 56, 100, 0.1); color: var(--color-red);"></p>

            <div class="flex justify-end gap-3 pt-4 border-t border-custom">
                <button type="button" onclick="closeUserModal()" class="btn-outline">Cancelar</button>
                <button type="submit" class="btn-primary" id="userSubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    <span id="userSubmitBtnText">Salvar usuário</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('userModal');
        const form = document.getElementById('userForm');
        const title = document.getElementById('userModalTitle');
        const methodInput = document.getElementById('userFormMethod');
        const submitText = document.getElementById('userSubmitBtnText');
        const submitBtn = document.getElementById('userSubmitBtn');
        const errorBox = document.getElementById('userFormError');
        const networkSelect = document.getElementById('user_network_id');
        const regionSelect = document.getElementById('user_region_id');
        const unitSelect = document.getElementById('user_unit_id');
        const passwordInput = document.getElementById('user_password');
        const passwordRequired = document.getElementById('userPasswordRequired');
        const passwordHint = document.getElementById('userPasswordHint');
        const regionOptions = Array.from(regionSelect.querySelectorAll('option[data-network-id]'));
        const unitOptions = Array.from(unitSelect.querySelectorAll('option[data-network-id]'));

        if (!modal || !form) {
            return;
        }

        const fields = {
            name: document.getElementById('user_name'),
            email: document.getElementById('user_email'),
            role_id: document.getElementById('user_role_id'),
            vinculo_status: document.getElementById('user_vinculo_status'),
            network_id: networkSelect,
            region_id: regionSelect,
            unit_id: unitSelect,
        };

        let currentUserId = null;

        function filterRegions() {
            const networkId = networkSelect.value;

            regionOptions.forEach(function (option) {
                const matches = Boolean(networkId) && option.dataset.networkId === networkId;
                option.hidden = !matches;
                option.disabled = !matches;

                if (!matches && option.selected) {
                    regionSelect.value = '';
                }
            });
        }

        function filterUnits() {
            const networkId = networkSelect.value;
            const regionId = regionSelect.value;

            unitOptions.forEach(function (option) {
                let matches = Boolean(networkId) && option.dataset.networkId === networkId;

                if (matches && regionId) {
                    matches = option.dataset.regionId === regionId;
                }

                option.hidden = !matches;
                option.disabled = !matches;

                if (!matches && option.selected) {
                    unitSelect.value = '';
                }
            });
        }

        networkSelect.addEventListener('change', function () {
            regionSelect.value = '';
            unitSelect.value = '';
            filterRegions();
            filterUnits();
        });

        regionSelect.addEventListener('change', function () {
            unitSelect.value = '';
            filterUnits();
        });

        window.openUserModal = function (userId = null) {
            form.reset();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            currentUserId = userId;

            if (userId) {
                title.textContent = 'Editar usuário';
                submitText.textContent = 'Atualizar';
                methodInput.value = 'PUT';
                passwordRequired.classList.add('hidden');
                passwordHint.classList.remove('hidden');
                form.action = `{{ route('admin.users.update', ':id') }}`.replace(':id', userId);

                fetch(`{{ route('admin.users.edit-data', ':id') }}`.replace(':id', userId), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Não foi possível carregar os dados do usuário.');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        fields.name.value = data.name ?? '';
                        fields.email.value = data.email ?? '';
                        fields.role_id.value = data.role_id ?? '';
                        fields.vinculo_status.value = data.vinculo_status ?? 'active';
                        fields.network_id.value = data.network_id ?? '';
                        filterRegions();
                        filterUnits();
                        fields.region_id.value = data.region_id ?? '';
                        filterUnits();
                        fields.unit_id.value = data.unit_id ?? '';
                    })
                    .catch(function (error) {
                        alertError('Erro', error.message);
                    });
            } else {
                title.textContent = 'Novo usuário';
                submitText.textContent = 'Salvar usuário';
                methodInput.value = 'POST';
                passwordRequired.classList.remove('hidden');
                passwordHint.classList.add('hidden');
                form.action = '{{ route('admin.users.store') }}';
                filterRegions();
                filterUnits();
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                fields.name.focus();
            }, 100);
        };

        window.closeUserModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            currentUserId = null;
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

                        throw new Error(result.data.message || 'Não foi possível salvar o usuário.');
                    }

                    alertSuccess(currentUserId ? 'Atualizado!' : 'Cadastrado!', result.data.message);
                    window.closeUserModal();

                    if (typeof window.afterUserSaved === 'function') {
                        window.afterUserSaved(result.data.data);
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
                window.closeUserModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closeUserModal();
            }
        });
    })();
</script>
