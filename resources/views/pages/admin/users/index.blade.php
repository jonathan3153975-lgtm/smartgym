@extends('layouts.app')

@section('title', 'Cadastro de Usuários')

@section('content')
<div class="container mx-auto max-w-6xl p-4 sm:p-6">
    <div class="card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Cadastro de usuários</h1>
                <p class="text-text-secondary mt-1">Usuários da plataforma e seus vínculos organizacionais</p>
            </div>
            <button type="button" onclick="openUserModal()" class="btn-primary whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i>
                Novo usuário
            </button>
        </div>

        <div class="sticky top-16 z-20 -mx-6 mt-6 mb-4 border-b border-custom bg-surface px-6 py-4 lg:static lg:mx-0 lg:mt-6 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1 max-w-md">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                    <input type="search"
                           id="userSearch"
                           value="{{ request('search') }}"
                           placeholder="Buscar por nome ou e-mail..."
                           class="input-field input-search"
                           autocomplete="off"
                           aria-label="Buscar usuários">
                    <i id="userSearchSpinner"
                       class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hidden"></i>
                </div>

                <select id="userNetworkFilter"
                        class="input-field sm:max-w-xs"
                        aria-label="Filtrar por rede">
                    <option value="">Todas as redes</option>
                    @foreach ($networks as $network)
                        <option value="{{ $network->id }}" @selected((string) request('network_id') === (string) $network->id)>
                            {{ $network->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="usersResults">
            @include('pages.admin.users._table')
        </div>
    </div>
</div>

@include('pages.admin.users._modal')

<script>
    (function () {
        const searchInput = document.getElementById('userSearch');
        const networkFilter = document.getElementById('userNetworkFilter');
        const results = document.getElementById('usersResults');
        const spinner = document.getElementById('userSearchSpinner');
        let debounceTimer = null;

        window.refreshUsersTable = async function (url) {
            const target = new URL(url || '{{ route('admin.users.index') }}', window.location.origin);

            if (!url) {
                const search = searchInput.value.trim();
                const networkId = networkFilter.value;

                target.searchParams.delete('page');

                if (search) {
                    target.searchParams.set('search', search);
                } else {
                    target.searchParams.delete('search');
                }

                if (networkId) {
                    target.searchParams.set('network_id', networkId);
                } else {
                    target.searchParams.delete('network_id');
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
                    throw new Error('Não foi possível carregar os usuários.');
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
                window.refreshUsersTable();
            }, 300);
        });

        networkFilter?.addEventListener('change', function () {
            window.refreshUsersTable();
        });

        results?.addEventListener('click', function (event) {
            const link = event.target.closest('nav[role="navigation"] a');

            if (link) {
                event.preventDefault();
                window.refreshUsersTable(link.href);
                return;
            }

            const editButton = event.target.closest('.edit-user');
            if (editButton) {
                window.openUserModal(Number(editButton.dataset.userId));
                return;
            }

            const toggleButton = event.target.closest('.toggle-user');
            if (toggleButton) {
                window.toggleUserStatus(
                    Number(toggleButton.dataset.userId),
                    toggleButton.dataset.userName,
                    toggleButton.dataset.userActive === '1'
                );
                return;
            }

            const deleteButton = event.target.closest('.delete-user');
            if (deleteButton) {
                window.deleteUser(Number(deleteButton.dataset.userId), deleteButton.dataset.userName);
            }
        });

        window.afterUserSaved = function () {
            window.refreshUsersTable();
        };

        window.toggleUserStatus = function (id, name, isActive) {
            const action = isActive ? 'inativar' : 'ativar';

            alertConfirm(
                isActive ? 'Inativar usuário' : 'Ativar usuário',
                `Deseja ${action} o vínculo principal do usuário "${name}"?`,
                function () {
                    fetch(`{{ url('admin/users') }}/${id}/status`, {
                        method: 'PATCH',
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
                                throw new Error(result.data.message || 'Não foi possível alterar o status.');
                            }

                            alertSuccess('Atualizado!', result.data.message);
                            window.refreshUsersTable();
                        })
                        .catch(function (error) {
                            alertError('Erro', error.message);
                        });
                }
            );
        };

        window.deleteUser = function (id, name) {
            alertConfirm(
                'Excluir usuário',
                `Deseja excluir o usuário "${name}"? Todos os vínculos serão removidos.`,
                function () {
                    fetch(`{{ url('admin/users') }}/${id}`, {
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
                                throw new Error(result.data.message || 'Não foi possível excluir o usuário.');
                            }

                            alertSuccess('Excluído!', result.data.message);
                            window.refreshUsersTable();
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
