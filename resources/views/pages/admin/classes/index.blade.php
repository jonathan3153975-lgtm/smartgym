@extends('layouts.app')

@section('title', 'Turmas')

@section('content')
<div class="container mx-auto max-w-6xl p-4 sm:p-6">
    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Turmas</h1>
                <p class="mt-1 text-text-secondary">Turmas vinculadas às unidades, com modalidade, dia, horário, vagas e professor</p>
            </div>

            <button type="button" onclick="openClassModal()" class="btn-primary whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i>
                Nova turma
            </button>
        </div>

        <div class="sticky top-16 z-20 -mx-6 mt-6 mb-4 border-b border-custom bg-surface px-6 py-4 lg:static lg:mx-0 lg:mt-6 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0">
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1 sm:max-w-md">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                    <input type="search"
                           id="classSearch"
                           value="{{ request('search') }}"
                           placeholder="Buscar por turma, modalidade, unidade ou professor..."
                           class="input-field input-search"
                           autocomplete="off"
                           aria-label="Buscar turmas">
                    <i id="classSearchSpinner"
                       class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hidden"></i>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:w-auto">
                    <select id="classUnitFilter" class="input-field" aria-label="Filtrar por unidade">
                        <option value="">Todas as unidades</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected((string) request('unit_id') === (string) $unit->id)>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>

                    <select id="classWeekdayFilter" class="input-field" aria-label="Filtrar por dia da semana">
                        <option value="">Todos os dias</option>
                        @foreach (\App\Models\SchoolClass::WEEKDAYS as $value => $label)
                            <option value="{{ $value }}" @selected((string) request('weekday') === (string) $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div id="classesResults">
            @include('pages.admin.classes._table')
        </div>
    </div>
</div>

@include('pages.admin.classes._modal')

<script>
    (function () {
        const searchInput = document.getElementById('classSearch');
        const unitFilter = document.getElementById('classUnitFilter');
        const weekdayFilter = document.getElementById('classWeekdayFilter');
        const results = document.getElementById('classesResults');
        const spinner = document.getElementById('classSearchSpinner');
        let debounceTimer = null;

        window.refreshClassesTable = async function (url) {
            const target = new URL(url || '{{ route('admin.classes.index') }}', window.location.origin);

            if (!url) {
                target.searchParams.delete('page');

                const filters = {
                    search: searchInput.value.trim(),
                    unit_id: unitFilter.value,
                    weekday: weekdayFilter.value,
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
                    throw new Error('Não foi possível carregar as turmas.');
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
                window.refreshClassesTable();
            }, 300);
        });

        [unitFilter, weekdayFilter].forEach(function (filter) {
            filter?.addEventListener('change', function () {
                window.refreshClassesTable();
            });
        });

        results?.addEventListener('click', function (event) {
            const link = event.target.closest('nav[role="navigation"] a');

            if (link) {
                event.preventDefault();
                window.refreshClassesTable(link.href);
                return;
            }

            const editButton = event.target.closest('.edit-class');
            if (editButton) {
                window.openClassModal(Number(editButton.dataset.classId));
                return;
            }

            const deleteButton = event.target.closest('.delete-class');
            if (deleteButton) {
                window.deleteClass(Number(deleteButton.dataset.classId), deleteButton.dataset.className);
            }
        });

        window.afterClassSaved = function () {
            window.refreshClassesTable();
        };

        window.deleteClass = function (id, name) {
            alertConfirm(
                'Excluir turma',
                `Deseja excluir a turma "${name}"?`,
                function () {
                    fetch(`{{ url('admin/classes') }}/${id}`, {
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
                                throw new Error(result.data.message || 'Não foi possível excluir a turma.');
                            }

                            alertSuccess('Excluída!', result.data.message);
                            window.refreshClassesTable();
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
