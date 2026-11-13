{{-- Simple modality category registry --}}
<div id="modalityCategoriesModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modalityCategoriesModalTitle">
    <div class="modal-content">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 id="modalityCategoriesModalTitle" class="text-xl font-bold">Categorias de modalidades</h2>
                <p class="text-sm text-text-secondary">Cadastro simples, apenas com o nome.</p>
            </div>
            <button type="button" onclick="closeModalityCategoriesModal()"
                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="modalityCategoryForm" class="flex gap-2">
            <input type="text" id="modalityCategoryName" maxlength="255" required class="input-field"
                   placeholder="Nome da categoria">
            <button type="submit" class="btn-primary whitespace-nowrap">
                <i class="fas fa-plus mr-2"></i>
                Adicionar
            </button>
        </form>

        <p id="modalityCategoryError"
           class="mt-3 hidden rounded-lg px-4 py-3 text-sm"
           style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

        <div id="modalityCategoriesList" class="mt-4 max-h-72 space-y-2 overflow-y-auto">
            <p class="text-sm text-text-secondary">Carregando categorias...</p>
        </div>

        <div class="mt-4 flex justify-end border-t border-custom pt-4">
            <button type="button" onclick="closeModalityCategoriesModal()" class="btn-outline">Fechar</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('modalityCategoriesModal');
        const form = document.getElementById('modalityCategoryForm');
        const nameInput = document.getElementById('modalityCategoryName');
        const list = document.getElementById('modalityCategoriesList');
        const errorBox = document.getElementById('modalityCategoryError');

        if (!modal || !form) {
            return;
        }

        let categories = [];

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';

            return div.innerHTML;
        }

        function showError(message) {
            errorBox.textContent = message;
            errorBox.classList.remove('hidden');
        }

        function clearError() {
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
        }

        function renderList() {
            if (!categories.length) {
                list.innerHTML = '<p class="text-sm text-text-secondary">Nenhuma categoria cadastrada.</p>';
                return;
            }

            list.innerHTML = categories.map(function (category) {
                return `
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-custom bg-bg-secondary px-3 py-2">
                        <span class="min-w-0 truncate text-sm">${escapeHtml(category.name)}</span>
                        <span class="flex items-center gap-1">
                            <span class="badge badge-info">${category.modalities_count ?? 0}</span>
                            <button type="button" class="edit-modality-category p-2 text-text-secondary transition-colors hover:bg-surface" data-id="${category.id}" title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" class="delete-modality-category p-2 text-text-secondary transition-colors hover:bg-surface" data-id="${category.id}" title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </span>
                    </div>
                `;
            }).join('');
        }

        function refreshCategorySelect() {
            const select = document.getElementById('modality_category_id');

            if (!select) {
                return;
            }

            const current = select.value;
            select.innerHTML = '<option value="">Sem categoria</option>' + categories.map(function (category) {
                return `<option value="${category.id}">${escapeHtml(category.name)}</option>`;
            }).join('');
            select.value = categories.some(function (category) {
                return String(category.id) === String(current);
            }) ? current : '';
        }

        function loadCategories() {
            return fetch('{{ route('admin.modality-categories.index') }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Não foi possível carregar as categorias.');
                    }

                    return response.json();
                })
                .then(function (data) {
                    categories = Array.isArray(data.data) ? data.data : [];
                    renderList();
                    refreshCategorySelect();
                })
                .catch(function (error) {
                    showError(error.message);
                });
        }

        window.openModalityCategoriesModal = function () {
            clearError();
            nameInput.value = '';
            list.innerHTML = '<p class="text-sm text-text-secondary">Carregando categorias...</p>';
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            loadCategories();
            setTimeout(function () {
                nameInput.focus();
            }, 100);
        };

        window.closeModalityCategoriesModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        };

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            clearError();
            const name = nameInput.value.trim();

            if (!name) {
                return;
            }

            fetch('{{ route('admin.modality-categories.store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ name }),
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.ok) {
                        throw new Error(result.data.message || 'Não foi possível cadastrar a categoria.');
                    }

                    nameInput.value = '';
                    return loadCategories();
                })
                .catch(function (error) {
                    showError(error.message);
                });
        });

        list.addEventListener('click', function (event) {
            const editButton = event.target.closest('.edit-modality-category');
            const deleteButton = event.target.closest('.delete-modality-category');

            if (editButton) {
                const category = categories.find(function (item) {
                    return String(item.id) === String(editButton.dataset.id);
                });

                if (!category) {
                    return;
                }

                Swal.fire({
                    title: 'Editar categoria',
                    input: 'text',
                    inputValue: category.name,
                    showCancelButton: true,
                    confirmButtonText: 'Salvar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#0D6EFD',
                    background: getComputedStyle(document.documentElement).getPropertyValue('--color-surface-elevated').trim() || '#142944',
                    color: getComputedStyle(document.documentElement).getPropertyValue('--color-text-primary').trim() || '#F8FAFC',
                    inputValidator: function (value) {
                        return value && value.trim() ? undefined : 'Informe o nome da categoria.';
                    },
                }).then(function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    fetch(`{{ url('admin/modality-categories') }}/${editButton.dataset.id}`, {
                        method: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ name: result.value.trim() }),
                    })
                        .then(function (response) {
                            return response.json().then(function (data) {
                                return { ok: response.ok, data: data };
                            });
                        })
                        .then(function (resultData) {
                            if (!resultData.ok) {
                                throw new Error(resultData.data.message || 'Não foi possível atualizar a categoria.');
                            }

                            return loadCategories();
                        })
                        .catch(function (error) {
                            showError(error.message);
                        });
                });
            }

            if (deleteButton) {
                const category = categories.find(function (item) {
                    return String(item.id) === String(deleteButton.dataset.id);
                });

                if (!category) {
                    return;
                }

                alertConfirm(
                    'Excluir categoria',
                    `Deseja excluir a categoria "${category.name}"?`,
                    function () {
                        fetch(`{{ url('admin/modality-categories') }}/${deleteButton.dataset.id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
                                    throw new Error(result.data.message || 'Não foi possível excluir a categoria.');
                                }

                                return loadCategories();
                            })
                            .catch(function (error) {
                                showError(error.message);
                            });
                    }
                );
            }
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                window.closeModalityCategoriesModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closeModalityCategoriesModal();
            }
        });
    })();
</script>
