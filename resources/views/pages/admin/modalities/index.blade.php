@extends('layouts.app')

@section('title', 'Modalidades')

@section('content')
<div class="container mx-auto max-w-6xl p-4 sm:p-6">
    <div class="card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Modalidades</h1>
                <p class="mt-1 text-text-secondary">Atividades e serviços oferecidos pela rede e suas unidades</p>
            </div>

            <div class="flex flex-wrap gap-2">
                @can('modalities.manage')
                    <button type="button" onclick="openModalityCategoriesModal()" class="btn-outline whitespace-nowrap">
                        <i class="fas fa-tags mr-2"></i>
                        Categorias
                    </button>
                @endcan

                @can('modalities.create')
                    <button type="button" onclick="openModalityModal()" class="btn-primary whitespace-nowrap">
                        <i class="fas fa-plus mr-2"></i>
                        Nova modalidade
                    </button>
                @endcan
            </div>
        </div>

        <div class="sticky top-16 z-20 -mx-6 mt-6 mb-4 border-b border-custom bg-surface px-6 py-4 lg:static lg:mx-0 lg:mt-6 lg:border-0 lg:bg-transparent lg:px-0 lg:py-0">
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1 sm:max-w-md">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                    <input type="search"
                           id="modalitySearch"
                           value="{{ request('search') }}"
                           placeholder="Buscar por nome ou categoria..."
                           class="input-field input-search"
                           autocomplete="off"
                           aria-label="Buscar modalidades">
                    <i id="modalitySearchSpinner"
                       class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hidden"></i>
                </div>

                <select id="modalityCategoryFilter" class="input-field sm:max-w-xs" aria-label="Filtrar por categoria">
                    <option value="">Todas as categorias</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div id="modalitiesResults">
            @include('pages.admin.modalities._table')
        </div>
    </div>
</div>

@include('pages.admin.modalities._modal')
@include('pages.admin.modalities._units_modal')

@can('modalities.manage')
    @include('pages.admin.modalities._categories_modal')
@endcan

<script>
    (function () {
        const searchInput = document.getElementById('modalitySearch');
        const categoryFilter = document.getElementById('modalityCategoryFilter');
        const results = document.getElementById('modalitiesResults');
        const spinner = document.getElementById('modalitySearchSpinner');
        let debounceTimer = null;

        window.refreshModalitiesTable = async function (url) {
            const target = new URL(url || '{{ route('admin.modalities.index') }}', window.location.origin);

            if (!url) {
                const search = searchInput.value.trim();
                const categoryId = categoryFilter.value;

                target.searchParams.delete('page');

                if (search) {
                    target.searchParams.set('search', search);
                } else {
                    target.searchParams.delete('search');
                }

                if (categoryId) {
                    target.searchParams.set('category_id', categoryId);
                } else {
                    target.searchParams.delete('category_id');
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
                    throw new Error('Não foi possível carregar as modalidades.');
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
                window.refreshModalitiesTable();
            }, 300);
        });

        categoryFilter?.addEventListener('change', function () {
            window.refreshModalitiesTable();
        });

        results?.addEventListener('click', function (event) {
            const link = event.target.closest('nav[role="navigation"] a');

            if (link) {
                event.preventDefault();
                window.refreshModalitiesTable(link.href);
                return;
            }

            const editButton = event.target.closest('.edit-modality');
            if (editButton) {
                window.openModalityModal(Number(editButton.dataset.modalityId));
                return;
            }

            const unitsButton = event.target.closest('.manage-modality-units');
            if (unitsButton) {
                window.openModalityUnitsModal(
                    Number(unitsButton.dataset.modalityId),
                    unitsButton.dataset.modalityName
                );
                return;
            }

            const deleteButton = event.target.closest('.delete-modality');
            if (deleteButton) {
                window.deleteModality(Number(deleteButton.dataset.modalityId), deleteButton.dataset.modalityName);
            }
        });

        window.afterModalitySaved = function () {
            window.refreshModalitiesTable();
        };

        window.deleteModality = function (id, name) {
            alertConfirm(
                'Excluir modalidade',
                `Deseja excluir a modalidade "${name}"?`,
                function () {
                    fetch(`{{ url('admin/modalities') }}/${id}`, {
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
                                throw new Error(result.data.message || 'Não foi possível excluir a modalidade.');
                            }

                            alertSuccess('Excluída!', result.data.message);
                            window.refreshModalitiesTable();
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
