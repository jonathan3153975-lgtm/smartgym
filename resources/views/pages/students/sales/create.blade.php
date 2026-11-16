@extends('layouts.app')

@section('title', 'Nova venda - '.$student->name)

@section('content')
<div class="p-4 sm:p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('students.contracts', $student) }}" class="text-sm text-text-secondary transition-colors hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para contratos
            </a>
            <h1 class="mt-1 text-2xl font-bold">Nova venda presencial</h1>
            <p class="mt-1 text-text-secondary">
                {{ $student->name }} &middot; {{ $unit->name }} &middot; monte os itens da venda e confirme os dados.
            </p>
        </div>

        <span class="badge badge-info self-start sm:self-auto">Venda presencial</span>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_320px]">
        <div class="space-y-6">
            <section class="card space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Montagem da venda</p>
                        <h2 class="mt-1 text-lg font-semibold">Tipo de venda</h2>
                        <p id="builderCollapsedHint" class="mt-1 hidden text-xs text-text-secondary">
                            Formulário minimizado. Use “Inserir novo item” para continuar.
                        </p>
                    </div>
                    <button type="button" id="insertItemBtn" class="btn-outline hidden whitespace-nowrap self-start">
                        <i class="fas fa-plus mr-2"></i>
                        Inserir novo item
                    </button>
                </div>

                <div id="saleBuilderFields" class="space-y-4">
                    <div class="space-y-4">
                    <div>
                        <label for="saleKind" class="label-field">1. Tipo de venda <span class="text-red">*</span></label>
                        <select id="saleKind" class="input-field">
                            <option value="plan">Plano</option>
                            <option value="day_pass">Diária</option>
                            <option value="class">Aula avulsa</option>
                        </select>
                    </div>

                    <div>
                        <label for="saleUnit" class="label-field">2. Unidade de referência <span class="text-red">*</span></label>
                        <select id="saleUnit" class="input-field">
                            @foreach ($unitsData as $unitOption)
                                <option value="{{ $unitOption['id'] }}" @selected((int) $unitOption['id'] === (int) $defaultUnitId)>{{ $unitOption['name'] }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-text-secondary">
                            Unidades disponíveis conforme o seu nível de acesso. O aluno permanece vinculado à rede.
                        </p>
                    </div>

                    <div id="saleOptionWrap">
                        <label for="saleOption" class="label-field" id="saleOptionLabel">3. Selecione o plano <span class="text-red">*</span></label>
                        <select id="saleOption" class="input-field"></select>
                    </div>

                    <div>
                        <label for="saleLaunchedAt" class="label-field">Data de lançamento da venda</label>
                        <input type="date" id="saleLaunchedAt" class="input-field" value="{{ now()->toDateString() }}">
                    </div>
                </div>

                <div id="classFilters" class="hidden">
                    <button type="button" id="openClassGradeBtn" class="btn-outline w-full">
                        <i class="fas fa-table-cells-large mr-2"></i>
                        Abrir grade de aulas
                    </button>
                </div>

                <div class="flex flex-col gap-3 border-t border-custom pt-4 sm:flex-row sm:items-center sm:justify-between">
                    <p id="builderHint" class="text-xs text-text-secondary"></p>
                    <button type="button" id="addItemBtn" class="btn-primary whitespace-nowrap">
                        <i class="fas fa-plus mr-2"></i>
                        Adicionar item
                    </button>
                </div>
                </div>
            </section>

            <section class="card space-y-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-semibold">
                            <i class="fas fa-list-check text-cyan"></i>
                            Itens da venda
                        </h2>
                        <p class="mt-1 text-xs text-text-secondary">
                            Datas, limites e horários podem ser ajustados individualmente em cada item.
                        </p>
                    </div>
                    <span id="itemsCountBadge" class="badge badge-secondary self-start sm:self-auto">0 itens</span>
                </div>

                <div id="emptyItems" class="rounded-xl border border-dashed border-custom px-4 py-10 text-center">
                    <i class="fas fa-cart-shopping mb-3 text-3xl text-text-secondary"></i>
                    <p class="font-medium">Nenhum item adicionado</p>
                    <p class="mt-1 text-sm text-text-secondary">
                        Escolha o tipo de venda acima e clique em “Adicionar item”.
                    </p>
                </div>

                <div id="saleItems" class="space-y-4"></div>
            </section>
        </div>

        <aside class="space-y-4 self-start lg:sticky lg:top-20">
            <div class="card space-y-4">
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-calculator text-cyan"></i>
                    Resumo
                </h2>

                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">Itens</span>
                        <span id="summaryCount" class="font-semibold">0</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">Acessos básicos</span>
                        <span id="summaryBasic" class="font-semibold">0 dias · 0/dia</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-text-secondary">Aulas</span>
                        <span id="summaryClasses" class="font-semibold">0 dias · 0/dia</span>
                    </div>
                    <div class="flex items-center justify-between border-t border-custom pt-3 text-base">
                        <span class="font-medium">Total</span>
                        <span id="summaryTotal" class="text-xl font-bold text-cyan">R$ 0,00</span>
                    </div>
                </div>

                <p class="text-xs leading-relaxed text-text-secondary">
                    Os limites acima são pré-calculados pelos planos adicionados e podem ser ajustados item a item.
                </p>
            </div>

            <form id="saleForm" method="POST" action="{{ route('students.sales.store', $student) }}" class="card space-y-4">
                @csrf
                <input type="hidden" name="unit_id" id="saleUnitInput" value="{{ $defaultUnitId }}">
                <input type="hidden" name="launched_at" id="saleLaunchedAtInput" value="{{ now()->toDateString() }}">
                <div id="hiddenItems"></div>

                <p id="saleError"
                   class="hidden rounded-lg px-3 py-2 text-sm"
                   style="background: var(--color-danger-soft); color: var(--color-danger);"></p>

                <div class="flex flex-col gap-2 sm:flex-row">
                    <button type="submit" id="submitSale" class="btn-primary flex-1">
                        <i class="fas fa-check mr-2"></i>
                        Concluir
                    </button>

                    <a href="{{ route('students.contracts', $student) }}" class="btn-outline flex-1 text-center">
                        Cancelar
                    </a>
                </div>

                <p class="text-center text-xs text-text-secondary">
                    A venda gera cobranças pendentes no contrato do aluno.
                </p>
            </form>
        </aside>
    </div>
</div>

{{-- Class grade modal --}}
<div id="classGradeModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="classGradeTitle">
    <div class="modal-content modal-content-lg">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Grade de aulas</p>
                <h2 id="classGradeTitle" class="mt-1 text-xl font-bold">Selecionar aulas avulsas</h2>
                <p class="mt-1 text-sm text-text-secondary">
                    Selecione uma ou mais aulas. O valor de cada aula será somado ao total.
                </p>
            </div>
            <button type="button" id="classGradeClose" class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <label for="classGradeTurma" class="label-field">Turma</label>
                <select id="classGradeTurma" class="input-field">
                    <option value="">Todas as turmas</option>
                </select>
            </div>
            <div>
                <label for="classGradeModality" class="label-field">Modalidade</label>
                <select id="classGradeModality" class="input-field">
                    <option value="">Todas as modalidades</option>
                </select>
            </div>
        </div>

        <div id="classGradeGrid" class="mt-4 max-h-[42vh] overflow-auto rounded-xl border border-custom bg-bg-secondary p-3"></div>

        <div class="mt-3 rounded-xl border border-custom bg-surface p-3">
            <p class="text-sm font-medium">Aulas selecionadas</p>
            <div id="classGradeSelected" class="mt-2 space-y-1 text-sm text-text-secondary">
                Nenhuma aula selecionada.
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-custom pt-4 sm:flex-row sm:items-center sm:justify-between">
            <span id="classGradeCount" class="text-sm text-text-secondary">0 aula(s) selecionada(s)</span>
            <div class="flex justify-end gap-2">
                <button type="button" id="classGradeCancel" class="btn-outline">Cancelar</button>
                <button type="button" id="classGradeSave" class="btn-primary">
                    <i class="fas fa-check mr-2"></i>
                    Adicionar aulas
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Schedule modal --}}
<div id="scheduleModal" class="modal-overlay hidden" role="dialog" aria-modal="true" aria-labelledby="scheduleTitle">
    <div class="modal-content modal-content-lg">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Grade de turmas</p>
                <h2 id="scheduleTitle" class="mt-1 text-xl font-bold">Selecionar horários</h2>
                <p class="mt-1 text-sm text-text-secondary">
                    Selecione até <strong id="scheduleLimit">0</strong> horário(s). O check-in nesses horários será priorizado.
                </p>
            </div>
            <button type="button" id="scheduleClose" class="rounded-lg p-2 text-text-secondary hover:bg-surface" aria-label="Fechar">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div>
                <label for="scheduleUnitFilter" class="label-field">Unidade</label>
                <select id="scheduleUnitFilter" class="input-field">
                    <option value="">Todas as unidades</option>
                </select>
            </div>
            <div>
                <label for="scheduleWeekdayFilter" class="label-field">Dia da semana</label>
                <select id="scheduleWeekdayFilter" class="input-field">
                    <option value="">Todos os dias</option>
                </select>
            </div>
            <div>
                <label for="scheduleModalityFilter" class="label-field">Modalidade</label>
                <select id="scheduleModalityFilter" class="input-field">
                    <option value="">Todas as modalidades</option>
                </select>
            </div>
        </div>

        <div id="scheduleList" class="mt-4 max-h-[45vh] space-y-2 overflow-y-auto rounded-xl border border-custom bg-bg-secondary p-3"></div>

        <div class="mt-4 flex flex-col gap-3 border-t border-custom pt-4 sm:flex-row sm:items-center sm:justify-between">
            <span id="scheduleCount" class="text-sm text-text-secondary">0 horário(s) selecionado(s)</span>
            <div class="flex justify-end gap-2">
                <button type="button" id="scheduleCancel" class="btn-outline">Cancelar</button>
                <button type="button" id="scheduleSave" class="btn-primary">
                    <i class="fas fa-check mr-2"></i>
                    Salvar horários
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const plans = @json($plansData, JSON_UNESCAPED_UNICODE);
        const classes = @json($classesData, JSON_UNESCAPED_UNICODE);
        const units = @json($unitsData, JSON_UNESCAPED_UNICODE);
        const weekdays = @json(\App\Models\SchoolClass::WEEKDAYS, JSON_UNESCAPED_UNICODE);
        const dayPassByUnit = @json($dayPassByUnit);
        const defaultUnitId = @json($defaultUnitId);
        const today = todayString();

        const saleKind = document.getElementById('saleKind');
        const saleUnit = document.getElementById('saleUnit');
        const saleLaunchedAt = document.getElementById('saleLaunchedAt');
        const saleUnitInput = document.getElementById('saleUnitInput');
        const saleLaunchedAtInput = document.getElementById('saleLaunchedAtInput');
        const saleOptionWrap = document.getElementById('saleOptionWrap');
        const saleOptionLabel = document.getElementById('saleOptionLabel');
        const saleOption = document.getElementById('saleOption');
        const classFilters = document.getElementById('classFilters');
        const openClassGradeBtn = document.getElementById('openClassGradeBtn');
        const addItemBtn = document.getElementById('addItemBtn');
        const saleBuilderFields = document.getElementById('saleBuilderFields');
        const insertItemBtn = document.getElementById('insertItemBtn');
        const builderCollapsedHint = document.getElementById('builderCollapsedHint');
        const builderHint = document.getElementById('builderHint');
        const saleItemsEl = document.getElementById('saleItems');
        const emptyItems = document.getElementById('emptyItems');
        const itemsCountBadge = document.getElementById('itemsCountBadge');
        const summaryCount = document.getElementById('summaryCount');
        const summaryBasic = document.getElementById('summaryBasic');
        const summaryClasses = document.getElementById('summaryClasses');
        const summaryTotal = document.getElementById('summaryTotal');
        const saleForm = document.getElementById('saleForm');
        const hiddenItems = document.getElementById('hiddenItems');
        const saleError = document.getElementById('saleError');
        const submitSale = document.getElementById('submitSale');

        const scheduleModal = document.getElementById('scheduleModal');
        const scheduleTitle = document.getElementById('scheduleTitle');
        const scheduleLimit = document.getElementById('scheduleLimit');
        const scheduleUnitFilter = document.getElementById('scheduleUnitFilter');
        const scheduleWeekdayFilter = document.getElementById('scheduleWeekdayFilter');
        const scheduleModalityFilter = document.getElementById('scheduleModalityFilter');
        const scheduleList = document.getElementById('scheduleList');
        const scheduleCount = document.getElementById('scheduleCount');
        const scheduleSave = document.getElementById('scheduleSave');

        const classGradeModal = document.getElementById('classGradeModal');
        const classGradeTurma = document.getElementById('classGradeTurma');
        const classGradeModality = document.getElementById('classGradeModality');
        const classGradeGrid = document.getElementById('classGradeGrid');
        const classGradeSelected = document.getElementById('classGradeSelected');
        const classGradeCount = document.getElementById('classGradeCount');
        const classGradeSave = document.getElementById('classGradeSave');

        let sequence = 0;
        let items = [];
        let selectedGradeSchedules = new Set();
        let scheduleKey = null;
        let lastSaleUnit = saleUnit.value;

        const planById = new Map(plans.map((plan) => [Number(plan.id), plan]));
        const classById = new Map(classes.map((item) => [Number(item.id), item]));

        function todayString() {
            const now = new Date();
            return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
        }

        function addDays(dateString, days) {
            const date = new Date(`${dateString}T12:00:00`);
            date.setDate(date.getDate() + Number(days || 0));
            return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
        }

        function formatDate(dateString) {
            if (! dateString) {
                return '—';
            }

            return new Date(`${dateString}T12:00:00`).toLocaleDateString('pt-BR');
        }

        function money(value) {
            return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
        }

        function moneyInput(value) {
            if (value === '' || value === null || value === undefined) {
                return '';
            }

            return Number(value || 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        function parseMoneyInput(value) {
            const normalized = String(value ?? '')
                .replace(/[^\d,.-]/g, '')
                .replace(/\.(?=\d{3}(\D|$))/g, '')
                .replace(',', '.');

            return Number(normalized || 0);
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[char]));
        }

        function filteredClasses(unitId, weekday, modalityId) {
            return classes.filter((item) => {
                if (unitId && Number(item.unit_id) !== Number(unitId)) {
                    return false;
                }

                if (weekday !== '' && Number(item.weekday) !== Number(weekday)) {
                    return false;
                }

                if (modalityId && Number(item.modality_id) !== Number(modalityId)) {
                    return false;
                }

                return true;
            });
        }

        function fillWeekdaySelect(select) {
            if (select.options.length > 1) {
                return;
            }

            Object.entries(weekdays).forEach(([value, label]) => {
                const option = document.createElement('option');
                option.value = value;
                option.textContent = label;
                select.appendChild(option);
            });
        }

        function fillUnitSelect(select) {
            if (select.options.length > 1) {
                return;
            }

            units.forEach((unit) => {
                const option = document.createElement('option');
                option.value = unit.id;
                option.textContent = unit.name;
                select.appendChild(option);
            });
        }

        function fillModalitySelect(select, allowedIds = null) {
            select.innerHTML = '<option value="">Todas as modalidades</option>';

            const seen = new Set();

            classes.forEach((item) => {
                if (! item.modality_id || seen.has(Number(item.modality_id))) {
                    return;
                }

                if (allowedIds && allowedIds.length && ! allowedIds.includes(Number(item.modality_id))) {
                    return;
                }

                seen.add(Number(item.modality_id));

                const option = document.createElement('option');
                option.value = item.modality_id;
                option.textContent = item.modality_name || 'Modalidade';
                select.appendChild(option);
            });
        }

        function hasDailyPlan() {
            return items.some((item) => item.type === 'plan' && item.period === 'daily');
        }

        function planPrice(plan) {
            const unitId = Number(saleUnit.value);
            const dayPass = dayPassByUnit[unitId] ?? dayPassByUnit[String(unitId)];

            if (plan.period === 'daily' && dayPass !== null && dayPass !== undefined) {
                return Number(dayPass);
            }

            return Number(plan.price || 0);
        }

        function plansForUnit() {
            const unitId = Number(saleUnit.value);

            return plans.filter((plan) => (plan.unit_ids || []).map(Number).includes(unitId));
        }

        function renderOptions() {
            const kind = saleKind.value;

            if (hasDailyPlan()) {
                Array.from(saleKind.options).forEach((option) => {
                    option.disabled = true;
                });

                saleOptionWrap.classList.add('hidden');
                classFilters.classList.add('hidden');
                addItemBtn.classList.add('hidden');
                builderHint.textContent = 'Diária incluída. Itens adicionais serão liberados quando o módulo de produtos e serviços estiver disponível.';

                return;
            }

            Array.from(saleKind.options).forEach((option) => {
                option.disabled = false;
            });

            const currentKind = saleKind.value;
            const needsOption = currentKind === 'plan';

            saleOptionWrap.classList.toggle('hidden', !needsOption);
            classFilters.classList.toggle('hidden', currentKind !== 'class');
            addItemBtn.classList.toggle('hidden', currentKind === 'class');

            if (currentKind === 'plan') {
                saleOptionLabel.innerHTML = '3. Selecione o plano <span class="text-red">*</span>';
                saleOption.innerHTML = '<option value="">Selecione o plano</option>'
                    + plansForUnit().map((plan) => `<option value="${plan.id}">${escapeHtml(plan.name)} — ${money(planPrice(plan))} (${escapeHtml(plan.period_label)}) · ${escapeHtml(plan.type_label)}</option>`).join('');
                builderHint.textContent = 'Planos filtrados pela unidade de referência.';
            } else if (currentKind === 'class') {
                builderHint.textContent = 'Abra a grade, filtre por turma/modalidade e selecione uma ou mais aulas. O valor de cada aula será somado.';
            } else if (currentKind === 'day_pass') {
                builderHint.textContent = 'Ao confirmar o pagamento, o aluno poderá fazer check-in no dia informado.';
            } else {
                builderHint.textContent = 'Cadastre produtos, serviços ou outras cobranças avulsas.';
            }
        }

        function setBuilderCollapsed(collapsed) {
            saleBuilderFields.classList.toggle('hidden', collapsed);
            insertItemBtn.classList.toggle('hidden', ! collapsed);
            builderCollapsedHint.classList.toggle('hidden', ! collapsed);
        }

        function openBuilder() {
            setBuilderCollapsed(false);
            saleOption.value = '';
            renderOptions();
            saleKind.focus();
        }

        function newItemBase(type) {
            const timestamp = new Date().getTime().toString(36);
            sequence += 1;

            return {
                key: `${timestamp}-${sequence}`,
                type,
                launchedAt: today,
                startsAt: today,
                endsAt: null,
                amount: 0,
                description: '',
                weeklyAccesses: 0,
                dailyCheckins: 1,
                checkinAdvance: 20,
                planId: null,
                classId: null,
                bookedFor: null,
                slotIds: [],
                flexibleCredit: false,
                autoRenew: true,
                collapsed: false,
                durationDays: 30,
            };
        }

        function addItem() {
            const kind = saleKind.value;

            if (kind === 'plan') {
                const planId = Number(saleOption.value);

                if (! planId || ! planById.has(planId)) {
                    showError('Selecione um plano para adicionar.');
                    return;
                }

                const plan = planById.get(planId);

                if (plan.period === 'daily' && items.some((item) => item.type !== 'other')) {
                    showError('Remova os demais itens antes de adicionar uma diária. A diária só pode ser combinada com produtos ou serviços.');
                    return;
                }

                if (items.some((item) => item.type === 'plan' && Number(item.planId) === planId)) {
                    showError('Este plano já foi adicionado. Ajuste os limites no item existente.');
                    return;
                }

                const item = newItemBase('plan');
                item.planId = planId;
                item.planName = plan.name;
                item.planType = plan.type;
                item.planTypeLabel = plan.type_label;
                item.period = plan.period;
                item.includesClasses = plan.includes_classes;
                item.amount = planPrice(plan);
                item.durationDays = Number(plan.duration_days);
                item.startsAt = today;
                item.endsAt = addDays(today, plan.duration_days);
                item.weeklyAccesses = Number(plan.weekly_accesses);
                item.dailyCheckins = Number(plan.daily_checkins);
                item.checkinAdvance = Number(plan.checkin_advance_minutes);
                item.modalityIds = plan.modality_ids;
                item.unitIds = [Number(saleUnit.value)];
                items.push(item);
            } else if (kind === 'day_pass') {
                const item = newItemBase('day_pass');
                item.startsAt = today;
                item.endsAt = today;
                item.amount = '';
                item.dailyCheckins = 1;
                items.push(item);
            } else if (kind === 'class') {
                const classId = Number(saleOption.value);

                if (! classId || ! classById.has(classId)) {
                    showError('Selecione uma turma para adicionar.');
                    return;
                }

                const schoolClass = classById.get(classId);

                if (items.some((item) => item.type === 'class' && Number(item.classId) === classId)) {
                    showError('Esta turma já foi adicionada. Ajuste a data no item existente.');
                    return;
                }

                const item = newItemBase('class');
                item.classId = classId;
                item.className = schoolClass.name;
                item.unitName = schoolClass.unit_name;
                item.weekdayLabel = schoolClass.weekday_label;
                item.startTime = schoolClass.start_time;
                item.bookedFor = schoolClass.next_date;
                item.amount = schoolClass.drop_in_price ?? '';
                item.dailyCheckins = 1;
                items.push(item);
            } else {
                const item = newItemBase('other');
                item.amount = '';
                items.push(item);
            }

            showError(null);
            items.forEach((existing, index) => {
                existing.collapsed = index !== items.length - 1;
            });
            renderItems();
            summary();
            renderOptions();
            setBuilderCollapsed(true);
        }

        function findItem(key) {
            return items.find((item) => item.key === key);
        }

        function itemTitle(item) {
            if (item.type === 'plan') {
                return item.planName;
            }

            if (item.type === 'class') {
                return item.className;
            }

            if (item.type === 'day_pass') {
                return 'Diária';
            }

            return item.description || 'Produto / serviço';
        }

        function typeLabel(item) {
            return {
                plan: 'Plano',
                day_pass: 'Diária',
                class: 'Aula avulsa',
                other: 'Produto / serviço',
            }[item.type] ?? '—';
        }

        function slotSummary(item) {
            if (! item.slotIds.length) {
                return '';
            }

            return item.slotIds.map((id) => {
                const schoolClass = classById.get(Number(id));

                if (! schoolClass) {
                    return '';
                }

                return `<span class="badge badge-secondary">${escapeHtml(schoolClass.weekday_label)} ${escapeHtml(schoolClass.start_time)} · ${escapeHtml(schoolClass.name)}</span>`;
            }).filter(Boolean).join(' ');
        }

        function dateField(label, field, value, min = null) {
            return `
                <div>
                    <label class="label-field">${label}</label>
                    <input type="date" data-field="${field}" value="${escapeHtml(value ?? '')}" ${min ? `min="${min}"` : ''} class="input-field">
                </div>
            `;
        }

        function itemSummary(item) {
            const bits = [];

            if (item.type === 'plan') {
                bits.push(`${item.weeklyAccesses} dia(s)/semana`);
                bits.push(`${item.dailyCheckins}/dia`);

                if (item.period === 'daily') {
                    bits.push('diária');
                } else {
                    bits.push(`${formatDate(item.startsAt)} a ${formatDate(item.endsAt)}`);
                }

                if (item.autoRenew) {
                    bits.push('renovação automática');
                }
            } else if (item.type === 'day_pass') {
                bits.push(`válida em ${formatDate(item.startsAt)}`);
                bits.push(`${item.dailyCheckins} acesso(s)`);
            } else if (item.type === 'class') {
                bits.push(`${item.weekdayLabel ?? ''} ${item.startTime ?? ''}`.trim());
                bits.push(formatDate(item.bookedFor));

                if ((item.slotIds || []).length) {
                    bits.push(`${item.slotIds.length} horário(s)`);
                }
            } else {
                bits.push(item.description || 'sem descrição');
            }

            bits.push(money(item.amount));

            return bits.filter(Boolean).join(' · ');
        }

        function renderItems() {
            emptyItems.classList.toggle('hidden', items.length > 0);

            const orderedItems = [...items].reverse();

            saleItemsEl.innerHTML = orderedItems.map((item) => {
                const plan = item.type === 'plan' ? planById.get(Number(item.planId)) : null;
                const details = [];

                if (item.type === 'plan') {
                    details.push(`
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="badge badge-info">${escapeHtml(item.planTypeLabel ?? plan?.type_label ?? 'Plano')}</span>
                            <span class="text-xs text-text-secondary">Valor do plano (fixo): ${money(item.amount)}</span>
                        </div>
                    `);
                }

                if (item.type === 'class') {
                    details.push(`
                        <p class="text-xs text-text-secondary">
                            ${escapeHtml(item.unitName ?? '')} &middot; ${escapeHtml(item.weekdayLabel ?? '')} &middot; ${escapeHtml(item.startTime ?? '')}
                        </p>
                    `);
                }

                const headerActions = `
                    <div class="flex items-center gap-1">
                        <button type="button" data-toggle-item="${item.key}" class="p-2 text-text-secondary transition-colors hover:text-cyan" title="${item.collapsed ? 'Expandir item' : 'Minimizar item'}">
                            <i class="fas ${item.collapsed ? 'fa-chevron-down' : 'fa-chevron-up'}"></i>
                        </button>
                        <button type="button" data-remove="${item.key}" class="p-2 text-text-secondary transition-colors hover:text-red" title="Remover item">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;

                if (item.collapsed) {
                    return `
                        <article data-item-key="${item.key}" class="rounded-xl border border-custom bg-surface p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="badge badge-info">${typeLabel(item)}</span>
                                        ${item.type === 'plan' && item.includesClasses ? '<span class="badge badge-success">Inclui aulas</span>' : ''}
                                        ${item.type === 'plan' && item.autoRenew ? '<span class="badge badge-info">Renovação automática</span>' : ''}
                                    </div>
                                    <p class="mt-2 font-semibold">${escapeHtml(itemTitle(item))}</p>
                                    <p class="mt-1 text-xs text-text-secondary">${escapeHtml(itemSummary(item))}</p>
                                </div>
                                ${headerActions}
                            </div>
                        </article>
                    `;
                }

                return `
                    <article data-item-key="${item.key}" class="rounded-xl border border-custom bg-surface p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="badge badge-info">${typeLabel(item)}</span>
                                    ${item.type === 'plan' && item.includesClasses ? '<span class="badge badge-success">Inclui aulas</span>' : ''}
                                </div>
                                <p class="mt-2 font-semibold">${escapeHtml(itemTitle(item))}</p>
                                ${details.join('')}
                            </div>
                            ${headerActions}
                        </div>

                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 ${item.type === 'plan' ? 'lg:grid-cols-5' : 'lg:grid-cols-3'}">
                            ${dateField(item.type === 'day_pass' ? 'Dia de validade' : (item.type === 'class' ? 'Data da aula' : 'Data inicial'), 'starts_at', item.type === 'class' ? item.bookedFor : item.startsAt, today)}
                            ${item.type !== 'day_pass' && item.type !== 'class' ? dateField('Data final', 'ends_at', item.endsAt, item.startsAt) : ''}

                            ${item.type === 'plan' ? `
                                <div>
                                    <label class="label-field">Dias/semana</label>
                                    <input type="number" min="1" max="7" step="1" data-field="weekly_accesses" value="${escapeHtml(item.weeklyAccesses)}" class="input-field">
                                </div>
                            ` : ''}

                            ${item.type === 'plan' || item.type === 'day_pass' ? `
                                <div>
                                    <label class="label-field">${item.type === 'plan' && item.planType === 'classes' ? 'Aulas/dia' : 'Acessos/dia'}</label>
                                    <input type="number" min="1" max="10" step="1" data-field="daily_checkins" value="${escapeHtml(item.dailyCheckins)}" class="input-field">
                                </div>
                            ` : ''}

                            ${item.type === 'plan' ? (item.period === 'daily' ? `
                                <div>
                                    <label class="label-field">Valor da diária <span class="text-red">*</span></label>
                                    <input type="text" inputmode="decimal" data-currency data-field="amount" value="${moneyInput(item.amount)}" class="input-field">
                                </div>
                            ` : `
                                <div>
                                    <label class="label-field">Valor</label>
                                    <input type="text" value="${money(item.amount)}" disabled class="input-field">
                                </div>
                            `) : `
                                <div>
                                    <label class="label-field">Valor <span class="text-red">*</span></label>
                                    <input type="text" inputmode="decimal" data-currency data-field="amount" value="${moneyInput(item.amount)}" class="input-field">
                                </div>
                            `}

                            ${(item.type === 'plan' && item.includesClasses) || item.type === 'class' ? `
                                <div>
                                    <label class="label-field">Antecedência de check-in (min)</label>
                                    <input type="number" min="0" max="120" step="1" data-field="checkin_advance_minutes" value="${escapeHtml(item.checkinAdvance)}" class="input-field">
                                </div>
                            ` : ''}

                            ${item.type === 'class' ? `
                                <div class="sm:col-span-2 lg:col-span-full">
                                    <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-custom bg-bg-secondary px-3 py-2 text-sm">
                                        <input type="checkbox" data-field="flexible_credit" ${item.flexibleCredit ? 'checked' : ''} class="mt-0.5 rounded border-custom bg-bg-secondary text-cyan focus:ring-cyan">
                                        <span>
                                            <span class="block font-medium">Permitir crédito para reservar em qualquer dia/horário com vagas</span>
                                            <span class="block text-xs text-text-secondary">Se não houver check-in no dia comprado, a aula fica como crédito para outra reserva desta turma.</span>
                                        </span>
                                    </label>
                                </div>
                            ` : ''}

                            ${item.type === 'other' ? `
                                <div class="sm:col-span-2 lg:col-span-full">
                                    <label class="label-field">Descrição <span class="text-red">*</span></label>
                                    <input type="text" data-field="description" maxlength="255" value="${escapeHtml(item.description)}" class="input-field" placeholder="Ex.: avaliação física, camiseta, suplemento...">
                                </div>
                            ` : ''}

                            ${item.type === 'day_pass' ? `
                                <div class="sm:col-span-2 lg:col-span-full">
                                    <label class="label-field">Descrição</label>
                                    <input type="text" data-field="description" maxlength="255" value="${escapeHtml(item.description)}" class="input-field" placeholder="Opcional">
                                </div>
                            ` : ''}
                        </div>

                        ${item.type === 'plan' ? `
                            <div class="mt-4">
                                <label class="flex cursor-pointer items-start gap-2 rounded-lg border border-custom bg-bg-secondary px-3 py-2 text-sm">
                                    <input type="checkbox" data-field="auto_renew" ${item.autoRenew ? 'checked' : ''} class="mt-0.5 rounded border-custom bg-bg-secondary text-cyan focus:ring-cyan">
                                    <span>
                                        <span class="block font-medium">Renovação automática</span>
                                        <span class="block text-xs text-text-secondary">Ao final do plano é gerada uma nova cobrança, mantendo o plano ativo até o cancelamento.</span>
                                    </span>
                                </label>
                            </div>
                        ` : ''}

                        ${item.type === 'plan' && item.includesClasses ? `
                            <div class="mt-4 rounded-lg border border-custom bg-bg-secondary p-3">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm font-medium">Horários preferenciais</p>
                                        <p class="text-xs text-text-secondary">
                                            ${item.slotIds.length} de ${item.weeklyAccesses} horário(s) selecionado(s). O check-in nesses horários é priorizado.
                                        </p>
                                    </div>
                                    <button type="button" data-schedule="${item.key}" class="btn-outline whitespace-nowrap">
                                        <i class="fas fa-calendar-days mr-2"></i>
                                        Selecionar horários
                                    </button>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    ${slotSummary(item) || '<span class="text-xs text-text-secondary">Nenhum horário selecionado.</span>'}
                                </div>
                            </div>
                        ` : ''}
                    </article>
                `;
            }).join('');

            if (window.SmartGymInitCurrencyMasks) {
                window.SmartGymInitCurrencyMasks(saleItemsEl);
            }

            itemsCountBadge.textContent = `${items.length} item(ns)`;
        }

        function summary() {
            const total = items.reduce((sum, item) => sum + Number(item.amount || 0), 0);
            const planItems = items.filter((item) => item.type === 'plan');
            const basicItems = planItems.filter((item) => item.planType === 'basic' || item.planType === 'mixed');
            const classPlanItems = planItems.filter((item) => item.planType === 'classes' || item.planType === 'mixed');
            const dayPassItems = items.filter((item) => item.type === 'day_pass');
            const classItems = items.filter((item) => item.type === 'class');
            const basicDays = basicItems.reduce((sum, item) => sum + Number(item.weeklyAccesses || 0), 0) + dayPassItems.length;
            const basicDaily = basicItems.reduce((sum, item) => sum + Number(item.dailyCheckins || 0), 0)
                + dayPassItems.reduce((sum, item) => sum + Number(item.dailyCheckins || 0), 0);
            const classDays = classPlanItems.reduce((sum, item) => sum + Number(item.weeklyAccesses || 0), 0) + classItems.length;
            const classDaily = classPlanItems.reduce((sum, item) => sum + Number(item.dailyCheckins || 0), 0) + classItems.length;

            summaryCount.textContent = String(items.length);
            summaryBasic.textContent = `${basicDays} dias · ${basicDaily}/dia`;
            summaryClasses.textContent = `${classDays} dias · ${classDaily}/dia`;
            summaryTotal.textContent = money(total);
        }

        function syncItem(event) {
            const target = event.target.closest('[data-field]');

            if (! target) {
                return;
            }

            const card = target.closest('[data-item-key]');

            if (! card) {
                return;
            }

            const item = findItem(card.dataset.itemKey);

            if (! item) {
                return;
            }

            const field = target.dataset.field;
            let value = target.value;

            if (field === 'flexible_credit') {
                item.flexibleCredit = target.checked;
                return;
            }

            if (field === 'auto_renew') {
                item.autoRenew = target.checked;
                return;
            }

            if (field === 'amount') {
                item.amount = parseMoneyInput(target.value);

                if (event.type === 'change') {
                    target.value = moneyInput(item.amount);
                }

                summary();
                return;
            }

            if (['amount', 'weekly_accesses', 'daily_checkins', 'checkin_advance_minutes'].includes(field)) {
                value = value === '' ? '' : Number(value);
            }

            if (field === 'launched_at') {
                item.launchedAt = value;
            } else if (field === 'starts_at') {
                if (item.type === 'class') {
                    item.bookedFor = value;
                } else {
                    item.startsAt = value;

                    if (item.type === 'plan') {
                        item.endsAt = addDays(value, item.durationDays);
                        const endInput = card.querySelector('[data-field="ends_at"]');

                        if (endInput) {
                            endInput.value = item.endsAt;
                        }
                    }

                    if (item.type === 'day_pass') {
                        item.endsAt = value;
                    }
                }
            } else if (field === 'ends_at') {
                item.endsAt = value;
            } else if (field === 'weekly_accesses') {
                item.weeklyAccesses = value;

                if (item.slotIds.length > Number(value || 0)) {
                    item.slotIds = item.slotIds.slice(0, Number(value || 0));
                    renderItems();
                }
            } else if (field === 'daily_checkins') {
                item.dailyCheckins = value;
            } else if (field === 'checkin_advance_minutes') {
                item.checkinAdvance = value;
            } else if (field === 'amount') {
                item.amount = value;
            } else if (field === 'description') {
                item.description = value;
            }

            summary();
        }

        function removeItem(key) {
            const removed = findItem(key);

            if (removed?.type === 'class' && removed.scheduleId) {
                selectedGradeSchedules.delete(Number(removed.scheduleId));
            }

            items = items.filter((item) => item.key !== key);
            renderItems();
            summary();
            renderOptions();
        }

        function showError(message) {
            if (! message) {
                saleError.classList.add('hidden');
                saleError.textContent = '';
                return;
            }

            alertError('Venda', message);
        }

        function updateHiddenInputs() {
            hiddenItems.innerHTML = items.map((item, index) => {
                const fields = [
                    ['type', item.type],
                    ['plan_id', item.type === 'plan' ? item.planId : ''],
                    ['class_id', item.type === 'class' ? item.classId : ''],
                    ['class_schedule_id', item.type === 'class' ? item.scheduleId : ''],
                    ['flexible_credit', item.type === 'class' && item.flexibleCredit ? 1 : 0],
                    ['description', item.type === 'other' || item.type === 'day_pass' ? item.description : ''],
                    ['amount', item.type === 'plan' ? item.amount : item.amount],
                    ['starts_at', item.type === 'day_pass' || item.type === 'plan' || item.type === 'other' ? item.startsAt : ''],
                    ['ends_at', item.type === 'plan' || item.type === 'other' ? item.endsAt : ''],
                    ['booked_for', item.type === 'class' ? item.bookedFor : ''],
                    ['weekly_accesses', item.type === 'plan' ? item.weeklyAccesses : ''],
                    ['daily_checkins', item.type === 'plan' || item.type === 'day_pass' ? item.dailyCheckins : ''],
                    ['checkin_advance_minutes', item.type === 'plan' || item.type === 'class' ? item.checkinAdvance : ''],
                    ['auto_renew', item.type === 'plan' && item.autoRenew ? 1 : 0],
                ];

                let html = fields.map(([field, value]) => `<input type="hidden" name="items[${index}][${field}]" value="${escapeHtml(value)}">`).join('');

                (item.slotIds || []).forEach((slotId, slotIndex) => {
                    html += `<input type="hidden" name="items[${index}][slot_ids][${slotIndex}]" value="${escapeHtml(slotId)}">`;
                });

                return html;
            }).join('');
        }

        function validateItems() {
            if (items.length === 0) {
                return 'Adicione ao menos um item à venda.';
            }

            for (const item of items) {
                if (item.type === 'plan') {
                    if (! item.planId) {
                        return 'Selecione o plano de todos os itens.';
                    }

                    if (! item.startsAt || ! item.endsAt) {
                        return 'Informe as datas inicial e final do plano.';
                    }

                    if (Number(item.weeklyAccesses) < 1 || Number(item.dailyCheckins) < 1) {
                        return 'Informe os limites de acesso do plano.';
                    }

                    if (item.period === 'daily' && Number(item.amount) <= 0) {
                        return 'Informe o valor da diária.';
                    }
                }

                if (item.type === 'day_pass') {
                    if (! item.startsAt) {
                        return 'Informe o dia de validade da diária.';
                    }

                    if (Number(item.amount) <= 0) {
                        return 'Informe o valor da diária.';
                    }
                }

                if (item.type === 'class') {
                    if (! item.bookedFor) {
                        return 'Informe a data da aula avulsa.';
                    }

                    if (Number(item.amount) <= 0) {
                        return 'Informe o valor da aula avulsa.';
                    }
                }

                if (item.type === 'other') {
                    if (! item.description.trim()) {
                        return 'Informe a descrição do produto ou serviço.';
                    }

                    if (Number(item.amount) <= 0) {
                        return 'Informe o valor do produto ou serviço.';
                    }
                }
            }

            return null;
        }

        // Builder events
        saleKind.addEventListener('change', renderOptions);
        saleUnit.addEventListener('change', function () {
            if (items.length > 0 && ! confirm('Alterar a unidade de referência remove os itens já adicionados. Continuar?')) {
                saleUnit.value = lastSaleUnit;
                return;
            }

            lastSaleUnit = saleUnit.value;
            items = [];
            selectedGradeSchedules.clear();
            saleUnitInput.value = saleUnit.value;
            renderItems();
            summary();
            renderOptions();
        });
        saleLaunchedAt.addEventListener('change', function () {
            saleLaunchedAtInput.value = saleLaunchedAt.value;
        });
        addItemBtn.addEventListener('click', addItem);
        insertItemBtn.addEventListener('click', openBuilder);

        saleItemsEl.addEventListener('input', syncItem);
        saleItemsEl.addEventListener('change', syncItem);

        saleItemsEl.addEventListener('click', function (event) {
            const toggle = event.target.closest('[data-toggle-item]');

            if (toggle) {
                const item = findItem(toggle.dataset.toggleItem);

                if (item) {
                    item.collapsed = ! item.collapsed;
                    renderItems();
                }

                return;
            }

            const remove = event.target.closest('[data-remove]');

            if (remove) {
                if (confirm('Remover este item da venda?')) {
                    removeItem(remove.dataset.remove);
                }
                return;
            }

            const schedule = event.target.closest('[data-schedule]');

            if (schedule) {
                openSchedule(schedule.dataset.schedule);
            }
        });

        // Schedule modal
        function openSchedule(key) {
            const item = findItem(key);

            if (! item || item.type !== 'plan' || ! item.includesClasses) {
                return;
            }

            scheduleKey = key;
            scheduleTitle.textContent = `Horários — ${item.planName}`;
            scheduleLimit.textContent = String(item.weeklyAccesses);

            fillUnitSelect(scheduleUnitFilter);
            fillWeekdaySelect(scheduleWeekdayFilter);
            fillModalitySelect(scheduleModalityFilter, item.modalityIds || []);

            scheduleUnitFilter.value = '';
            scheduleWeekdayFilter.value = '';
            scheduleModalityFilter.value = '';

            renderScheduleList();
            scheduleModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function scheduleClasses() {
            const item = findItem(scheduleKey);

            if (! item) {
                return [];
            }

            const allowedUnits = (item.unitIds || []).map(Number);
            const allowedModalities = (item.modalityIds || []).map(Number);

            return filteredClasses(
                scheduleUnitFilter.value,
                scheduleWeekdayFilter.value,
                scheduleModalityFilter.value
            ).filter((schoolClass) => {
                if (allowedUnits.length && ! allowedUnits.includes(Number(schoolClass.unit_id))) {
                    return false;
                }

                if (allowedModalities.length && schoolClass.modality_id && ! allowedModalities.includes(Number(schoolClass.modality_id))) {
                    return false;
                }

                return true;
            });
        }

        function renderScheduleList() {
            const item = findItem(scheduleKey);

            if (! item) {
                return;
            }

            const list = scheduleClasses();
            const selected = new Set(item.slotIds.map(Number));
            const limit = Number(item.weeklyAccesses || 0);
            const atLimit = selected.size >= limit;

            scheduleCount.textContent = `${selected.size} de ${limit} horário(s) selecionado(s)`;

            scheduleList.innerHTML = list.length === 0
                ? '<p class="py-6 text-center text-sm text-text-secondary">Nenhuma aula disponível para os filtros selecionados.</p>'
                : list.map((schoolClass) => {
                    const isSelected = selected.has(Number(schoolClass.id));
                    const disabled = ! isSelected && atLimit;

                    return `
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-custom bg-surface px-3 py-2 text-sm transition-colors ${disabled ? 'opacity-50' : 'hover:border-cyan'}">
                            <input type="checkbox" data-schedule-class="${schoolClass.id}" ${isSelected ? 'checked' : ''} ${disabled ? 'disabled' : ''} class="mt-0.5 rounded border-custom bg-bg-secondary text-cyan focus:ring-cyan">
                            <span class="min-w-0 flex-1">
                                <span class="block font-medium">${escapeHtml(schoolClass.name)}</span>
                                <span class="block text-xs text-text-secondary">
                                    ${escapeHtml(schoolClass.unit_name)} &middot; ${escapeHtml(schoolClass.weekday_label)} ${escapeHtml(schoolClass.start_time)}
                                    ${schoolClass.modality_name ? '&middot; ' + escapeHtml(schoolClass.modality_name) : ''}
                                    &middot; ${schoolClass.available} vaga(s)
                                </span>
                            </span>
                        </label>
                    `;
                }).join('');
        }

        scheduleList.addEventListener('change', function (event) {
            const checkbox = event.target.closest('[data-schedule-class]');

            if (! checkbox) {
                return;
            }

            const item = findItem(scheduleKey);

            if (! item) {
                return;
            }

            const classId = Number(checkbox.dataset.scheduleClass);

            if (checkbox.checked) {
                if (item.slotIds.length >= Number(item.weeklyAccesses || 0)) {
                    checkbox.checked = false;
                    return;
                }

                if (! item.slotIds.includes(classId)) {
                    item.slotIds.push(classId);
                }
            } else {
                item.slotIds = item.slotIds.filter((id) => Number(id) !== classId);
            }

            renderScheduleList();
        });

        [scheduleUnitFilter, scheduleWeekdayFilter, scheduleModalityFilter].forEach((select) => {
            select.addEventListener('change', renderScheduleList);
        });

        function closeSchedule() {
            scheduleModal.classList.add('hidden');
            document.body.style.overflow = '';
            scheduleKey = null;
        }

        scheduleSave.addEventListener('click', function () {
            renderItems();
            summary();
            closeSchedule();
        });

        document.getElementById('scheduleClose').addEventListener('click', closeSchedule);
        document.getElementById('scheduleCancel').addEventListener('click', closeSchedule);
        scheduleModal.addEventListener('click', function (event) {
            if (event.target === scheduleModal) {
                closeSchedule();
            }
        });

        // Class grade modal
        function unitClasses() {
            return classes.filter((schoolClass) => Number(schoolClass.unit_id) === Number(saleUnit.value));
        }

        function populateGradeFilters() {
            const list = unitClasses();
            const seenClasses = new Set();

            classGradeTurma.innerHTML = '<option value="">Todas as turmas</option>';

            list.forEach((schoolClass) => {
                if (seenClasses.has(Number(schoolClass.class_id))) {
                    return;
                }

                seenClasses.add(Number(schoolClass.class_id));

                const option = document.createElement('option');
                option.value = schoolClass.class_id;
                option.textContent = schoolClass.name;
                classGradeTurma.appendChild(option);
            });

            const seenModalities = new Set();
            classGradeModality.innerHTML = '<option value="">Todas as modalidades</option>';

            list.forEach((schoolClass) => {
                if (! schoolClass.modality_id || seenModalities.has(Number(schoolClass.modality_id))) {
                    return;
                }

                seenModalities.add(Number(schoolClass.modality_id));

                const option = document.createElement('option');
                option.value = schoolClass.modality_id;
                option.textContent = schoolClass.modality_name || 'Modalidade';
                classGradeModality.appendChild(option);
            });
        }

        function gradeSchedules() {
            const turma = classGradeTurma.value;
            const modality = classGradeModality.value;

            return unitClasses().filter((schoolClass) => {
                if (Number(schoolClass.available) <= 0) {
                    return false;
                }

                if (turma && Number(schoolClass.class_id) !== Number(turma)) {
                    return false;
                }

                if (modality && Number(schoolClass.modality_id) !== Number(modality)) {
                    return false;
                }

                return true;
            });
        }

        function scheduleButton(schoolClass) {
            const selected = selectedGradeSchedules.has(Number(schoolClass.id));

            return `
                <button type="button" data-grade-schedule="${schoolClass.id}"
                        class="mb-1 mr-1 inline-flex flex-col rounded-lg border px-2 py-1 text-left text-xs transition-colors ${selected ? 'border-cyan bg-cyan/10 text-cyan' : 'border-custom bg-surface text-text-secondary hover:border-cyan'}">
                    <span class="font-semibold">${escapeHtml(schoolClass.start_time)}${schoolClass.end_time ? ' / ' + escapeHtml(schoolClass.end_time) : ''} - ${schoolClass.drop_in_price !== null ? money(schoolClass.drop_in_price) : 'Sem valor'}</span>
                    <span>${escapeHtml(schoolClass.unit_name)} (${schoolClass.available} vaga(s))</span>
                </button>
            `;
        }

        function renderGradeGrid() {
            const list = gradeSchedules();
            const showClassColumn = ! classGradeTurma.value;
            const grouped = {};

            list.forEach((schoolClass) => {
                const key = schoolClass.class_id;

                grouped[key] = grouped[key] || {
                    name: schoolClass.name,
                    days: {},
                };

                grouped[key].days[schoolClass.weekday] = grouped[key].days[schoolClass.weekday] || [];
                grouped[key].days[schoolClass.weekday].push(schoolClass);
            });

            let rows = '';

            Object.entries(grouped).forEach(([classId, group]) => {
                const dayEntries = Object.entries(group.days).sort(([a], [b]) => Number(a) - Number(b));

                dayEntries.forEach(([day, daySchedules], index) => {
                    daySchedules.sort((a, b) => a.start_time.localeCompare(b.start_time));

                    rows += `
                        <tr class="border-t border-custom">
                            ${showClassColumn && index === 0 ? `<td rowspan="${dayEntries.length}" class="py-2 pr-3 align-top font-medium">${escapeHtml(group.name)}</td>` : ''}
                            <td class="py-2 pr-3 align-top whitespace-nowrap text-xs font-semibold uppercase tracking-wide text-cyan">${escapeHtml(weekdays[day] || '—')}</td>
                            <td class="py-2 align-top">${daySchedules.map(scheduleButton).join('')}</td>
                        </tr>
                    `;
                });
            });

            classGradeGrid.innerHTML = rows === ''
                ? '<p class="py-6 text-center text-sm text-text-secondary">Nenhuma aula disponível para os filtros selecionados.</p>'
                : `
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wider text-text-secondary">
                                ${showClassColumn ? '<th class="pb-2 pr-3">Turma</th>' : ''}
                                <th class="pb-2 pr-3">Dia</th>
                                <th class="pb-2">Horários</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                `;

            renderGradeSelected();
            classGradeCount.textContent = `${selectedGradeSchedules.size} aula(s) selecionada(s)`;
        }

        function renderGradeSelected() {
            if (selectedGradeSchedules.size === 0) {
                classGradeSelected.innerHTML = 'Nenhuma aula selecionada.';
                return;
            }

            classGradeSelected.innerHTML = Array.from(selectedGradeSchedules).map((id) => {
                const schoolClass = classById.get(Number(id));

                if (! schoolClass) {
                    return '';
                }

                return `
                    <div class="flex items-center justify-between gap-2 rounded-lg border border-custom bg-bg-secondary px-2 py-1">
                        <span>${escapeHtml(schoolClass.name)} · ${escapeHtml(schoolClass.weekday_label)} ${escapeHtml(schoolClass.start_time)} · ${escapeHtml(schoolClass.unit_name)}</span>
                        <button type="button" data-remove-grade="${id}" class="p-1 text-red" title="Remover">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                `;
            }).join('');
        }

        function openClassGrade() {
            populateGradeFilters();
            classGradeTurma.value = '';
            classGradeModality.value = '';
            renderGradeGrid();
            classGradeModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeClassGrade() {
            classGradeModal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        classGradeGrid.addEventListener('click', function (event) {
            const button = event.target.closest('[data-grade-schedule]');

            if (! button) {
                return;
            }

            const id = Number(button.dataset.gradeSchedule);

            if (selectedGradeSchedules.has(id)) {
                selectedGradeSchedules.delete(id);
            } else {
                selectedGradeSchedules.add(id);
            }

            renderGradeGrid();
        });

        classGradeSelected.addEventListener('click', function (event) {
            const button = event.target.closest('[data-remove-grade]');

            if (! button) {
                return;
            }

            selectedGradeSchedules.delete(Number(button.dataset.removeGrade));
            renderGradeGrid();
        });

        classGradeTurma.addEventListener('change', function () {
            classGradeModality.value = '';
            renderGradeGrid();
        });

        classGradeModality.addEventListener('change', renderGradeGrid);

        openClassGradeBtn.addEventListener('click', openClassGrade);
        document.getElementById('classGradeClose').addEventListener('click', closeClassGrade);
        document.getElementById('classGradeCancel').addEventListener('click', closeClassGrade);
        classGradeModal.addEventListener('click', function (event) {
            if (event.target === classGradeModal) {
                closeClassGrade();
            }
        });

        classGradeSave.addEventListener('click', function () {
            if (hasDailyPlan()) {
                alertError('Venda com diária', 'Uma venda com diária não pode incluir aulas avulsas.');
                return;
            }

            const beforeCount = items.length;

            Array.from(selectedGradeSchedules).forEach((scheduleId) => {
                const schoolClass = classById.get(Number(scheduleId));

                if (! schoolClass) {
                    return;
                }

                if (items.some((item) => item.type === 'class' && Number(item.scheduleId) === Number(scheduleId))) {
                    return;
                }

                const item = newItemBase('class');
                item.classId = schoolClass.class_id;
                item.scheduleId = schoolClass.id;
                item.className = schoolClass.name;
                item.unitName = schoolClass.unit_name;
                item.weekdayLabel = schoolClass.weekday_label;
                item.startTime = schoolClass.start_time;
                item.bookedFor = schoolClass.next_date;
                item.amount = schoolClass.drop_in_price ?? '';
                item.dailyCheckins = 1;
                items.push(item);
            });

            if (items.length > beforeCount) {
                items.forEach((existing, index) => {
                    existing.collapsed = index !== items.length - 1;
                });
            }

            renderItems();
            summary();
            renderOptions();

            if (items.length > beforeCount) {
                setBuilderCollapsed(true);
            }

            closeClassGrade();
        });

        // Submit
        saleForm.addEventListener('submit', async function (event) {
            event.preventDefault();
            showError(null);
            saleUnitInput.value = saleUnit.value;
            saleLaunchedAtInput.value = saleLaunchedAt.value;

            const validation = validateItems();

            if (validation) {
                showError(validation);
                return;
            }

            updateHiddenInputs();
            submitSale.disabled = true;

            try {
                const response = await fetch(saleForm.action, {
                    method: 'POST',
                    body: new FormData(saleForm),
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                });

                const data = await response.json();

                if (! response.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat().join(' ') : '';
                    showError(errors || data.message || 'Não foi possível registrar a venda.');
                    return;
                }

                window.location = data.redirect;
            } catch (error) {
                showError('Não foi possível registrar a venda. Tente novamente.');
            } finally {
                submitSale.disabled = false;
            }
        });

        // Initial state
        saleUnitInput.value = saleUnit.value;
        saleLaunchedAtInput.value = saleLaunchedAt.value;
        renderOptions();
        renderItems();
        summary();
    })();
</script>
@endsection
