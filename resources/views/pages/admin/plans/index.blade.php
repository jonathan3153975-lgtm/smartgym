@extends('layouts.app')

@section('title', 'Cadastro de Planos SaaS')

@section('content')
<div class="container mx-auto max-w-6xl p-4 sm:p-6">
    <div class="card">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Cadastro de planos</h1>
                <p class="text-text-secondary mt-1">Planos SaaS e seus parâmetros comerciais</p>
            </div>
            <button type="button"
                    onclick="openPlanModal()"
                    class="btn-primary whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i>
                Novo plano
            </button>
        </div>

        {{-- Search bar (sticky on mobile) --}}
        <div class="sticky top-16 z-20 -mx-6 mt-6 mb-4 border-b border-custom bg-surface px-6 py-4 lg:static lg:mx-0 lg:mt-6 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0">
            <div class="relative max-w-md">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                <input type="search"
                       id="planSearch"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="Buscar por nome ou descrição..."
                       class="input-field input-search"
                       autocomplete="off"
                       aria-label="Buscar planos">
                <i id="planSearchSpinner"
                   class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hidden"></i>
            </div>
        </div>

        <div id="plansResults">
            @include('pages.admin.plans._table')
        </div>
    </div>
</div>

@include('pages.admin.plans._modal')
@include('pages.admin.plans._parameter_modal')

<script>
    (function () {
        const searchInput = document.getElementById('planSearch');
        const results = document.getElementById('plansResults');
        const spinner = document.getElementById('planSearchSpinner');
        let debounceTimer = null;

        window.refreshPlansTable = async function (url) {
            const target = new URL(url || '{{ route('admin.plans.index') }}', window.location.origin);

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
                    throw new Error('Não foi possível carregar os planos.');
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
                window.refreshPlansTable();
            }, 300);
        });

        // Pagination without full page reload
        results?.addEventListener('click', function (event) {
            const link = event.target.closest('nav[role="navigation"] a');

            if (link) {
                event.preventDefault();
                window.refreshPlansTable(link.href);
                return;
            }

            const parameterButton = event.target.closest('.open-plan-parameters');
            if (parameterButton) {
                window.openPlanParametersModal(
                    Number(parameterButton.dataset.planId),
                    parameterButton.dataset.planName
                );
                return;
            }

            const editButton = event.target.closest('.edit-plan');
            if (editButton) {
                window.openPlanModal(Number(editButton.dataset.planId));
                return;
            }

            const deleteButton = event.target.closest('.delete-plan');
            if (deleteButton) {
                window.deletePlan(Number(deleteButton.dataset.planId), deleteButton.dataset.planName);
            }
        });

        window.deletePlan = function (id, name) {
            alertConfirm(
                'Excluir plano',
                `Deseja excluir o plano "${name}"? Os parâmetros vinculados também serão removidos.`,
                function () {
                    fetch(`{{ url('admin/plans') }}/${id}`, {
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
                                throw new Error(result.data.message || 'Não foi possível excluir o plano.');
                            }

                            alertSuccess('Excluído!', result.data.message);
                            window.refreshPlansTable();
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
