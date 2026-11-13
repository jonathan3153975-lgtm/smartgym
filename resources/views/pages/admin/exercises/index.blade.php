@extends('layouts.app')

@section('title', 'Exercícios')

@section('content')
<div class="container mx-auto max-w-6xl p-4 sm:p-6">
    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Exercícios</h1>
                <p class="mt-1 text-text-secondary">Catálogo de exercícios usado na montagem dos planos de treino</p>
            </div>

            <button type="button" onclick="openExerciseModal()" class="btn-primary whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i>
                Novo exercício
            </button>
        </div>

        <div class="sticky top-16 z-20 -mx-6 mt-6 mb-4 border-b border-custom bg-surface px-6 py-4 lg:static lg:mx-0 lg:mt-6 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0">
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1 sm:max-w-md">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                    <input type="search"
                           id="exerciseSearch"
                           value="{{ request('search') }}"
                           placeholder="Buscar por nome ou equipamento..."
                           class="input-field input-search"
                           autocomplete="off"
                           aria-label="Buscar exercícios">
                    <i id="exerciseSearchSpinner"
                       class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hidden"></i>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:w-auto lg:grid-cols-3">
                    <select id="exerciseGroupFilter" class="input-field" aria-label="Filtrar por grupo muscular">
                        <option value="">Todos os grupos</option>
                        @foreach (\App\Models\Exercise::MUSCLE_GROUPS as $value => $label)
                            <option value="{{ $value }}" @selected((string) request('muscle_group') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <select id="exerciseEquipmentFilter" class="input-field" aria-label="Filtrar por equipamento">
                        <option value="">Todos os equipamentos</option>
                        @foreach (\App\Models\Exercise::EQUIPMENT_OPTIONS as $value => $label)
                            <option value="{{ $value }}" @selected((string) request('equipment') === (string) $value)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <select id="exerciseStatusFilter" class="input-field" aria-label="Filtrar por status">
                        <option value="">Todos os status</option>
                        <option value="active" @selected(request('status') === 'active')>Ativos</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inativos</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="exercisesResults">
            @include('pages.admin.exercises._table')
        </div>
    </div>
</div>

@include('pages.admin.exercises._modal')

<script>
    (function () {
        const searchInput = document.getElementById('exerciseSearch');
        const groupFilter = document.getElementById('exerciseGroupFilter');
        const equipmentFilter = document.getElementById('exerciseEquipmentFilter');
        const statusFilter = document.getElementById('exerciseStatusFilter');
        const results = document.getElementById('exercisesResults');
        const spinner = document.getElementById('exerciseSearchSpinner');
        const filterPairs = @json($filterPairs ?? []);
        let debounceTimer = null;

        function filterOptionsByPairs() {
            const selectedGroup = groupFilter.value;
            const selectedEquipment = equipmentFilter.value;

            const equipmentForGroup = selectedGroup ? (filterPairs[selectedGroup] || []) : null;
            const groupsForEquipment = Object.entries(filterPairs)
                .filter(([, equipments]) => !selectedEquipment || equipments.includes(selectedEquipment))
                .map(([group]) => group);

            Array.from(equipmentFilter.options).forEach(function (option) {
                if (!option.value) {
                    return;
                }

                const matches = !equipmentForGroup || equipmentForGroup.includes(option.value);
                option.disabled = !matches;
                option.hidden = !matches;
            });

            Array.from(groupFilter.options).forEach(function (option) {
                if (!option.value) {
                    return;
                }

                const matches = !selectedEquipment || groupsForEquipment.includes(option.value);
                option.disabled = !matches;
                option.hidden = !matches;
            });

            if (equipmentFilter.value && equipmentFilter.selectedOptions[0]?.disabled) {
                equipmentFilter.value = '';
            }

            if (groupFilter.value && groupFilter.selectedOptions[0]?.disabled) {
                groupFilter.value = '';
            }
        }

        filterOptionsByPairs();

        window.refreshExercisesTable = async function (url) {
            const target = new URL(url || '{{ route('admin.exercises.index') }}', window.location.origin);

            if (!url) {
                target.searchParams.delete('page');

                const filters = {
                    search: searchInput.value.trim(),
                    muscle_group: groupFilter.value,
                    equipment: equipmentFilter.value,
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
                    throw new Error('Não foi possível carregar os exercícios.');
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
                window.refreshExercisesTable();
            }, 300);
        });

        [groupFilter, equipmentFilter].forEach(function (filter) {
            filter?.addEventListener('change', function () {
                filterOptionsByPairs();
                window.refreshExercisesTable();
            });
        });

        statusFilter?.addEventListener('change', function () {
            window.refreshExercisesTable();
        });

        results?.addEventListener('click', function (event) {
            const link = event.target.closest('nav[role="navigation"] a');

            if (link) {
                event.preventDefault();
                window.refreshExercisesTable(link.href);
                return;
            }

            const editButton = event.target.closest('.edit-exercise');
            if (editButton) {
                window.openExerciseModal(Number(editButton.dataset.exerciseId));
                return;
            }

            const toggleButton = event.target.closest('.toggle-exercise');
            if (toggleButton) {
                window.toggleExercise(Number(toggleButton.dataset.exerciseId));
                return;
            }

            const deleteButton = event.target.closest('.delete-exercise');
            if (deleteButton) {
                window.deleteExercise(Number(deleteButton.dataset.exerciseId), deleteButton.dataset.exerciseName);
            }
        });

        window.afterExerciseSaved = function () {
            window.refreshExercisesTable();
        };

        window.toggleExercise = function (id) {
            fetch(`{{ url('admin/exercises') }}/${id}/status`, {
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
                    window.refreshExercisesTable();
                })
                .catch(function (error) {
                    alertError('Erro', error.message);
                });
        };

        window.deleteExercise = function (id, name) {
            alertConfirm(
                'Excluir exercício',
                `Deseja excluir o exercício "${name}"?`,
                function () {
                    fetch(`{{ url('admin/exercises') }}/${id}`, {
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
                                throw new Error(result.data.message || 'Não foi possível excluir o exercício.');
                            }

                            alertSuccess('Excluído!', result.data.message);
                            window.refreshExercisesTable();
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
