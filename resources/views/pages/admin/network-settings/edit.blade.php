@extends('layouts.app')

@section('title', 'Configurações da Rede')

@php
    $themes = \App\Support\NetworkTheme::forNetwork($network);
    $logoUrl = \App\Support\NetworkTheme::logoUrl($network);
    $storedLogo = $network->settings['logo'] ?? null;
    $themeMode = \App\Support\NetworkTheme::mode($network);
    $policyDomains = $policyDomains ?? \App\Support\NetworkPolicy::schema();
    $policyValues = $policyValues ?? \App\Support\NetworkPolicy::forNetwork($network);
    $requestedMode = old('theme_mode', $themeMode);
    $editingMode = in_array($requestedMode, ['light', 'dark'], true) ? $requestedMode : 'dark';

    $errorKeys = $errors->keys();
    $hasErrorPrefix = fn (string $prefix): bool => collect($errorKeys)->contains(
        fn (string $key): bool => str_starts_with($key, $prefix)
    );

    $tabHasErrors = [
        'empresa' => collect($errorKeys)->contains(fn (string $key): bool => ! $hasErrorPrefix('theme') && ! $hasErrorPrefix('policies')),
        'tema' => $hasErrorPrefix('theme'),
        'politica' => $hasErrorPrefix('policies'),
    ];

    $initialTab = $tabHasErrors['tema'] ? 'tema' : ($tabHasErrors['politica'] ? 'politica' : 'empresa');

    $settingsTabs = [
        'empresa' => ['label' => 'Dados da empresa', 'icon' => 'fa-building'],
        'tema' => ['label' => 'Tema', 'icon' => 'fa-palette'],
        'politica' => ['label' => 'Política', 'icon' => 'fa-sitemap'],
    ];
@endphp

@section('content')
<div class="w-full space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Configurações da rede</h1>
            <p class="mt-1 text-text-secondary">{{ $network->name }} &middot; {{ $network->slug }}</p>
        </div>
        <p class="text-xs text-text-secondary">
            <i class="fas fa-shield-halved mr-1"></i>
            Administração da rede
        </p>
    </div>

    @if (session('success'))
        <div class="rounded-lg px-4 py-3 text-sm" style="background: var(--color-success-soft); border: 1px solid var(--color-success); color: var(--color-success);">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg px-4 py-3 text-sm" style="background: var(--color-danger-soft); border: 1px solid var(--color-danger); color: var(--color-danger);">
            <p class="mb-1 font-medium">Verifique os campos destacados:</p>
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="networkSettingsForm"
          action="{{ route('admin.network.settings.update') }}"
          method="POST"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Tabs (desktop) --}}
        <div class="hidden md:block" role="tablist" aria-label="Seções das configurações">
            <div class="flex gap-1 rounded-xl border border-custom bg-bg-secondary p-1">
                @foreach ($settingsTabs as $tabKey => $tab)
                    <button type="button"
                            data-settings-tab="{{ $tabKey }}"
                            role="tab"
                            id="settings-tab-{{ $tabKey }}"
                            aria-controls="settings-panel-{{ $tabKey }}"
                            aria-selected="{{ $initialTab === $tabKey ? 'true' : 'false' }}"
                            tabindex="{{ $initialTab === $tabKey ? '0' : '-1' }}"
                            class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-text-secondary transition-colors hover:text-text-primary aria-selected:bg-surface aria-selected:text-cyan aria-selected:shadow-sm">
                        <i class="fas {{ $tab['icon'] }}"></i>
                        <span>{{ $tab['label'] }}</span>
                        @if ($tabHasErrors[$tabKey])
                            <span class="h-2 w-2 flex-shrink-0 rounded-full" style="background: var(--color-red);" title="Há campos com erro nesta seção"></span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Tabs (mobile) --}}
        <div class="md:hidden">
            <label for="settingsTabSelect" class="label-field">Seção</label>
            <select id="settingsTabSelect" class="input-field">
                @foreach ($settingsTabs as $tabKey => $tab)
                    <option value="{{ $tabKey }}" @selected($initialTab === $tabKey)>{{ $tab['label'] }}</option>
                @endforeach
            </select>
        </div>

        {{-- Tab contents --}}
        <div class="min-h-[320px]" data-initial-settings-tab="{{ $initialTab }}">
            {{-- Dados da empresa --}}
            <div id="settings-panel-empresa"
                 data-settings-panel="empresa"
                 role="tabpanel"
                 aria-labelledby="settings-tab-empresa"
                 class="{{ $initialTab === 'empresa' ? '' : 'hidden' }}">
                <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-2">
                    <div class="space-y-6">
                        <section class="card space-y-6">
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="flex items-center gap-2 font-semibold">
                                    <i class="fas fa-building text-cyan"></i>
                                    Dados da empresa
                                </h2>
                                <span class="badge badge-info">Identificação</span>
                            </div>

                            {{-- Company name (8) + CNPJ (4) --}}
                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-12 md:col-span-8">
                                    <label for="company_name" class="label-field">Nome da empresa</label>
                                    <input type="text" id="company_name" name="company_name" maxlength="80" class="input-field"
                                           value="{{ old('company_name', $network->settings['company_name'] ?? $network->name) }}"
                                           placeholder="Nome exibido no menu do sistema">
                                    <p class="mt-1 text-xs text-text-secondary">Nome curto usado no menu e no título das páginas.</p>
                                    @error('company_name')
                                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="col-span-12 md:col-span-4">
                                    <label for="document" class="label-field">CNPJ</label>
                                    <input type="text" id="document" name="document" maxlength="18" class="input-field"
                                           value="{{ old('document', $network->document) }}"
                                           placeholder="00.000.000/0000-00">
                                    @error('document')
                                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Network name (full width) --}}
                            <div>
                                <label for="name" class="label-field">Nome da rede <span class="text-red">*</span></label>
                                <input type="text" id="name" name="name" required maxlength="255" class="input-field"
                                       value="{{ old('name', $network->name) }}">
                                @error('name')
                                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- Logo --}}
                            <div class="rounded-xl border border-custom bg-bg-secondary p-4">
                                <div class="flex flex-col gap-4 sm:flex-row">
                                    <div class="flex-shrink-0">
                                        <img id="logoPreview"
                                             src="{{ $logoUrl ?? '' }}"
                                             alt="Logo atual"
                                             class="h-24 w-24 rounded-xl border border-custom bg-surface-elevated object-contain p-2 {{ $logoUrl ? '' : 'hidden' }}">
                                        <div id="logoPlaceholder" class="flex h-24 w-24 items-center justify-center rounded-xl border border-dashed border-custom {{ $logoUrl ? 'hidden' : '' }}">
                                            <i class="fas fa-image text-2xl text-text-secondary"></i>
                                        </div>
                                    </div>

                                    <div class="flex-1 space-y-2">
                                        <label for="logo" class="label-field">Logo da rede</label>
                                        <input type="file"
                                               id="logo"
                                               name="logo"
                                               accept="image/png,image/jpeg,image/webp"
                                               class="input-field file:mr-3 file:rounded-md file:border-0 file:bg-cyan/10 file:px-3 file:py-1 file:text-sm file:font-medium file:text-cyan">
                                        @error('logo')
                                            <p class="text-sm text-red">{{ $message }}</p>
                                        @enderror

                                        <div class="space-y-1 text-xs text-text-secondary">
                                            <p class="font-medium text-text-secondary">Como deve ser a imagem:</p>
                                            <ul class="list-inside list-disc space-y-0.5">
                                                <li>Formatos aceitos: <strong>PNG, JPG ou WebP</strong>.</li>
                                                <li>Preferencialmente <strong>quadrada (1:1)</strong>, com fundo transparente (PNG/WebP).</li>
                                                <li>Tamanho recomendado: <strong>512 × 512 px</strong>.</li>
                                                <li>Mínimo de <strong>128 × 128 px</strong> e máximo de <strong>2048 × 2048 px</strong>.</li>
                                                <li>Arquivo de até <strong>2 MB</strong>.</li>
                                                <li>Evite textos pequenos: o logo aparece com cerca de 40 px no menu.</li>
                                            </ul>
                                        </div>

                                        @if ($logoUrl || $storedLogo)
                                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-red">
                                                <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))>
                                                Remover o logo atual
                                            </label>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="space-y-6">
                        {{-- Address --}}
                        <section class="card space-y-4">
                            <h2 class="flex items-center gap-2 font-semibold">
                                <i class="fas fa-location-dot text-purple"></i>
                                Endereço
                            </h2>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-6" data-cep-scope>
                                <div class="sm:col-span-2">
                                    <label for="zip_code" class="label-field">CEP</label>
                                    <input type="text" id="zip_code" name="zip_code" maxlength="9" class="input-field"
                                           value="{{ old('zip_code', $network->zip_code) }}" data-cep>
                                </div>

                                <div class="sm:col-span-3">
                                    <label for="street" class="label-field">Rua / Logradouro</label>
                                    <input type="text" id="street" name="street" maxlength="255" class="input-field"
                                           value="{{ old('street', $network->street) }}" data-cep-field="street">
                                </div>

                                <div class="sm:col-span-1">
                                    <label for="number" class="label-field">Número</label>
                                    <input type="text" id="number" name="number" maxlength="20" class="input-field"
                                           value="{{ old('number', $network->number) }}" data-cep-field="number">
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="complement" class="label-field">Complemento</label>
                                    <input type="text" id="complement" name="complement" maxlength="255" class="input-field"
                                           value="{{ old('complement', $network->complement) }}">
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="neighborhood" class="label-field">Bairro</label>
                                    <input type="text" id="neighborhood" name="neighborhood" maxlength="255" class="input-field"
                                           value="{{ old('neighborhood', $network->neighborhood) }}" data-cep-field="neighborhood">
                                </div>

                                <div class="sm:col-span-1">
                                    <label for="city" class="label-field">Cidade</label>
                                    <input type="text" id="city" name="city" maxlength="255" class="input-field"
                                           value="{{ old('city', $network->city) }}" data-cep-field="city">
                                </div>

                                <div class="sm:col-span-1">
                                    <label for="state" class="label-field">UF</label>
                                    <input type="text" id="state" name="state" maxlength="2" class="input-field uppercase"
                                           value="{{ old('state', $network->state) }}" data-cep-field="state">
                                </div>
                            </div>
                        </section>

                        {{-- Responsible --}}
                        <section class="card space-y-4">
                            <h2 class="flex items-center gap-2 font-semibold">
                                <i class="fas fa-user-tie text-lime"></i>
                                Responsável
                            </h2>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="responsible_name" class="label-field">Nome do responsável</label>
                                    <input type="text" id="responsible_name" name="responsible_name" maxlength="255" class="input-field"
                                           value="{{ old('responsible_name', $network->responsible_name) }}">
                                </div>

                                <div>
                                    <label for="responsible_cpf" class="label-field">CPF do responsável</label>
                                    <input type="text" id="responsible_cpf" name="responsible_cpf" maxlength="14" class="input-field"
                                           value="{{ old('responsible_cpf', $network->responsible_cpf) }}">
                                </div>

                                <div>
                                    <label for="responsible_email" class="label-field">E-mail do responsável</label>
                                    <input type="email" id="responsible_email" name="responsible_email" maxlength="255" class="input-field"
                                           value="{{ old('responsible_email', $network->responsible_email) }}">
                                </div>

                                <div>
                                    <label for="responsible_phone" class="label-field">Telefone do responsável</label>
                                    <input type="text" id="responsible_phone" name="responsible_phone" maxlength="20" class="input-field"
                                           value="{{ old('responsible_phone', $network->responsible_phone) }}">
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>

            {{-- Tema --}}
            <div id="settings-panel-tema"
                 data-settings-panel="tema"
                 role="tabpanel"
                 aria-labelledby="settings-tab-tema"
                 class="{{ $initialTab === 'tema' ? '' : 'hidden' }}">
                <section class="card space-y-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="flex items-center gap-2 font-semibold">
                                <i class="fas fa-palette text-cyan"></i>
                                Tema padrão da rede
                            </h2>
                            <p class="mt-1 text-xs text-text-secondary">
                                Escolha o tema padrão e ajuste as cores. Usuários que já escolheram um tema mantêm a preferência salva.
                            </p>
                        </div>
                        <span class="badge badge-secondary">White-label</span>
                    </div>

                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 md:col-span-6">
                            <label for="theme_mode" class="label-field">Tema padrão</label>
                            <select id="theme_mode" name="theme_mode" class="input-field">
                                <option value="system" @selected(old('theme_mode', $themeMode) === 'system')>Seguir o sistema</option>
                                <option value="light" @selected(old('theme_mode', $themeMode) === 'light')>Claro</option>
                                <option value="dark" @selected(old('theme_mode', $themeMode) === 'dark')>Escuro</option>
                            </select>
                            <p class="mt-1 text-xs text-text-secondary">
                                Ao trocar o tema, a paleta correspondente é aberta para edição.
                            </p>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <span class="label-field">Paleta em edição</span>
                            <div class="flex gap-2" role="tablist" aria-label="Paleta de cores">
                                <button type="button"
                                        data-theme-tab="light"
                                        role="tab"
                                        aria-selected="false"
                                        class="flex-1 cursor-pointer rounded-lg border border-custom px-3 py-2 text-sm text-text-secondary transition-colors hover:text-text-primary aria-selected:border-cyan aria-selected:bg-cyan/10 aria-selected:text-cyan">
                                    <i class="fas fa-sun mr-1"></i>
                                    Clara
                                </button>
                                <button type="button"
                                        data-theme-tab="dark"
                                        role="tab"
                                        aria-selected="false"
                                        class="flex-1 cursor-pointer rounded-lg border border-custom px-3 py-2 text-sm text-text-secondary transition-colors hover:text-text-primary aria-selected:border-cyan aria-selected:bg-cyan/10 aria-selected:text-cyan">
                                    <i class="fas fa-moon mr-1"></i>
                                    Escura
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        {{-- Elements preview --}}
                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wider text-text-secondary">
                                Exemplos de elementos
                            </p>
                            @include('pages.admin.network-settings._theme-preview', [
                                'mode' => $editingMode,
                                'palette' => $themes[$editingMode],
                            ])
                        </div>

                        {{-- Colors and references --}}
                        <div class="space-y-2" style="max-height: 70vh; overflow: auto;">
                            <p class="text-xs font-semibold uppercase tracking-wider text-text-secondary">
                                Cores e referências
                            </p>

                            <div data-theme-panel="light" class="{{ $editingMode === 'light' ? '' : 'hidden' }}">
                                @include('pages.admin.network-settings._color-fields', ['mode' => 'light', 'palette' => $themes['light']])
                            </div>

                            <div data-theme-panel="dark" class="{{ $editingMode === 'dark' ? '' : 'hidden' }}">
                                @include('pages.admin.network-settings._color-fields', ['mode' => 'dark', 'palette' => $themes['dark']])
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Política --}}
            <div id="settings-panel-politica"
                 data-settings-panel="politica"
                 role="tabpanel"
                 aria-labelledby="settings-tab-politica"
                 class="{{ $initialTab === 'politica' ? '' : 'hidden' }}">
                <div class="space-y-6">
                    <section class="card space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h2 class="flex items-center gap-2 font-semibold">
                                    <i class="fas fa-sitemap text-purple"></i>
                                    Políticas organizacionais
                                </h2>
                                <p class="mt-1 text-xs text-text-secondary">
                                    Defina o alcance de cada domínio dentro da rede, quem pode administrá-lo e o que as unidades podem sobrescrever.
                                </p>
                            </div>
                            <span class="badge badge-info">Escopos</span>
                        </div>

                        @include('pages.admin.network-settings._policy-fields')
                    </section>

                    {{-- Current plan --}}
                    <section class="card">
                        <h2 class="mb-4 flex items-center gap-2 font-semibold">
                            <i class="fas fa-layer-group text-purple"></i>
                            Plano contratado
                        </h2>

                        @if ($network->plan)
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <p class="font-medium">{{ $network->plan->name }}</p>
                                    <p class="text-sm text-text-secondary">{{ $network->plan->description ?? 'Sem descrição' }}</p>
                                </div>
                                @if ($network->plan->isActive())
                                    <span class="badge badge-success">Ativo</span>
                                @else
                                    <span class="badge badge-secondary">Inativo</span>
                                @endif
                            </div>

                            @if ($network->plan->parameters->count() > 0)
                                <div class="space-y-2">
                                    @foreach ($network->plan->parameters as $parameter)
                                        <div class="flex items-center justify-between rounded-lg bg-bg-secondary px-4 py-3">
                                            <span class="text-sm">{{ $parameter->description }}</span>
                                            <span class="font-medium">R$ {{ number_format((float) $parameter->value, 2, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-text-secondary">Este plano não possui parâmetros cadastrados.</p>
                            @endif
                        @else
                            <p class="text-sm text-text-secondary">Nenhum plano vinculado a esta rede.</p>
                        @endif
                    </section>
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse justify-end gap-3 border-t border-custom pt-4 sm:flex-row">
            <a href="{{ route('admin.dashboard') }}" class="btn-outline justify-center text-center">Voltar</a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>
                Salvar configurações
            </button>
        </div>
    </form>
</div>

<script>
    (function () {
        // Main tabs
        const settingsForm = document.getElementById('networkSettingsForm');
        const settingsTabs = Array.from(document.querySelectorAll('[data-settings-tab]'));
        const settingsPanels = Array.from(document.querySelectorAll('[data-settings-panel]'));
        const settingsSelect = document.getElementById('settingsTabSelect');

        function showSettingsTab(tab) {
            settingsPanels.forEach(function (panel) {
                panel.classList.toggle('hidden', panel.dataset.settingsPanel !== tab);
            });

            settingsTabs.forEach(function (button) {
                const isActive = button.dataset.settingsTab === tab;
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                button.tabIndex = isActive ? 0 : -1;
            });

            if (settingsSelect) {
                settingsSelect.value = tab;
            }
        }

        settingsTabs.forEach(function (button) {
            button.addEventListener('click', function () {
                showSettingsTab(button.dataset.settingsTab);
            });
        });

        settingsSelect?.addEventListener('change', function () {
            showSettingsTab(settingsSelect.value);
        });

        // Reveal the tab that contains the first invalid field on submit
        settingsForm?.addEventListener('submit', function () {
            const invalid = settingsForm.querySelector(':invalid');

            if (!invalid) {
                return;
            }

            const panel = invalid.closest('[data-settings-panel]');

            if (panel) {
                showSettingsTab(panel.dataset.settingsPanel);
            }
        });

        if (settingsSelect) {
            showSettingsTab(settingsSelect.value);
        }

        // Logo preview
        const logoInput = document.getElementById('logo');
        const logoPreview = document.getElementById('logoPreview');
        const logoPlaceholder = document.getElementById('logoPlaceholder');

        logoInput?.addEventListener('change', function () {
            const file = this.files && this.files[0];

            if (!file) {
                return;
            }

            logoPreview.src = URL.createObjectURL(file);
            logoPreview.classList.remove('hidden');
            logoPlaceholder?.classList.add('hidden');
        });

        // Theme palette editor
        const themeSelect = document.getElementById('theme_mode');
        const preview = document.querySelector('[data-theme-preview]');
        const previewLabel = preview?.querySelector('[data-theme-preview-label]');
        const paletteTabs = Array.from(document.querySelectorAll('[data-theme-tab]'));
        const palettePanels = Array.from(document.querySelectorAll('[data-theme-panel]'));
        const colorInputs = Array.from(document.querySelectorAll('[data-theme-color]'));

        if (!preview || !themeSelect) {
            return;
        }

        const colorKeys = Array.from(new Set(colorInputs.map((input) => input.dataset.themeColorKey)));

        function syncPreview(mode) {
            colorKeys.forEach(function (key) {
                const input = document.getElementById('theme_' + mode + '_' + key);

                if (input) {
                    preview.style.setProperty('--tp-' + key.replaceAll('_', '-'), input.value);
                }
            });

            preview.dataset.palette = mode;

            if (previewLabel) {
                previewLabel.textContent = mode === 'dark' ? 'escuro' : 'claro';
            }
        }

        function showPalette(mode) {
            palettePanels.forEach(function (panel) {
                panel.classList.toggle('hidden', panel.dataset.themePanel !== mode);
            });

            paletteTabs.forEach(function (tab) {
                tab.setAttribute('aria-selected', tab.dataset.themeTab === mode ? 'true' : 'false');
            });

            syncPreview(mode);
        }

        paletteTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                showPalette(tab.dataset.themeTab);
            });
        });

        themeSelect.addEventListener('change', function () {
            if (themeSelect.value === 'light' || themeSelect.value === 'dark') {
                showPalette(themeSelect.value);
            }
        });

        colorInputs.forEach(function (input) {
            input.addEventListener('input', function () {
                const hexLabel = input.closest('div')?.querySelector('[data-color-hex]');

                if (hexLabel) {
                    hexLabel.textContent = input.value.toUpperCase();
                }

                if (input.dataset.themeColorMode === preview.dataset.palette) {
                    syncPreview(input.dataset.themeColorMode);
                }
            });
        });

        showPalette(themeSelect.value === 'light' ? 'light' : 'dark');
    })();
</script>
@endsection
