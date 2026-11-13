@extends('layouts.app')

@section('title', 'Cadastro de Regiões')

@section('content')
<div class="p-4 sm:p-6">
    <div class="card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Cadastro de regiões</h1>
                <p class="text-text-secondary mt-1">Agrupamentos gerenciais das unidades de cada rede</p>
            </div>
            <button type="button" onclick="openRegionModal()" class="btn-primary whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i>
                Nova região
            </button>
        </div>

        <div class="sticky top-16 z-20 -mx-6 mt-6 mb-4 border-b border-custom bg-surface px-6 py-4 lg:static lg:mx-0 lg:mt-6 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1 max-w-md">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                    <input type="search"
                           id="regionSearch"
                           value="{{ request('search') }}"
                           placeholder="Buscar por região, rede ou descrição..."
                           class="input-field input-search"
                           autocomplete="off"
                           aria-label="Buscar regiões">
                    <i id="regionSearchSpinner"
                       class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hidden"></i>
                </div>

                <select id="regionNetworkFilter"
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

        <div id="regionsResults">
            @include('pages.admin.regions._table')
        </div>
    </div>
</div>

@include('pages.admin.regions._modal')

<script>
    (function () {
        const searchInput = document.getElementById('regionSearch');
        const networkFilter = document.getElementById('regionNetworkFilter');
        const results = document.getElementById('regionsResults');
        const spinner = document.getElementById('regionSearchSpinner');
        let debounceTimer = null;

        window.refreshRegionsTable = async function (url) {
            const target = new URL(url || '{{ route('admin.regions.index') }}', window.location.origin);

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
                    throw new Error('Não foi possível carregar as regiões.');
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
                window.refreshRegionsTable();
            }, 300);
        });

        networkFilter?.addEventListener('change', function () {
            window.refreshRegionsTable();
        });

        results?.addEventListener('click', function (event) {
            const link = event.target.closest('nav[role="navigation"] a');

            if (link) {
                event.preventDefault();
                window.refreshRegionsTable(link.href);
                return;
            }

            const editButton = event.target.closest('.edit-region');
            if (editButton) {
                window.openRegionModal(Number(editButton.dataset.regionId));
                return;
            }

            const toggleButton = event.target.closest('.toggle-region');
            if (toggleButton) {
                window.toggleRegionStatus(
                    Number(toggleButton.dataset.regionId),
                    toggleButton.dataset.regionName,
                    toggleButton.dataset.regionActive === '1'
                );
                return;
            }

            const deleteButton = event.target.closest('.delete-region');
            if (deleteButton) {
                window.deleteRegion(Number(deleteButton.dataset.regionId), deleteButton.dataset.regionName);
            }
        });

        window.afterRegionSaved = function () {
            window.refreshRegionsTable();
        };

        window.toggleRegionStatus = function (id, name, isActive) {
            const action = isActive ? 'inativar' : 'ativar';

            alertConfirm(
                isActive ? 'Inativar região' : 'Ativar região',
                `Deseja ${action} a região "${name}"?`,
                function () {
                    fetch(`{{ url('admin/regions') }}/${id}/status`, {
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
                            window.refreshRegionsTable();
                        })
                        .catch(function (error) {
                            alertError('Erro', error.message);
                        });
                }
            );
        };

        window.deleteRegion = function (id, name) {
            alertConfirm(
                'Excluir região',
                `Deseja excluir a região "${name}"? As unidades vinculadas ficarão sem região.`,
                function () {
                    fetch(`{{ url('admin/regions') }}/${id}`, {
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
                                throw new Error(result.data.message || 'Não foi possível excluir a região.');
                            }

                            alertSuccess('Excluída!', result.data.message);
                            window.refreshRegionsTable();
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
