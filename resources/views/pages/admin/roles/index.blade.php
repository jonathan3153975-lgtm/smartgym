@extends('layouts.app')

@section('title', 'Cadastro de Perfis')

@section('content')
<div class="container mx-auto max-w-6xl p-4 sm:p-6">
    <div class="card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Cadastro de perfis</h1>
                <p class="text-text-secondary mt-1">Perfis de acesso e suas permissões</p>
            </div>
            <button type="button" onclick="openRoleModal()" class="btn-primary whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i>
                Novo perfil
            </button>
        </div>

        <div class="sticky top-16 z-20 -mx-6 mt-6 mb-4 border-b border-custom bg-surface px-6 py-4 lg:static lg:mx-0 lg:mt-6 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1 max-w-md">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                    <input type="search"
                           id="roleSearch"
                           value="{{ request('search') }}"
                           placeholder="Buscar por nome ou descrição..."
                           class="input-field input-search"
                           autocomplete="off"
                           aria-label="Buscar perfis">
                    <i id="roleSearchSpinner"
                       class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hidden"></i>
                </div>

                <select id="roleLevelFilter" class="input-field sm:max-w-xs" aria-label="Filtrar por nível">
                    <option value="">Todos os níveis</option>
                    <option value="platform" @selected(request('level') === 'platform')>Plataforma</option>
                    <option value="network" @selected(request('level') === 'network')>Rede</option>
                    <option value="region" @selected(request('level') === 'region')>Região</option>
                    <option value="unit" @selected(request('level') === 'unit')>Unidade</option>
                </select>
            </div>
        </div>

        <div id="rolesResults">
            @include('pages.admin.roles._table')
        </div>
    </div>
</div>

@include('pages.admin.roles._modal')

<script>
    (function () {
        const searchInput = document.getElementById('roleSearch');
        const levelFilter = document.getElementById('roleLevelFilter');
        const results = document.getElementById('rolesResults');
        const spinner = document.getElementById('roleSearchSpinner');
        let debounceTimer = null;

        window.refreshRolesTable = async function (url) {
            const target = new URL(url || '{{ route('admin.roles.index') }}', window.location.origin);

            if (!url) {
                const search = searchInput.value.trim();
                const level = levelFilter.value;

                target.searchParams.delete('page');

                if (search) {
                    target.searchParams.set('search', search);
                } else {
                    target.searchParams.delete('search');
                }

                if (level) {
                    target.searchParams.set('level', level);
                } else {
                    target.searchParams.delete('level');
                }
            }

            spinner?.classList.remove('hidden');

            try {
                const response = await fetch(target.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'text/html',
                    },
                });

                if (!response.ok) {
                    throw new Error('Não foi possível carregar os perfis.');
                }

                results.innerHTML = await response.text();

                if (!url) {
                    window.history.replaceState({}, '', target.toString());
                }
            } catch (error) {
                alertError('Erro', error.message);
            } finally {
                spinner?.classList.add('hidden');
            }
        };

        searchInput?.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                window.refreshRolesTable();
            }, 300);
        });

        levelFilter?.addEventListener('change', function () {
            window.refreshRolesTable();
        });

        results?.addEventListener('click', function (event) {
            const link = event.target.closest('nav[role="navigation"] a');

            if (link) {
                event.preventDefault();
                window.refreshRolesTable(link.href);
                return;
            }

            const viewButton = event.target.closest('.view-role');
            if (viewButton) {
                window.openRoleModal(
                    Number(viewButton.dataset.roleId),
                    viewButton.dataset.roleReadonly === '1'
                );
                return;
            }

            const editButton = event.target.closest('.edit-role');
            if (editButton) {
                window.openRoleModal(Number(editButton.dataset.roleId), false);
                return;
            }

            const deleteButton = event.target.closest('.delete-role');
            if (deleteButton) {
                window.deleteRole(Number(deleteButton.dataset.roleId), deleteButton.dataset.roleName);
            }
        });

        window.afterRoleSaved = function () {
            window.refreshRolesTable();
        };

        window.deleteRole = function (id, name) {
            alertConfirm(
                'Excluir perfil',
                `Deseja excluir o perfil "${name}"?`,
                function () {
                    fetch(`{{ url('admin/roles') }}/${id}`, {
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
                                throw new Error(result.data.message || 'Não foi possível excluir o perfil.');
                            }

                            alertSuccess('Excluído!', result.data.message);
                            window.refreshRolesTable();
                        })
                        .catch(function (error) {
                            alertError('Erro', error.message);
                        });
                }
            );
        };
    })();
</script>
@endsection
