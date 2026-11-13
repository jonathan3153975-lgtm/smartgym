@extends('layouts.app')

@section('title', 'Cadastro de Pessoas')

@section('content')
<div class="p-4 sm:p-6">
    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Cadastro de pessoas</h1>
                <p class="mt-1 text-text-secondary">Pessoas físicas e jurídicas da rede, classificadas como equipe ou clientes</p>
            </div>
            @can('people.create')
                <button type="button" onclick="openPersonModal()" class="btn-primary whitespace-nowrap">
                    <i class="fas fa-plus mr-2"></i>
                    Nova pessoa
                </button>
            @endcan
        </div>

        {{-- Search and filters (sticky on mobile) --}}
        <div class="sticky top-16 z-20 -mx-6 mt-6 mb-4 border-b border-custom bg-surface px-6 py-4 lg:static lg:mx-0 lg:mt-6 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                <div class="relative flex-1 lg:max-w-md">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                    <input type="search"
                           id="personSearch"
                           value="{{ request('search') }}"
                           placeholder="Buscar por nome, documento, e-mail ou telefone..."
                           class="input-field input-search"
                           autocomplete="off"
                           aria-label="Buscar pessoas">
                    <i id="personSearchSpinner"
                       class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hidden"></i>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:w-auto">
                    <select id="personTypeFilter" class="input-field" aria-label="Filtrar por tipo de pessoa">
                        <option value="">Todos os tipos</option>
                        <option value="individual" @selected(request('person_type') === 'individual')>Física</option>
                        <option value="company" @selected(request('person_type') === 'company')>Jurídica</option>
                    </select>

                    <select id="personCategoryFilter" class="input-field" aria-label="Filtrar por categoria">
                        <option value="">Todas as categorias</option>
                        <option value="staff" @selected(request('category') === 'staff')>Equipe</option>
                        <option value="client" @selected(request('category') === 'client')>Cliente</option>
                    </select>

                    <select id="personUnitFilter" class="input-field col-span-2 sm:col-span-1" aria-label="Filtrar por unidade">
                        <option value="">Todas as unidades</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected((string) request('unit_id') === (string) $unit->id)>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div id="peopleResults">
            @include('pages.admin.people._table')
        </div>
    </div>
</div>

@include('pages.admin.people._modal')

<script>
    (function () {
        const searchInput = document.getElementById('personSearch');
        const typeFilter = document.getElementById('personTypeFilter');
        const categoryFilter = document.getElementById('personCategoryFilter');
        const unitFilter = document.getElementById('personUnitFilter');
        const results = document.getElementById('peopleResults');
        const spinner = document.getElementById('personSearchSpinner');
        let debounceTimer = null;

        window.refreshPeopleTable = async function (url) {
            const target = new URL(url || '{{ route('admin.people.index') }}', window.location.origin);

            if (!url) {
                const search = searchInput.value.trim();

                target.searchParams.delete('page');

                const filters = {
                    search,
                    person_type: typeFilter.value,
                    category: categoryFilter.value,
                    unit_id: unitFilter.value,
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
                    throw new Error('Não foi possível carregar as pessoas.');
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
                window.refreshPeopleTable();
            }, 300);
        });

        [typeFilter, categoryFilter, unitFilter].forEach(function (filter) {
            filter?.addEventListener('change', function () {
                window.refreshPeopleTable();
            });
        });

        results?.addEventListener('click', function (event) {
            const link = event.target.closest('nav[role="navigation"] a');

            if (link) {
                event.preventDefault();
                window.refreshPeopleTable(link.href);
                return;
            }

            const editButton = event.target.closest('.edit-person');
            if (editButton) {
                window.openPersonModal(Number(editButton.dataset.personId));
                return;
            }

            const toggleButton = event.target.closest('.toggle-person');
            if (toggleButton) {
                window.togglePersonStatus(
                    Number(toggleButton.dataset.personId),
                    toggleButton.dataset.personName,
                    toggleButton.dataset.personActive === '1'
                );
                return;
            }

            const deleteButton = event.target.closest('.delete-person');
            if (deleteButton) {
                window.deletePerson(Number(deleteButton.dataset.personId), deleteButton.dataset.personName);
            }
        });

        window.afterPersonSaved = function () {
            window.refreshPeopleTable();
        };

        window.togglePersonStatus = function (id, name, isActive) {
            const action = isActive ? 'inativar' : 'ativar';

            alertConfirm(
                isActive ? 'Inativar pessoa' : 'Ativar pessoa',
                `Deseja ${action} a pessoa "${name}"?`,
                function () {
                    fetch(`{{ url('admin/people') }}/${id}/status`, {
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
                            window.refreshPeopleTable();
                        })
                        .catch(function (error) {
                            alertError('Erro', error.message);
                        });
                }
            );
        };

        window.deletePerson = function (id, name) {
            alertConfirm(
                'Excluir pessoa',
                `Deseja excluir a pessoa "${name}"?`,
                function () {
                    fetch(`{{ url('admin/people') }}/${id}`, {
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
                                throw new Error(result.data.message || 'Não foi possível excluir a pessoa.');
                            }

                            alertSuccess('Excluído!', result.data.message);
                            window.refreshPeopleTable();
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
