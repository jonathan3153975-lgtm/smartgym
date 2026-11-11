@php
    $contextNetwork = $network ?? null;
    $regionNetworkOptions = $contextNetwork ? collect([$contextNetwork]) : \App\Support\TenantContext::current()->networksQuery()->get(['id', 'name']);
    $selectedRegionNetworkId = old('network_id', $contextNetwork?->id ?? ($regionNetworkOptions->count() === 1 ? $regionNetworkOptions->first()->id : null));
@endphp

{{-- Region Modal (create/edit). Pass $network to lock the network context. --}}
<div id="regionModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="regionModalTitle">
    <div class="modal-content">
        <div class="flex items-center justify-between mb-6">
            <h2 id="regionModalTitle" class="text-xl font-bold">Nova região</h2>
            <button type="button"
                    onclick="closeRegionModal()"
                    class="p-2 rounded-lg hover:bg-surface transition-colors text-text-secondary"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="regionForm" method="POST" action="{{ route('admin.regions.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_method" id="regionFormMethod" value="POST">

            <div>
                <label for="region_network_id" class="label-field">Rede <span class="text-red">*</span></label>
                <select id="region_network_id" name="network_id" required class="input-field">
                    <option value="">Selecione uma rede</option>
                    @foreach ($regionNetworkOptions as $networkOption)
                        <option value="{{ $networkOption->id }}" @selected((string) $selectedRegionNetworkId === (string) $networkOption->id)>
                            {{ $networkOption->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="region_name" class="label-field">Nome da região <span class="text-red">*</span></label>
                <input type="text"
                       id="region_name"
                       name="name"
                       required
                       maxlength="255"
                       class="input-field"
                       placeholder="Ex: Região Sul">
            </div>

            <div>
                <label for="region_description" class="label-field">Descrição</label>
                <textarea id="region_description"
                          name="description"
                          rows="3"
                          maxlength="1000"
                          class="input-field"
                          placeholder="Informe o critério de agrupamento desta região (opcional)"></textarea>
            </div>

            <p id="regionFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: rgba(255, 56, 100, 0.1); color: var(--color-red);"></p>

            <div class="flex justify-end gap-3 pt-4 border-t border-custom">
                <button type="button" onclick="closeRegionModal()" class="btn-outline">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary" id="regionSubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    <span id="regionSubmitBtnText">Salvar região</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('regionModal');
        const form = document.getElementById('regionForm');
        const title = document.getElementById('regionModalTitle');
        const methodInput = document.getElementById('regionFormMethod');
        const submitText = document.getElementById('regionSubmitBtnText');
        const errorBox = document.getElementById('regionFormError');
        const networkSelect = document.getElementById('region_network_id');
        const nameInput = document.getElementById('region_name');
        const descriptionInput = document.getElementById('region_description');

        if (!modal || !form) {
            return;
        }

        let currentRegionId = null;

        window.openRegionModal = function (regionId = null) {
            form.reset();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            currentRegionId = regionId;
            networkSelect.disabled = false;

            if (regionId) {
                title.textContent = 'Editar região';
                submitText.textContent = 'Atualizar';
                methodInput.value = 'PUT';
                form.action = `{{ route('admin.regions.update', ':id') }}`.replace(':id', regionId);

                fetch(`{{ route('admin.regions.show', ':id') }}`.replace(':id', regionId), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Não foi possível carregar os dados da região.');
                        }
                        return response.json();
                    })
                    .then(function (data) {
                        networkSelect.value = data.network_id ?? '';
                        networkSelect.disabled = true;
                        nameInput.value = data.name ?? '';
                        descriptionInput.value = data.description ?? '';
                    })
                    .catch(function (error) {
                        alertError('Erro', error.message);
                    });
            } else {
                title.textContent = 'Nova região';
                submitText.textContent = 'Salvar região';
                methodInput.value = 'POST';
                form.action = '{{ route('admin.regions.store') }}';
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                nameInput.focus();
            }, 100);
        };

        window.closeRegionModal = function () {
            modal.classList.add('hidden');
            document.body.style.overflow = '';
            currentRegionId = null;
            networkSelect.disabled = false;
        };

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            errorBox.classList.add('hidden');

            const submitBtn = document.getElementById('regionSubmitBtn');
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

                        throw new Error(result.data.message || 'Não foi possível salvar a região.');
                    }

                    alertSuccess(currentRegionId ? 'Atualizada!' : 'Cadastrada!', result.data.message);
                    window.closeRegionModal();

                    if (typeof window.afterRegionSaved === 'function') {
                        window.afterRegionSaved(result.data.data);
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
                window.closeRegionModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closeRegionModal();
            }
        });
    })();
</script>
