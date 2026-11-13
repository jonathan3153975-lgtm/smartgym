@extends('layouts.app')

@section('title', 'Planos')

@section('content')
<div class="container mx-auto max-w-6xl p-4 sm:p-6">
    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Planos</h1>
                <p class="mt-1 text-text-secondary">Planos comerciais, períodos de acesso, unidades e modalidades incluídas</p>
            </div>

            @can('membership_plans.create')
                <button type="button" onclick="openMembershipPlanModal()" class="btn-primary whitespace-nowrap">
                    <i class="fas fa-plus mr-2"></i>
                    Novo plano
                </button>
            @endcan
        </div>

        <div class="sticky top-16 z-20 -mx-6 mt-6 mb-4 border-b border-custom bg-surface px-6 py-4 lg:static lg:mx-0 lg:mt-6 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0">
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1 sm:max-w-md">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                    <input type="search"
                           id="membershipPlanSearch"
                           value="{{ request('search') }}"
                           placeholder="Buscar planos..."
                           class="input-field input-search"
                           autocomplete="off"
                           aria-label="Buscar planos">
                    <i id="membershipPlanSearchSpinner"
                       class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hidden"></i>
                </div>

                <select id="membershipPlanPeriodFilter" class="input-field sm:max-w-xs" aria-label="Filtrar por período">
                    <option value="">Todos os períodos</option>
                    @foreach (\App\Models\MembershipPlan::PERIOD_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected(request('period') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="membershipPlansResults">
            @include('pages.admin.membership-plans._table')
        </div>
    </div>
</div>

@include('pages.admin.membership-plans._modal')

<script>
    (function () {
        const searchInput = document.getElementById('membershipPlanSearch');
        const periodFilter = document.getElementById('membershipPlanPeriodFilter');
        const results = document.getElementById('membershipPlansResults');
        const spinner = document.getElementById('membershipPlanSearchSpinner');
        let debounceTimer = null;

        window.refreshMembershipPlansTable = async function (url) {
            const target = new URL(url || '{{ route('admin.membership-plans.index') }}', window.location.origin);

            if (!url) {
                const search = searchInput.value.trim();
                const period = periodFilter.value;

                target.searchParams.delete('page');

                if (search) {
                    target.searchParams.set('search', search);
                } else {
                    target.searchParams.delete('search');
                }

                if (period) {
                    target.searchParams.set('period', period);
                } else {
                    target.searchParams.delete('period');
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
                window.refreshMembershipPlansTable();
            }, 300);
        });

        periodFilter?.addEventListener('change', function () {
            window.refreshMembershipPlansTable();
        });

        results?.addEventListener('click', function (event) {
            const link = event.target.closest('nav[role="navigation"] a');

            if (link) {
                event.preventDefault();
                window.refreshMembershipPlansTable(link.href);
                return;
            }

            const editButton = event.target.closest('.edit-membership-plan');
            if (editButton) {
                window.openMembershipPlanModal(Number(editButton.dataset.planId));
                return;
            }

            const deleteButton = event.target.closest('.delete-membership-plan');
            if (deleteButton) {
                window.deleteMembershipPlan(Number(deleteButton.dataset.planId), deleteButton.dataset.planName);
            }
        });

        window.afterMembershipPlanSaved = function () {
            window.refreshMembershipPlansTable();
        };

        window.deleteMembershipPlan = function (id, name) {
            alertConfirm(
                'Excluir plano',
                `Deseja excluir o plano "${name}"?`,
                function () {
                    fetch(`{{ url('admin/membership-plans') }}/${id}`, {
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
                            window.refreshMembershipPlansTable();
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
