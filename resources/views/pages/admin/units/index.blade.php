@extends('layouts.app')

@section('title', 'Cadastro de Unidades')

@section('content')
<div class="p-4 sm:p-6">
    <div class="card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Cadastro de unidades</h1>
                <p class="text-text-secondary mt-1">Academias e demais unidades operacionais</p>
            </div>
            <button type="button" onclick="openUnitModal()" class="btn-primary whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i>
                Nova unidade
            </button>
        </div>

        <div class="sticky top-16 z-20 -mx-6 mt-6 mb-4 border-b border-custom bg-surface px-6 py-4 lg:static lg:mx-0 lg:mt-6 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1 max-w-md">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                    <input type="search"
                           id="unitSearch"
                           value="{{ request('search') }}"
                           placeholder="Buscar por unidade, código, rede, região ou cidade..."
                           class="input-field input-search"
                           autocomplete="off"
                           aria-label="Buscar unidades">
                    <i id="unitSearchSpinner"
                       class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hidden"></i>
                </div>

                <select id="unitNetworkFilter"
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

        <div id="unitsResults">
            @include('pages.admin.units._table')
        </div>
    </div>
</div>

@include('pages.admin.units._modal')

<script>
    (function () {
        const searchInput = document.getElementById('unitSearch');
        const networkFilter = document.getElementById('unitNetworkFilter');
        const results = document.getElementById('unitsResults');
        const spinner = document.getElementById('unitSearchSpinner');
        let debounceTimer = null;

        window.refreshUnitsTable = async function (url) {
            const target = new URL(url || '{{ route('admin.units.index') }}', window.location.origin);

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
                    throw new Error('Não foi possível carregar as unidades.');
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
                window.refreshUnitsTable();
            }, 300);
        });

        networkFilter?.addEventListener('change', function () {
            window.refreshUnitsTable();
        });

        results?.addEventListener('click', function (event) {
            const link = event.target.closest('nav[role="navigation"] a');

            if (link) {
                event.preventDefault();
                window.refreshUnitsTable(link.href);
                return;
            }

            const editButton = event.target.closest('.edit-unit');
            if (editButton) {
                window.openUnitModal(Number(editButton.dataset.unitId));
                return;
            }

            const toggleButton = event.target.closest('.toggle-unit');
            if (toggleButton) {
                window.toggleUnitStatus(
                    Number(toggleButton.dataset.unitId),
                    toggleButton.dataset.unitName,
                    toggleButton.dataset.unitActive === '1'
                );
                return;
            }

            const deleteButton = event.target.closest('.delete-unit');
            if (deleteButton) {
                window.deleteUnit(Number(deleteButton.dataset.unitId), deleteButton.dataset.unitName);
            }
        });

        window.afterUnitSaved = function () {
            window.refreshUnitsTable();
        };

        window.toggleUnitStatus = function (id, name, isActive) {
            const action = isActive ? 'inativar' : 'ativar';

            alertConfirm(
                isActive ? 'Inativar unidade' : 'Ativar unidade',
                `Deseja ${action} a unidade "${name}"?`,
                function () {
                    fetch(`{{ url('admin/units') }}/${id}/status`, {
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
                            window.refreshUnitsTable();
                        })
                        .catch(function (error) {
                            alertError('Erro', error.message);
                        });
                }
            );
        };

        window.deleteUnit = function (id, name) {
            alertConfirm(
                'Excluir unidade',
                `Deseja excluir a unidade "${name}"?`,
                function () {
                    fetch(`{{ url('admin/units') }}/${id}`, {
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
                                throw new Error(result.data.message || 'Não foi possível excluir a unidade.');
                            }

                            alertSuccess('Excluída!', result.data.message);
                            window.refreshUnitsTable();
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
