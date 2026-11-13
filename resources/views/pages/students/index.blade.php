@extends('layouts.app')

@section('title', 'Alunos')

@section('content')
<div class="container mx-auto max-w-6xl p-4 sm:p-6">
    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Alunos</h1>
                <p class="mt-1 text-text-secondary">Cadastro e gestão dos alunos da rede e suas unidades</p>
            </div>

            @can('students.create')
                <button type="button" onclick="openStudentModal()" class="btn-primary whitespace-nowrap">
                    <i class="fas fa-plus mr-2"></i>
                    Novo aluno
                </button>
            @endcan
        </div>

        <div class="sticky top-16 z-20 -mx-6 mt-6 mb-4 border-b border-custom bg-surface px-6 py-4 lg:static lg:mx-0 lg:mt-6 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0">
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1 sm:max-w-md">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                    <input type="search"
                           id="studentSearch"
                           value="{{ request('search') }}"
                           placeholder="Buscar por nome, CPF, e-mail ou telefone..."
                           class="input-field input-search"
                           autocomplete="off"
                           aria-label="Buscar alunos">
                    <i id="studentSearchSpinner"
                       class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hidden"></i>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:w-auto">
                    <select id="studentStatusFilter" class="input-field" aria-label="Filtrar por situação">
                        <option value="">Todas as situações</option>
                        <option value="active" @selected(request('status') === 'active')>Ativos</option>
                        <option value="pending" @selected(request('status') === 'pending')>Pendentes</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inativos</option>
                    </select>

                    <select id="studentUnitFilter" class="input-field" aria-label="Filtrar por unidade">
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

        <div id="studentsResults">
            @include('pages.students._table')
        </div>
    </div>
</div>

@include('pages.students._modal')

<script>
    (function () {
        const searchInput = document.getElementById('studentSearch');
        const statusFilter = document.getElementById('studentStatusFilter');
        const unitFilter = document.getElementById('studentUnitFilter');
        const results = document.getElementById('studentsResults');
        const spinner = document.getElementById('studentSearchSpinner');
        let debounceTimer = null;

        window.refreshStudentsTable = async function (url) {
            const target = new URL(url || '{{ route('students.index') }}', window.location.origin);

            if (!url) {
                target.searchParams.delete('page');

                const filters = {
                    search: searchInput.value.trim(),
                    status: statusFilter.value,
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
                    throw new Error('Não foi possível carregar os alunos.');
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
                window.refreshStudentsTable();
            }, 300);
        });

        [statusFilter, unitFilter].forEach(function (filter) {
            filter?.addEventListener('change', function () {
                window.refreshStudentsTable();
            });
        });

        results?.addEventListener('click', function (event) {
            const link = event.target.closest('nav[role="navigation"] a');

            if (link) {
                event.preventDefault();
                window.refreshStudentsTable(link.href);
                return;
            }

            const editButton = event.target.closest('.edit-student');
            if (editButton) {
                window.openStudentModal(Number(editButton.dataset.studentId));
                return;
            }

            const toggleButton = event.target.closest('.toggle-student');
            if (toggleButton) {
                window.toggleStudentStatus(
                    Number(toggleButton.dataset.studentId),
                    toggleButton.dataset.studentName,
                    toggleButton.dataset.studentActive === '1'
                );
                return;
            }

            const deleteButton = event.target.closest('.delete-student');
            if (deleteButton) {
                window.deleteStudent(Number(deleteButton.dataset.studentId), deleteButton.dataset.studentName);
            }
        });

        window.afterStudentSaved = function () {
            window.refreshStudentsTable();
        };

        window.toggleStudentStatus = function (id, name, isActive) {
            const action = isActive ? 'inativar' : 'ativar';

            alertConfirm(
                isActive ? 'Inativar aluno' : 'Ativar aluno',
                `Deseja ${action} o aluno "${name}"?`,
                function () {
                    fetch(`{{ url('students') }}/${id}/status`, {
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
                                throw new Error(result.data.message || 'Não foi possível alterar a situação.');
                            }

                            alertSuccess('Atualizado!', result.data.message);
                            window.refreshStudentsTable();
                        })
                        .catch(function (error) {
                            alertError('Erro', error.message);
                        });
                }
            );
        };

        window.deleteStudent = function (id, name) {
            alertConfirm(
                'Excluir aluno',
                `Deseja excluir o aluno "${name}"?`,
                function () {
                    fetch(`{{ url('students') }}/${id}`, {
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
                                throw new Error(result.data.message || 'Não foi possível excluir o aluno.');
                            }

                            alertSuccess('Excluído!', result.data.message);
                            window.refreshStudentsTable();
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
