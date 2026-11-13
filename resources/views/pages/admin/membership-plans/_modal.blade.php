{{-- Membership plan create/edit modal --}}
<div id="membershipPlanModal"
     class="modal-overlay hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="membershipPlanModalTitle">
    <div class="modal-content modal-content-lg">
        <div class="mb-6 flex items-center justify-between">
            <h2 id="membershipPlanModalTitle" class="text-xl font-bold">Novo plano</h2>
            <button type="button" onclick="closeMembershipPlanModal()"
                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface"
                    aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="membershipPlanForm" method="POST" action="{{ route('admin.membership-plans.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="_method" id="membershipPlanFormMethod" value="POST">

            {{-- Identification --}}
            <section class="space-y-4">
                <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Identificação</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="plan_name" class="label-field">Nome do plano <span class="text-red">*</span></label>
                        <input type="text" id="plan_name" name="name" required maxlength="255" class="input-field"
                               placeholder="Ex.: Plano Anual Premium">
                    </div>

                    <div>
                        <label for="plan_price" class="label-field">Valor</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-text-secondary">R$</span>
                            <input type="text" id="plan_price" class="input-field pl-10"
                                   placeholder="0,00" autocomplete="off"
                                   data-currency data-currency-target="plan_price_value">
                            <input type="hidden" id="plan_price_value" name="price">
                        </div>
                    </div>

                    <div>
                        <label for="plan_period" class="label-field">Período <span class="text-red">*</span></label>
                        <select id="plan_period" name="period" required class="input-field">
                            @foreach (\App\Models\MembershipPlan::PERIOD_LABELS as $value => $label)
                                <option value="{{ $value }}">{{ $label }} — {{ \App\Models\MembershipPlan::PERIODS[$value] }} dias</option>
                            @endforeach
                        </select>
                        <p id="planDurationHint" class="mt-1 text-xs text-text-secondary"></p>
                    </div>

                    <div>
                        <label for="plan_weekly_accesses" class="label-field">Acessos por semana <span class="text-red">*</span></label>
                        <input type="number" id="plan_weekly_accesses" name="weekly_accesses" min="1" max="7" step="1"
                               value="7" required class="input-field">
                        <p class="mt-1 text-xs text-text-secondary">Quantidade de acessos permitidos por semana.</p>
                    </div>

                    <div>
                        <label for="plan_daily_checkins" class="label-field">Check-ins diários <span class="text-red">*</span></label>
                        <input type="number" id="plan_daily_checkins" name="daily_checkins" min="1" max="10" step="1"
                               value="1" required class="input-field">
                        <p class="mt-1 text-xs text-text-secondary">Quantidade de check-ins por dia. Inclui as aulas.</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="plan_description" class="label-field">Descrição</label>
                        <textarea id="plan_description" name="description" rows="2" maxlength="2000" class="input-field"
                                  placeholder="Detalhes do plano"></textarea>
                    </div>
                </div>
            </section>

            {{-- Unit access --}}
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Unidades de acesso</h3>
                    <div class="flex gap-2 text-sm">
                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <input type="radio" name="all_units" value="1" class="text-cyan focus:ring-cyan" checked>
                            Todas
                        </label>
                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <input type="radio" name="all_units" value="0" class="text-cyan focus:ring-cyan">
                            Específicas
                        </label>
                    </div>
                </div>

                <div id="planUnitsSpecific" class="hidden max-h-52 overflow-y-auto rounded-xl border border-custom bg-bg-secondary p-3">
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @forelse ($units as $unit)
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-custom bg-surface px-3 py-2 text-sm transition-colors hover:border-cyan">
                                <input type="checkbox" name="unit_ids[]" value="{{ $unit->id }}"
                                       class="rounded border-custom bg-bg-secondary text-cyan focus:ring-cyan">
                                <span>{{ $unit->name }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-text-secondary">Nenhuma unidade disponível para o seu escopo.</p>
                        @endforelse
                    </div>
                </div>
                <p class="text-xs text-text-secondary">
                    Ao escolher “Todas”, o plano considera todas as unidades do escopo em que foi criado.
                </p>
            </section>

            {{-- Modality access --}}
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold uppercase tracking-wider text-text-secondary">Modalidades incluídas</h3>
                    <div class="flex gap-2 text-sm">
                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <input type="radio" name="all_modalities" value="1" class="text-cyan focus:ring-cyan" checked>
                            Todas
                        </label>
                        <label class="inline-flex cursor-pointer items-center gap-2">
                            <input type="radio" name="all_modalities" value="0" class="text-cyan focus:ring-cyan">
                            Específicas
                        </label>
                    </div>
                </div>

                <div id="planModalitiesSpecific" class="hidden max-h-60 overflow-y-auto rounded-xl border border-custom bg-bg-secondary p-3">
                    @forelse ($modalities->groupBy(fn ($modality) => $modality->category?->name ?? 'Sem categoria') as $categoryName => $categoryModalities)
                        <div class="mb-3 last:mb-0">
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-cyan">{{ $categoryName }}</p>
                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                @foreach ($categoryModalities as $modality)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-custom bg-surface px-3 py-2 text-sm transition-colors hover:border-cyan">
                                        <input type="checkbox" name="modality_ids[]" value="{{ $modality->id }}"
                                               class="rounded border-custom bg-bg-secondary text-cyan focus:ring-cyan">
                                        <span>{{ $modality->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-text-secondary">Nenhuma modalidade cadastrada.</p>
                    @endforelse
                </div>
                <p class="text-xs text-text-secondary">
                    Ao escolher “Todas”, o plano considera todas as modalidades visíveis no escopo em que foi criado.
                </p>
            </section>

            <div id="planLevelInfo" class="hidden rounded-lg border border-custom bg-bg-secondary px-4 py-3 text-sm text-text-secondary"></div>

            <p id="membershipPlanFormError"
               class="hidden rounded-lg px-4 py-3 text-sm"
               style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

            <div class="flex justify-end gap-3 border-t border-custom pt-4">
                <button type="button" onclick="closeMembershipPlanModal()" class="btn-outline">Cancelar</button>
                <button type="submit" class="btn-primary" id="membershipPlanSubmitBtn">
                    <i class="fas fa-save mr-2"></i>
                    <span id="membershipPlanSubmitBtnText">Salvar plano</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('membershipPlanModal');
        const form = document.getElementById('membershipPlanForm');
        const title = document.getElementById('membershipPlanModalTitle');
        const methodInput = document.getElementById('membershipPlanFormMethod');
        const submitText = document.getElementById('membershipPlanSubmitBtnText');
        const submitBtn = document.getElementById('membershipPlanSubmitBtn');
        const errorBox = document.getElementById('membershipPlanFormError');
        const nameInput = document.getElementById('plan_name');
        const priceInput = document.getElementById('plan_price');
        const periodSelect = document.getElementById('plan_period');
        const weeklyAccessesInput = document.getElementById('plan_weekly_accesses');
        const dailyCheckinsInput = document.getElementById('plan_daily_checkins');
        const descriptionInput = document.getElementById('plan_description');
        const unitsSpecific = document.getElementById('planUnitsSpecific');
        const modalitiesSpecific = document.getElementById('planModalitiesSpecific');
        const durationHint = document.getElementById('planDurationHint');
        const levelInfo = document.getElementById('planLevelInfo');

        if (!modal || !form) {
            return;
        }

        const durations = @json(\App\Models\MembershipPlan::PERIODS);
        let currentId = null;

        function selectedRadio(name) {
            return form.querySelector(`input[name="${name}"]:checked`)?.value ?? '1';
        }

        function toggleSections() {
            unitsSpecific.classList.toggle('hidden', selectedRadio('all_units') === '1');
            modalitiesSpecific.classList.toggle('hidden', selectedRadio('all_modalities') === '1');
        }

        function updateDuration() {
            const days = durations[periodSelect.value] ?? 0;
            durationHint.textContent = days ? `Este plano concede ${days} dias de acesso.` : '';
        }

        function setCheckboxes(name, ids) {
            const selected = (ids ?? []).map(Number);

            form.querySelectorAll(`input[name="${name}"]`).forEach(function (input) {
                input.checked = selected.includes(Number(input.value));
            });
        }

        form.querySelectorAll('input[name="all_units"], input[name="all_modalities"]').forEach(function (input) {
            input.addEventListener('change', toggleSections);
        });

        periodSelect.addEventListener('change', updateDuration);

        window.openMembershipPlanModal = function (id = null) {
            form.reset();
            errorBox.classList.add('hidden');
            errorBox.textContent = '';
            levelInfo.classList.add('hidden');
            levelInfo.textContent = '';
            currentId = id;

            setCheckboxes('unit_ids[]', []);
            setCheckboxes('modality_ids[]', []);
            form.querySelector('input[name="all_units"][value="1"]').checked = true;
            form.querySelector('input[name="all_modalities"][value="1"]').checked = true;
            toggleSections();
            updateDuration();

            if (id) {
                title.textContent = 'Editar plano';
                submitText.textContent = 'Atualizar';
                methodInput.value = 'PUT';
                form.action = `{{ route('admin.membership-plans.update', ':id') }}`.replace(':id', id);

                fetch(`{{ route('admin.membership-plans.edit-data', ':id') }}`.replace(':id', id), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Não foi possível carregar o plano.');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        nameInput.value = data.name ?? '';
                        window.SmartGymSetCurrency(priceInput, data.price);
                        periodSelect.value = data.period ?? 'monthly';
                        weeklyAccessesInput.value = data.weekly_accesses ?? 7;
                        dailyCheckinsInput.value = data.daily_checkins ?? 1;
                        descriptionInput.value = data.description ?? '';

                        form.querySelector(`input[name="all_units"][value="${data.all_units ? '1' : '0'}"]`).checked = true;
                        form.querySelector(`input[name="all_modalities"][value="${data.all_modalities ? '1' : '0'}"]`).checked = true;

                        setCheckboxes('unit_ids[]', data.unit_ids ?? []);
                        setCheckboxes('modality_ids[]', data.modality_ids ?? []);
                        toggleSections();
                        updateDuration();

                        if (data.created_level_label) {
                            levelInfo.textContent = 'Plano criado no nível: ' + data.created_level_label + '.';
                            levelInfo.classList.remove('hidden');
                        }
                    })
                    .catch(function (error) {
                        alertError('Erro', error.message);
                    });
            } else {
                title.textContent = 'Novo plano';
                submitText.textContent = 'Salvar plano';
                methodInput.value = 'POST';
                form.action = '{{ route('admin.membership-plans.store') }}';
            }

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            setTimeout(function () {
                nameInput.focus();
            }, 100);
        };

        window.closeMembershipPlanModal = function () {
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

                        throw new Error(result.data.message || 'Não foi possível salvar o plano.');
                    }

                    alertSuccess(currentId ? 'Atualizado!' : 'Cadastrado!', result.data.message);
                    window.closeMembershipPlanModal();

                    if (typeof window.afterMembershipPlanSaved === 'function') {
                        window.afterMembershipPlanSaved(result.data.data);
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
                window.closeMembershipPlanModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                window.closeMembershipPlanModal();
            }
        });
    })();
</script>
