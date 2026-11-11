@php
    $permissionGroups = $permissions ?? \App\Support\TenantContext::current()->permissionOptionsQuery()->orderBy('module')->orderBy('name')->get()->groupBy('module');
@endphp

{{-- Role Modal (create/edit/view) --}}
<div id="roleModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="roleModalTitle">
    <div class="modal-content modal-content-lg">
        <div class="flex items-center justify-between mb-6">
            <h2 id="roleModalTitle" class="text-xl font-bold">Novo perfil</h2>
            <button type="button"
                    onclick="closeRoleModal()"
                    class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="roleForm" method="POST" action="{{ route('admin.roles.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" id="roleFormMethod" value="POST">

            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Dados do perfil</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="role_display_name" class="label-field">Nome de exibição <span class="text-red">*</span></label>
                        <input type="text" id="role_display_name" name="display_name" required maxlength="255" class="input-field" placeholder="Ex: Gerente Regional">
                    </div>
                    <div>
                        <label for="role_name" class="label-field">Identificador</label>
                        <input type="text" id="role_name" name="name" maxlength="255" class="input-field font-mono" placeholder="gerente_regional">
                        <p class="text-xs text-text-secondary mt-1">Apenas letras minúsculas, números e underscore.</p>
                    </div>
                    <div>
                        <label for="role_level" class="label-field">Nível <span class="text-red">*</span></label>
                        <select id="role_level" name="level" required class="input-field">
                            <option value="platform">Plataforma</option>
                            <option value="network">Rede</option>
                            <option value="region">Região</option>
                            <option value="unit">Unidade</option>
                        </select>
                    </div>
                    <div class="sm:col-span-1">
                        <label for="role_description" class="label-field">Descrição</label>
                        <input type="text" id="role_description" name="description" maxlength="1000" class="input-field" placeholder="Resumo das responsabilidades">
                    </div>
                </div>
            </section>

            <section class="space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Permissões</h3>
                    <span id="rolePermissionCount" class="text-xs text-text-secondary"></span>
                </div>

                <div class="max-h-72 overflow-y-auto rounded-xl border border-custom bg-bg-secondary p-4 space-y-5">
                    @foreach ($permissionGroups as $module => $modulePermissions)
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-cyan mb-2">
                                {{ ucfirst(str_replace('_', ' ', $module)) }}
                            </p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                @foreach ($modulePermissions as $permission)
                                    <label class="flex items-start gap-2 text-sm cursor-pointer">
                                        <input type="checkbox"
                                               name="permissions[]"
                                               value="{{ $permission->id }}"
                                               class="role-permission mt-0.5 rounded border-custom bg-surface text-cyan focus:ring-cyan">
                                        <span>
                                            {{ $permission->name }}
                                            <span class="block text-xs text-text-secondary font-mono">{{ $permission->key }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <p id="roleFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: rgba(255, 56, 100, 0.1); color: var(--color-red);"></p>

            <div class="flex justify-end gap-3 pt-4 border-t border-custom">
                <button type="button" onclick="closeRoleModal()" class="btn-outline" id="roleCancelBtn">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary" id="roleSubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    <span id="roleSubmitBtnText">Salvar perfil</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('roleModal');
        const form = document.getElementById('roleForm');
        const title = document.getElementById('roleModalTitle');
        const methodInput = document.getElementById('roleFormMethod');
        const submitText = document.getElementById('roleSubmitBtnText');
        const submitBtn = document.getElementById('roleSubmitBtn');
        const cancelBtn = document.getElementById('roleCancelBtn');
        const errorBox = document.getElementById('roleFormError');
        const displayNameInput = document.getElementById('role_display_name');
        const nameInput = document.getElementById('role_name');
        const levelSelect = document.getElementById('role_level');
        const descriptionInput = document.getElementById('role_description');
        const permissionCheckboxes = Array.from(document.querySelectorAll('.role-permission'));
        const permissionCount = document.getElementById('rolePermissionCount');

        if (!modal || !form) {
            return;
        }

        let currentRoleId = null;
        let nameTouched = false;

        function updatePermissionCount() {
            const total = permissionCheckboxes.filter((checkbox) => checkbox.checked).length;
            permissionCount.textContent = `${total} selecionada(s)`;
        }

        permissionCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', updatePermissionCount);
        });

        function setReadOnly(readOnly) {
            form.querySelectorAll('input, select, textarea').forEach(function (element) {
                element.disabled = readOnly;
            });

            submitBtn.classList.toggle('hidden', readOnly);
            cancelBtn.textContent = readOnly ? 'Fechar' : 'Cancelar';
        }

        window.openRoleModal = function (roleId = null, readOnly = false) {
            form.reset();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            currentRoleId = roleId;
            nameTouched = false;
            setReadOnly(false);

            if (roleId) {
                title.textContent = readOnly ? 'Visualizar perfil' : 'Editar perfil';
                submitText.textContent = 'Atualizar';
                methodInput.value = 'PUT';
                form.action = `{{ route('admin.roles.update', ':id') }}`.replace(':id', roleId);

                fetch(`{{ route('admin.roles.show', ':id') }}`.replace(':id', roleId), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Não foi possível carregar os dados do perfil.');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        displayNameInput.value = data.display_name ?? '';
                        nameInput.value = data.name ?? '';
                        levelSelect.value = data.level ?? 'network';
                        descriptionInput.value = data.description ?? '';

                        const rolePermissions = data.permissions || [];
                        permissionCheckboxes.forEach(function (checkbox) {
                            checkbox.checked = rolePermissions.includes(Number(checkbox.value));
                        });

                        updatePermissionCount();

                        if (readOnly) {
                            title.textContent = 'Visualizar perfil';
                            setReadOnly(true);
                        }
                    })
                    .catch(function (error) {
                        alertError('Erro', error.message);
                    });
            } else {
                title.textContent = 'Novo perfil';
                submitText.textContent = 'Salvar perfil';
                methodInput.value = 'POST';
                form.action = '{{ route('admin.roles.store') }}';
                updatePermissionCount();
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                if (!readOnly) {
                    displayNameInput.focus();
                }
            }, 100);
        };

        window.closeRoleModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            currentRoleId = null;
            setReadOnly(false);
        };

        displayNameInput.addEventListener('blur', function () {
            if (nameTouched || nameInput.value) {
                return;
            }

            nameInput.value = this.value
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/(^_|_$)/g, '');
        });

        nameInput.addEventListener('input', function () {
            nameTouched = true;
        });

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

                        throw new Error(result.data.message || 'Não foi possível salvar o perfil.');
                    }

                    alertSuccess(currentRoleId ? 'Atualizado!' : 'Cadastrado!', result.data.message);
                    window.closeRoleModal();

                    if (typeof window.afterRoleSaved === 'function') {
                        window.afterRoleSaved(result.data.data);
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
                window.closeRoleModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closeRoleModal();
            }
        });
    })();
</script>
