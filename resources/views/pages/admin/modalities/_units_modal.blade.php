{{-- Link / unlink units to a modality --}}
<div id="modalityUnitsModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modalityUnitsModalTitle">
    <div class="modal-content">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h2 id="modalityUnitsModalTitle" class="text-xl font-bold">Unidades da modalidade</h2>
                <p id="modalityUnitsModalSubtitle" class="text-sm text-text-secondary"></p>
            </div>
            <button type="button" onclick="closeModalityUnitsModal()"
                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="modalityUnitsForm" class="space-y-4">
            <div class="max-h-72 overflow-y-auto rounded-xl border border-custom bg-bg-secondary p-4">
                <div id="modalityUnitsList" class="space-y-2">
                    <p class="text-sm text-text-secondary">Carregando unidades...</p>
                </div>
            </div>

            <p id="modalityUnitsError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

            <div class="flex justify-end gap-3 border-t border-custom pt-4">
                <button type="button" onclick="closeModalityUnitsModal()" class="btn-outline">Cancelar</button>
                <button type="submit" class="btn-primary" id="modalityUnitsSubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    Salvar unidades
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('modalityUnitsModal');
        const form = document.getElementById('modalityUnitsForm');
        const subtitle = document.getElementById('modalityUnitsModalSubtitle');
        const list = document.getElementById('modalityUnitsList');
        const errorBox = document.getElementById('modalityUnitsError');
        const submitBtn = document.getElementById('modalityUnitsSubmitBtn');

        if (!modal || !form) {
            return;
        }

        let currentId = null;

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = value ?? '';

            return div.innerHTML;
        }

        window.openModalityUnitsModal = function (id, name) {
            currentId = id;
            subtitle.textContent = name ? `Selecione as unidades que podem oferecer "${name}".` : '';
            list.innerHTML = '<p class="text-sm text-text-secondary">Carregando unidades...</p>';
            errorBox.classList.add('hidden');
            errorBox.textContent = '';

            fetch(`{{ route('admin.modalities.units-data', ':id') }}`.replace(':id', id), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Não foi possível carregar as unidades.');
                    }

                    return response.json();
                })
                .then(function (data) {
                    const selected = Array.isArray(data.selected) ? data.selected.map(Number) : [];
                    const units = Array.isArray(data.units) ? data.units : [];

                    if (!units.length) {
                        list.innerHTML = '<p class="text-sm text-text-secondary">Nenhuma unidade disponível para o seu escopo.</p>';
                        return;
                    }

                    list.innerHTML = units.map(function (unit) {
                        const checked = selected.includes(Number(unit.id)) ? ' checked' : '';

                        return `
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-custom bg-surface px-3 py-2 text-sm transition-colors hover:border-cyan">
                                <input type="checkbox" name="unit_ids[]" value="${unit.id}" class="rounded border-custom bg-bg-secondary text-cyan focus:ring-cyan"${checked}>
                                <span>${escapeHtml(unit.name)}</span>
                            </label>
                        `;
                    }).join('');
                })
                .catch(function (error) {
                    list.innerHTML = '';
                    errorBox.textContent = error.message;
                    errorBox.classList.remove('hidden');
                });

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        window.closeModalityUnitsModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            currentId = null;
        };

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            errorBox.classList.add('hidden');
            submitBtn.disabled = true;

            fetch(`{{ url('admin/modalities') }}/${currentId}/units`, {
                method: 'PUT',
                body: new FormData(form),
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            })
                .then(function (response) {
                    return response.json().then(function (data) {
                        return { ok: response.ok, status: response.status, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.ok) {
                        if (result.status === 422 && result.data.errors) {
                            throw new Error(Object.values(result.data.errors).flat().join(' '));
                        }

                        throw new Error(result.data.message || 'Não foi possível salvar as unidades.');
                    }

                    alertSuccess('Atualizado!', result.data.message);
                    window.closeModalityUnitsModal();

                    if (typeof window.refreshModalitiesTable === 'function') {
                        window.refreshModalitiesTable();
                    } else {
                        window.location.reload();
                    }
                })
                .catch(function (error) {
                    errorBox.textContent = error.message;
                    errorBox.classList.remove('hidden');
                })
                .finally(function () {
                    submitBtn.disabled = false;
                });
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                window.closeModalityUnitsModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closeModalityUnitsModal();
            }
        });
    })();
</script>
