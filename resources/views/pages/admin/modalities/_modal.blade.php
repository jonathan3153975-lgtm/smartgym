{{-- Modality create/edit modal --}}
<div id="modalityModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="modalityModalTitle">
    <div class="modal-content modal-content-lg">
        <div class="mb-6 flex items-center justify-between">
            <h2 id="modalityModalTitle" class="text-xl font-bold">Nova modalidade</h2>
            <button type="button" onclick="closeModalityModal()"
                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="modalityForm" method="POST" action="{{ route('admin.modalities.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" id="modalityFormMethod" value="POST">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="modality_name" class="label-field">Nome <span class="text-red">*</span></label>
                    <input type="text" id="modality_name" name="name" required maxlength="255" class="input-field"
                           placeholder="Ex.: Musculação, Crossfit, Pilates">
                </div>

                <div>
                    <label for="modality_category_id" class="label-field">Categoria</label>
                    <select id="modality_category_id" name="modality_category_id" class="input-field">
                        <option value="">Sem categoria</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="modality_image" class="label-field">Imagem ilustrativa</label>
                    <input type="file" id="modality_image" name="image"
                           accept="image/png,image/jpeg,image/webp"
                           class="input-field file:mr-3 file:rounded-md file:border-0 file:bg-cyan/10 file:px-3 file:py-1 file:text-sm file:font-medium file:text-cyan">
                    <p class="mt-1 text-xs text-text-secondary">Opcional. PNG, JPG ou WebP, até 2 MB.</p>
                </div>

                <div class="sm:col-span-2">
                    <div id="modalityImagePreviewWrap" class="hidden items-center gap-3 rounded-xl border border-custom bg-bg-secondary p-3">
                        <img id="modalityImagePreview" src="" alt="Prévia da imagem" class="h-20 w-20 rounded-lg object-cover">
                        <div class="min-w-0">
                            <p class="text-sm font-medium">Imagem atual</p>
                            <label class="mt-1 inline-flex cursor-pointer items-center gap-2 text-sm text-red">
                                <input type="checkbox" name="remove_image" value="1">
                                Remover imagem
                            </label>
                        </div>
                    </div>
                </div>

                <div id="modalityLevelInfo" class="hidden sm:col-span-2 rounded-lg border border-custom bg-bg-secondary px-4 py-3 text-sm text-text-secondary"></div>
            </div>

            <p id="modalityFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

            <div class="flex justify-end gap-3 border-t border-custom pt-4">
                <button type="button" onclick="closeModalityModal()" class="btn-outline">Cancelar</button>
                <button type="submit" class="btn-primary" id="modalitySubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    <span id="modalitySubmitBtnText">Salvar modalidade</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('modalityModal');
        const form = document.getElementById('modalityForm');
        const title = document.getElementById('modalityModalTitle');
        const methodInput = document.getElementById('modalityFormMethod');
        const submitText = document.getElementById('modalitySubmitBtnText');
        const submitBtn = document.getElementById('modalitySubmitBtn');
        const errorBox = document.getElementById('modalityFormError');
        const nameInput = document.getElementById('modality_name');
        const categorySelect = document.getElementById('modality_category_id');
        const imageInput = document.getElementById('modality_image');
        const previewWrap = document.getElementById('modalityImagePreviewWrap');
        const preview = document.getElementById('modalityImagePreview');
        const levelInfo = document.getElementById('modalityLevelInfo');
        const removeImage = form.querySelector('input[name="remove_image"]');

        if (!modal || !form) {
            return;
        }

        let currentId = null;

        function showPreview(url) {
            if (!url) {
                previewWrap.classList.add('hidden');
                previewWrap.classList.remove('flex');
                preview.removeAttribute('src');
                return;
            }

            preview.src = url;
            previewWrap.classList.remove('hidden');
            previewWrap.classList.add('flex');
        }

        imageInput?.addEventListener('change', function () {
            const file = this.files && this.files[0];

            if (!file) {
                return;
            }

            if (removeImage) {
                removeImage.checked = false;
            }

            showPreview(URL.createObjectURL(file));
        });

        window.openModalityModal = function (id = null) {
            form.reset();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            levelInfo.classList.add('hidden');
            levelInfo.textContent = '';
            showPreview(null);
            currentId = id;

            if (id) {
                title.textContent = 'Editar modalidade';
                submitText.textContent = 'Atualizar';
                methodInput.value = 'PUT';
                form.action = `{{ route('admin.modalities.update', ':id') }}`.replace(':id', id);

                fetch(`{{ route('admin.modalities.edit-data', ':id') }}`.replace(':id', id), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Não foi possível carregar a modalidade.');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        nameInput.value = data.name ?? '';
                        categorySelect.value = data.modality_category_id ?? '';
                        showPreview(data.image_url);

                        if (data.created_level_label) {
                            levelInfo.textContent = 'Modalidade criada no nível: ' + data.created_level_label + '.';
                            levelInfo.classList.remove('hidden');
                        }
                    })
                    .catch(function (error) {
                        alertError('Erro', error.message);
                    });
            } else {
                title.textContent = 'Nova modalidade';
                submitText.textContent = 'Salvar modalidade';
                methodInput.value = 'POST';
                form.action = '{{ route('admin.modalities.store') }}';
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                nameInput.focus();
            }, 100);
        };

        window.closeModalityModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            currentId = null;
        };

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            errorBox.classList.add('hidden');
            submitBtn.disabled = true;

            fetch(form.action, {
                method: 'POST',
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

                        throw new Error(result.data.message || 'Não foi possível salvar a modalidade.');
                    }

                    alertSuccess(currentId ? 'Atualizada!' : 'Cadastrada!', result.data.message);
                    window.closeModalityModal();

                    if (typeof window.afterModalitySaved === 'function') {
                        window.afterModalitySaved(result.data.data);
                    } else {
                        setTimeout(function () {
                            window.location.reload();
                        }, 600);
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
                window.closeModalityModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closeModalityModal();
            }
        });
    })();
</script>
