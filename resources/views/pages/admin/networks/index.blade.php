@extends('layouts.app')

@section('title', 'Cadastro de Redes')

@section('content')
<div class="p-4 sm:p-6">
    <div class="card">
        {{-- Card header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Cadastro de redes</h1>
                <p class="text-text-secondary mt-1">Gerencie as redes de academias da plataforma</p>
            </div>
            <button type="button"
                    onclick="openNetworkModal()"
                    class="btn-primary whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i>
                Nova rede
            </button>
        </div>

        {{-- Search bar (sticky on mobile) --}}
        <div class="sticky top-16 z-20 -mx-6 mt-6 mb-4 border-b border-custom bg-surface px-6 py-4 lg:static lg:mx-0 lg:mt-6 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0">
            <div class="relative max-w-md">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                <input type="search"
                       id="networkSearch"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Buscar por nome, CNPJ, responsável ou cidade..."
                       class="input-field input-search"
                       autocomplete="off"
                       aria-label="Buscar redes">
                <i id="networkSearchSpinner"
                   class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hidden"></i>
            </div>
        </div>

        {{-- Results --}}
        <div id="networksResults">
            @include('pages.admin.networks._table')
        </div>
    </div>
</div>

@include('pages.admin.networks._modal')

<script>
    (function () {
        const searchInput = document.getElementById('networkSearch');
        const results = document.getElementById('networksResults');
        const spinner = document.getElementById('networkSearchSpinner');
        let debounceTimer = null;

        window.refreshNetworksTable = async function (url) {
            const target = new URL(url || '{{ route('admin.networks.index') }}', window.location.origin);

            if (!url) {
                const search = searchInput.value.trim();
                if (search) {
                    target.searchParams.set('search', search);
                } else {
                    target.searchParams.delete('search');
                }
                target.searchParams.delete('page');
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
                    throw new Error('Não foi possível carregar as redes.');
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
                window.refreshNetworksTable();
            }, 300);
        });

        // Pagination without full page reload
        results?.addEventListener('click', function (event) {
            const link = event.target.closest('nav[role="navigation"] a');
            if (!link) {
                return;
            }

            event.preventDefault();
            window.refreshNetworksTable(link.href);
        });

        // Activate/inactivate network
        results?.addEventListener('click', function (event) {
            const button = event.target.closest('.toggle-network-status');
            if (!button) {
                return;
            }

            window.toggleNetworkStatus(
                Number(button.dataset.networkId),
                button.dataset.networkName,
                button.dataset.networkActive === '1'
            );
        });

        window.toggleNetworkStatus = function (id, name, isActive) {
            const action = isActive ? 'inativar' : 'ativar';

            alertConfirm(
                isActive ? 'Inativar rede' : 'Ativar rede',
                `Deseja ${action} a rede "${name}"?`,
                function () {
                    fetch(`{{ url('admin/networks') }}/${id}/status`, {
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
                            window.refreshNetworksTable();
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
