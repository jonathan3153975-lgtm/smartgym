@extends('layouts.app')

@section('title', 'Planos de treino')

@section('content')
<div class="container mx-auto max-w-6xl p-4 sm:p-6">
    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Planos de treino</h1>
                <p class="mt-1 text-text-secondary">Monte treinos para os alunos e acompanhe a execução pelo portal</p>
            </div>

            <button type="button" onclick="openWorkoutPlanModal()" class="btn-primary whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i>
                Novo plano
            </button>
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1 sm:max-w-md">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                <input type="search"
                       id="workoutPlanSearch"
                       value="{{ request('search') }}"
                       placeholder="Buscar por plano, aluno ou treinador..."
                       class="input-field input-search"
                       autocomplete="off"
                       aria-label="Buscar planos de treino">
                <i id="workoutPlanSearchSpinner"
                   class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hidden"></i>
            </div>

            <select id="workoutPlanStatusFilter" class="input-field sm:max-w-xs" aria-label="Filtrar por status">
                <option value="">Todos os status</option>
                <option value="active" @selected(request('status') === 'active')>Ativos</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inativos</option>
            </select>
        </div>

        <div id="workoutPlansResults" class="mt-4">
            @include('pages.admin.workout-plans._table')
        </div>
    </div>
</div>

@include('pages.admin.workout-plans._modal')

<script>
    (function () {
        const searchInput = document.getElementById('workoutPlanSearch');
        const statusFilter = document.getElementById('workoutPlanStatusFilter');
        const results = document.getElementById('workoutPlansResults');
        const spinner = document.getElementById('workoutPlanSearchSpinner');
        let debounceTimer = null;

        window.refreshWorkoutPlansTable = async function (url) {
            const target = new URL(url || '{{ route('admin.workout-plans.index') }}', window.location.origin);

            if (!url) {
                target.searchParams.delete('page');

                const filters = {
                    search: searchInput.value.trim(),
                    status: statusFilter.value,
                };

                Object.entries(filters).forEach(function ([key, value]) {
                    if (value) {
                        target.searchParams.set(key, value);
                    } else {
                        target.searchParams.delete(key);
                    }
                });
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
                window.refreshWorkoutPlansTable();
            }, 300);
        });

        statusFilter?.addEventListener('change', function () {
            window.refreshWorkoutPlansTable();
        });

        results?.addEventListener('click', function (event) {
            const link = event.target.closest('nav[role="navigation"] a');

            if (link) {
                event.preventDefault();
                window.refreshWorkoutPlansTable(link.href);
                return;
            }

            const editButton = event.target.closest('.edit-workout-plan');
            if (editButton) {
                window.openWorkoutPlanModal(Number(editButton.dataset.planId));
                return;
            }

            const toggleButton = event.target.closest('.toggle-workout-plan');
            if (toggleButton) {
                window.toggleWorkoutPlan(Number(toggleButton.dataset.planId));
                return;
            }

            const deleteButton = event.target.closest('.delete-workout-plan');
            if (deleteButton) {
                window.deleteWorkoutPlan(Number(deleteButton.dataset.planId), deleteButton.dataset.planName);
            }
        });

        window.afterWorkoutPlanSaved = function (data) {
            if (data && data.id) {
                window.location.href = `{{ url('admin/workout-plans') }}/${data.id}`;
                return;
            }

            window.refreshWorkoutPlansTable();
        };

        window.toggleWorkoutPlan = function (id) {
            fetch(`{{ url('admin/workout-plans') }}/${id}/status`, {
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
                    window.refreshWorkoutPlansTable();
                })
                .catch(function (error) {
                    alertError('Erro', error.message);
                });
        };

        window.deleteWorkoutPlan = function (id, name) {
            alertConfirm(
                'Excluir plano de treino',
                `Deseja excluir o plano "${name}"?`,
                function () {
                    fetch(`{{ url('admin/workout-plans') }}/${id}`, {
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
                            window.refreshWorkoutPlansTable();
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
