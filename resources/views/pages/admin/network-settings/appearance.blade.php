@extends('layouts.app')

@section('title', 'Temas e layout')

@php
    $menu = array_merge($appearance['menu'], old('appearance.menu', []));
    $login = array_merge($appearance['login'], old('appearance.login', []));
    $requestedMode = old('theme_mode', $themeMode);
    $editingMode = in_array($requestedMode, ['light', 'dark'], true) ? $requestedMode : 'dark';
    $companyName = \App\Support\NetworkTheme::companyName($network);
    $brandLogo = $logoUrl ?: asset('images/logo.png');

    $errorKeys = collect($errors->keys());
    $initialTab = $errorKeys->contains(fn (string $key) => str_starts_with($key, 'appearance.login'))
        ? 'login'
        : ($errorKeys->contains(fn (string $key) => str_starts_with($key, 'theme')) ? 'components' : 'menu');

    $menuColors = [
        'background' => 'Fundo do topo',
        'border' => 'Borda inferior do topo',
        'text' => 'Cor do nome / logo',
        'sidebar_background' => 'Fundo do menu lateral',
        'sidebar_text' => 'Texto dos itens',
        'hover_background' => 'Fundo ao passar o mouse',
        'hover_text' => 'Texto ao passar o mouse',
        'active_background' => 'Fundo do item ativo',
        'active_text' => 'Texto do item ativo',
    ];
    $loginColors = [
        'background' => 'Fundo geral',
        'panel' => 'Fundo do card',
        'text' => 'Texto do card',
        'muted' => 'Texto secundário',
        'accent' => 'Destaque / botão',
    ];
@endphp

@push('styles')
<style>
    .appearance-preview-brand {
        align-items: center;
        border-bottom: 1px solid;
        display: flex;
        gap: .75rem;
        justify-content: space-between;
        min-height: 4rem;
        padding: .75rem 1rem;
    }

    .appearance-preview-name {
        font-size: 1.05rem;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .appearance-tab {
        align-items: center;
        border-radius: .55rem;
        display: inline-flex;
        font-size: .8rem;
        font-weight: 600;
        gap: .4rem;
        padding: .55rem .8rem;
        transition: all .15s ease;
    }

    .appearance-tab.is-active {
        background: var(--color-surface);
        box-shadow: 0 1px 2px color-mix(in srgb, var(--color-text-primary) 10%, transparent);
        color: var(--color-primary);
    }

    .appearance-color-row {
        align-items: center;
        background: var(--color-bg-secondary);
        border: 1px solid var(--color-border);
        border-radius: .7rem;
        display: flex;
        gap: .7rem;
        padding: .55rem .65rem;
    }

    .appearance-component-chip {
        border: 1px solid var(--color-border);
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 600;
        padding: .3rem .65rem;
        transition: all .15s ease;
    }

    .appearance-component-chip:hover {
        border-color: var(--color-primary);
        color: var(--color-primary);
    }

    .appearance-login-preview {
        border: 1px solid var(--color-border);
        border-radius: 1rem;
        overflow: hidden;
    }

    .appearance-login-brand {
        color: #fff;
        min-height: 220px;
        padding: 1.2rem;
    }

    .appearance-login-panel {
        min-height: 220px;
        padding: 1.2rem;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Temas e layout</h1>
            <p class="mt-1 text-text-secondary">{{ $network->name }} &middot; white-label da rede</p>
        </div>
        <p class="text-xs text-text-secondary">
            <i class="fas fa-palette mr-1"></i>
            Personalização visual
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

    <form id="appearanceForm"
          action="{{ route('admin.network.settings.appearance.update') }}"
          method="POST"
          class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-xl border border-custom bg-bg-secondary p-1">
            <div class="flex flex-wrap gap-1" role="tablist" aria-label="Temas e layout">
                <button type="button" data-appearance-tab="menu" class="appearance-tab {{ $initialTab === 'menu' ? 'is-active' : '' }}">
                    <i class="fas fa-bars"></i>
                    Menu
                </button>
                <button type="button" data-appearance-tab="login" class="appearance-tab {{ $initialTab === 'login' ? 'is-active' : '' }}">
                    <i class="fas fa-right-to-bracket"></i>
                    Login do aluno
                </button>
                <button type="button" data-appearance-tab="components" class="appearance-tab {{ $initialTab === 'components' ? 'is-active' : '' }}">
                    <i class="fas fa-shapes"></i>
                    Componentes
                </button>
            </div>
        </div>

        {{-- MENU --}}
        <section data-appearance-panel="menu" class="{{ $initialTab === 'menu' ? '' : 'hidden' }}">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                <div class="card space-y-4">
                    <div>
                        <h2 class="flex items-center gap-2 font-semibold">
                            <i class="fas fa-bars text-cyan"></i>
                            Prévia do topo do menu
                        </h2>
                        <p class="mt-1 text-xs text-text-secondary">
                            Este é o bloco do topo da barra lateral, onde ficam o logo e o nome da empresa.
                        </p>
                    </div>

                    <div class="appearance-login-preview" data-menu-preview-sidebar
                         style="background-color: {{ $menu['sidebar_background'] }};">
                        <div data-menu-preview
                             class="appearance-preview-brand"
                             style="background-color: {{ $menu['background'] }}; border-color: {{ $menu['border'] }}; color: {{ $menu['text'] }};">
                            <div data-menu-preview-anchor
                                 class="flex min-w-0 items-center gap-3 {{ ! $menu['show_name'] && $menu['center_logo'] ? 'flex-1 justify-center' : '' }}">
                                <img data-menu-preview-logo
                                     src="{{ $brandLogo }}"
                                     alt="{{ $companyName }}"
                                     class="w-auto flex-shrink-0 rounded-lg object-contain"
                                     style="height: {{ $menu['logo_height'] }}px; max-width: 180px; background: color-mix(in srgb, {{ $menu['text'] }} 10%, transparent); padding: 4px;">
                                <span data-menu-preview-name
                                      class="appearance-preview-name"
                                      style="color: {{ $menu['text'] }}; {{ $menu['show_name'] ? '' : 'display:none;' }}">
                                    {{ $companyName }}
                                </span>
                            </div>
                            <span class="text-sm opacity-60"><i class="fas fa-xmark"></i></span>
                        </div>

                        <div class="space-y-1 p-2">
                            <div data-menu-preview-active class="rounded-lg px-3 py-2 text-xs font-semibold"
                                 style="background-color: {{ $menu['active_background'] }}; color: {{ $menu['active_text'] }};">
                                Dashboard
                            </div>
                            <div data-menu-preview-item class="rounded-lg px-3 py-2 text-xs"
                                 style="color: {{ $menu['sidebar_text'] }};">
                                Visão geral da rede
                            </div>
                            <div data-menu-preview-item class="rounded-lg px-3 py-2 text-xs"
                                 style="color: {{ $menu['sidebar_text'] }};">
                                Configurações
                            </div>
                            <div data-menu-preview-hover class="rounded-lg px-3 py-2 text-xs"
                                 style="background-color: {{ $menu['hover_background'] }}; color: {{ $menu['hover_text'] }};">
                                Gerenciamento <span class="opacity-60">(hover)</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-text-secondary">
                        <i class="fas fa-circle-info mr-1"></i>
                        O upload do logo fica em <a href="{{ route('admin.network.settings.company') }}" class="text-cyan hover:underline">Dados da empresa</a>.
                    </p>
                </div>

                <div class="card space-y-4">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <i class="fas fa-sliders text-cyan"></i>
                        Configuração do menu
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="appearance_menu_logo_height" class="label-field">Altura do logo (px)</label>
                            <input type="number" id="appearance_menu_logo_height" name="appearance[menu][logo_height]"
                                   min="24" max="120" step="1" value="{{ old('appearance.menu.logo_height', $menu['logo_height']) }}"
                                   class="input-field" data-menu-field="logo_height">
                            <p class="mt-1 text-xs text-text-secondary">Recomendado entre 32 e 64 px. O logo pode ficar mais alto e largo.</p>
                            @error('appearance.menu.logo_height')<p class="mt-1 text-sm text-red">{{ $message }}</p>@enderror
                        </div>

                        <label class="sm:col-span-2 flex cursor-pointer items-center gap-2">
                            <input type="hidden" name="appearance[menu][show_name]" value="0">
                            <input type="checkbox" id="appearance_menu_show_name" name="appearance[menu][show_name]" value="1"
                                   @checked(old('appearance.menu.show_name', $menu['show_name'])) data-menu-field="show_name">
                            <span class="text-sm font-medium">Exibir o nome da empresa ao lado do logo</span>
                        </label>

                        <label class="sm:col-span-2 flex cursor-pointer items-center gap-2">
                            <input type="hidden" name="appearance[menu][center_logo]" value="0">
                            <input type="checkbox" id="appearance_menu_center_logo" name="appearance[menu][center_logo]" value="1"
                                   @checked(old('appearance.menu.center_logo', $menu['center_logo'])) data-menu-field="center_logo">
                            <span class="text-sm font-medium">Centralizar o logo no topo quando exibir apenas a imagem</span>
                        </label>

                        <p class="sm:col-span-2 text-xs font-semibold uppercase tracking-wider text-text-secondary">
                            Cores do topo e do menu lateral
                        </p>

                        @foreach ($menuColors as $colorKey => $colorLabel)
                            <div class="appearance-color-row sm:col-span-2">
                                <input type="color"
                                       id="appearance_menu_{{ $colorKey }}"
                                       name="appearance[menu][{{ $colorKey }}]"
                                       value="{{ old('appearance.menu.'.$colorKey, $menu[$colorKey]) }}"
                                       class="h-8 w-10 flex-shrink-0 cursor-pointer rounded-md border border-custom bg-transparent p-0.5"
                                       data-menu-field="{{ $colorKey }}"
                                       aria-label="{{ $colorLabel }}">
                                <div class="min-w-0 flex-1">
                                    <label for="appearance_menu_{{ $colorKey }}" class="block text-sm font-medium">{{ $colorLabel }}</label>
                                    <span class="font-mono text-xs uppercase text-text-secondary" data-menu-hex="{{ $colorKey }}">
                                        {{ old('appearance.menu.'.$colorKey, $menu[$colorKey]) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- LOGIN --}}
        <section data-appearance-panel="login" class="{{ $initialTab === 'login' ? '' : 'hidden' }}">
            <div class="grid grid-cols-1 gap-6 xl:grid-cols-[1.05fr_0.95fr]">
                <div class="card space-y-4">
                    <div>
                        <h2 class="flex items-center gap-2 font-semibold">
                            <i class="fas fa-right-to-bracket text-cyan"></i>
                            Prévia do login do aluno
                        </h2>
                        <p class="mt-1 text-xs text-text-secondary">
                            Textos, logo e cores exibidos na tela de acesso do aluno.
                        </p>
                    </div>

                    <div data-login-preview class="appearance-login-preview" style="background-color: {{ $login['background'] }};">
                        <div class="grid gap-0 lg:grid-cols-2">
                            <div data-login-brand class="appearance-login-brand"
                                 style="background: linear-gradient(145deg, {{ $login['accent'] }}, color-mix(in srgb, {{ $login['accent'] }} 55%, #000 45%));">
                                <img data-login-logo src="{{ $brandLogo }}" alt="{{ $companyName }}"
                                     class="w-auto rounded-xl bg-white/10 object-contain p-2"
                                     style="height: {{ $login['logo_height'] }}px; max-width: 200px;">
                                <p data-login-eyebrow class="mt-4 text-[0.65rem] font-bold uppercase tracking-[0.2em] text-white/70">
                                    {{ $login['eyebrow'] }}
                                </p>
                                <p data-login-headline class="mt-2 text-xl font-black leading-tight text-white">
                                    {{ $login['headline'] }}
                                </p>
                                <p data-login-description class="mt-2 text-xs leading-relaxed text-white/75">
                                    {{ $login['description'] }}
                                </p>
                            </div>

                            <div data-login-panel class="appearance-login-panel"
                                 style="background-color: {{ $login['panel'] }}; color: {{ $login['text'] }};">
                                <p data-login-welcome-title class="text-lg font-bold">{{ $login['welcome_title'] }}</p>
                                <p data-login-welcome-subtitle class="mt-1 text-xs"
                                   style="color: {{ $login['muted'] }};">{{ $login['welcome_subtitle'] }}</p>
                                <div class="mt-4 space-y-2">
                                    <div class="h-8 rounded-lg border border-custom bg-white/5"></div>
                                    <div class="h-8 rounded-lg border border-custom bg-white/5"></div>
                                    <button type="button" data-login-button class="w-full rounded-lg px-3 py-2 text-sm font-semibold text-white"
                                            style="background-color: {{ $login['accent'] }};">
                                        {{ $login['button_text'] }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card space-y-4">
                    <h2 class="flex items-center gap-2 font-semibold">
                        <i class="fas fa-pen-nib text-cyan"></i>
                        Textos e cores do login
                    </h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="appearance_login_eyebrow" class="label-field">Sobretítulo</label>
                            <input type="text" id="appearance_login_eyebrow" name="appearance[login][eyebrow]"
                                   maxlength="80" class="input-field" value="{{ old('appearance.login.eyebrow', $login['eyebrow']) }}"
                                   data-login-field="eyebrow">
                        </div>

                        <div>
                            <label for="appearance_login_button_text" class="label-field">Texto do botão</label>
                            <input type="text" id="appearance_login_button_text" name="appearance[login][button_text]"
                                   maxlength="40" class="input-field" value="{{ old('appearance.login.button_text', $login['button_text']) }}"
                                   data-login-field="button_text">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="appearance_login_headline" class="label-field">Título de destaque</label>
                            <input type="text" id="appearance_login_headline" name="appearance[login][headline]"
                                   maxlength="180" class="input-field" value="{{ old('appearance.login.headline', $login['headline']) }}"
                                   data-login-field="headline">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="appearance_login_description" class="label-field">Descrição</label>
                            <textarea id="appearance_login_description" name="appearance[login][description]" rows="2" maxlength="400"
                                      class="input-field" data-login-field="description">{{ old('appearance.login.description', $login['description']) }}</textarea>
                        </div>

                        <div>
                            <label for="appearance_login_welcome_title" class="label-field">Boas-vindas</label>
                            <input type="text" id="appearance_login_welcome_title" name="appearance[login][welcome_title]"
                                   maxlength="120" class="input-field" value="{{ old('appearance.login.welcome_title', $login['welcome_title']) }}"
                                   data-login-field="welcome_title">
                        </div>

                        <div>
                            <label for="appearance_login_logo_height" class="label-field">Altura do logo (px)</label>
                            <input type="number" id="appearance_login_logo_height" name="appearance[login][logo_height]"
                                   min="32" max="180" step="1" value="{{ old('appearance.login.logo_height', $login['logo_height']) }}"
                                   class="input-field" data-login-field="logo_height">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="appearance_login_welcome_subtitle" class="label-field">Subtítulo de boas-vindas</label>
                            <input type="text" id="appearance_login_welcome_subtitle" name="appearance[login][welcome_subtitle]"
                                   maxlength="240" class="input-field" value="{{ old('appearance.login.welcome_subtitle', $login['welcome_subtitle']) }}"
                                   data-login-field="welcome_subtitle">
                        </div>

                        @foreach ($loginColors as $colorKey => $colorLabel)
                            <div class="appearance-color-row sm:col-span-2">
                                <input type="color"
                                       id="appearance_login_{{ $colorKey }}"
                                       name="appearance[login][{{ $colorKey }}]"
                                       value="{{ old('appearance.login.'.$colorKey, $login[$colorKey]) }}"
                                       class="h-8 w-10 flex-shrink-0 cursor-pointer rounded-md border border-custom bg-transparent p-0.5"
                                       data-login-field="{{ $colorKey }}"
                                       aria-label="{{ $colorLabel }}">
                                <div class="min-w-0 flex-1">
                                    <label for="appearance_login_{{ $colorKey }}" class="block text-sm font-medium">{{ $colorLabel }}</label>
                                    <span class="font-mono text-xs uppercase text-text-secondary" data-login-hex="{{ $colorKey }}">
                                        {{ old('appearance.login.'.$colorKey, $login[$colorKey]) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- COMPONENTES --}}
        <section data-appearance-panel="components" class="{{ $initialTab === 'components' ? '' : 'hidden' }}">
            <div class="space-y-6">
                <div class="card space-y-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h2 class="flex items-center gap-2 font-semibold">
                                <i class="fas fa-shapes text-cyan"></i>
                                Componentes do sistema
                            </h2>
                            <p class="mt-1 text-xs text-text-secondary">
                                Cores de botões, avisos, textos, tabelas, formulários, abas, paginação e sobreposições.
                            </p>
                        </div>
                        <span class="badge badge-secondary">White-label</span>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        @foreach ([
                            'messages' => 'Mensagens',
                            'cards' => 'Cards',
                            'buttons' => 'Botões',
                            'badges' => 'Badges',
                            'forms' => 'Formulários',
                            'texts' => 'Textos',
                            'table' => 'Tabelas',
                            'tabs' => 'Abas e paginação',
                            'modal' => 'Modal / dropdown',
                        ] as $componentKey => $componentLabel)
                            <button type="button" class="appearance-component-chip" data-component-target="{{ $componentKey }}">
                                {{ $componentLabel }}
                            </button>
                        @endforeach
                    </div>

                    <div class="grid grid-cols-12 gap-4">
                        <div class="col-span-12 md:col-span-6">
                            <label for="theme_mode" class="label-field">Tema padrão</label>
                            <select id="theme_mode" name="theme_mode" class="input-field">
                                <option value="system" @selected(old('theme_mode', $themeMode) === 'system')>Seguir o sistema</option>
                                <option value="light" @selected(old('theme_mode', $themeMode) === 'light')>Claro</option>
                                <option value="dark" @selected(old('theme_mode', $themeMode) === 'dark')>Escuro</option>
                            </select>
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <span class="label-field">Paleta em edição</span>
                            <div class="flex gap-2">
                                <button type="button" data-theme-tab="light" aria-selected="false"
                                        class="flex-1 rounded-lg border border-custom px-3 py-2 text-sm text-text-secondary hover:text-text-primary aria-selected:border-cyan aria-selected:bg-cyan/10 aria-selected:text-cyan">
                                    <i class="fas fa-sun mr-1"></i> Clara
                                </button>
                                <button type="button" data-theme-tab="dark" aria-selected="false"
                                        class="flex-1 rounded-lg border border-custom px-3 py-2 text-sm text-text-secondary hover:text-text-primary aria-selected:border-cyan aria-selected:bg-cyan/10 aria-selected:text-cyan">
                                    <i class="fas fa-moon mr-1"></i> Escura
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                        <div class="space-y-2">
                            <p class="text-xs font-semibold uppercase tracking-wider text-text-secondary">Prévia dos componentes</p>
                            @include('pages.admin.network-settings._theme-preview', [
                                'mode' => $editingMode,
                                'palette' => $themes[$editingMode],
                            ])
                        </div>

                        <div class="space-y-2" style="max-height: 70vh; overflow: auto;">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-xs font-semibold uppercase tracking-wider text-text-secondary">Cores e referências</p>
                                <button type="button" id="showAllColors" class="hidden text-xs font-semibold text-cyan hover:underline">
                                    Mostrar todas
                                </button>
                            </div>

                            <div data-theme-panel="light" class="{{ $editingMode === 'light' ? '' : 'hidden' }}">
                                @include('pages.admin.network-settings._color-fields', ['mode' => 'light', 'palette' => $themes['light']])
                            </div>

                            <div data-theme-panel="dark" class="{{ $editingMode === 'dark' ? '' : 'hidden' }}">
                                @include('pages.admin.network-settings._color-fields', ['mode' => 'dark', 'palette' => $themes['dark']])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="flex flex-col-reverse justify-end gap-3 border-t border-custom pt-4 sm:flex-row">
            <a href="{{ route('admin.dashboard') }}" class="btn-outline justify-center text-center">Voltar</a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>
                Salvar temas e layout
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        // Appearance tabs
        const tabButtons = Array.from(document.querySelectorAll('[data-appearance-tab]'));
        const tabPanels = Array.from(document.querySelectorAll('[data-appearance-panel]'));

        function showAppearanceTab(tab) {
            tabPanels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.appearancePanel !== tab));
            tabButtons.forEach((button) => button.classList.toggle('is-active', button.dataset.appearanceTab === tab));
        }

        tabButtons.forEach((button) => {
            button.addEventListener('click', () => showAppearanceTab(button.dataset.appearanceTab));
        });

        // Menu preview
        const menuPreview = document.querySelector('[data-menu-preview]');
        const menuPreviewSidebar = document.querySelector('[data-menu-preview-sidebar]');
        const menuPreviewAnchor = document.querySelector('[data-menu-preview-anchor]');
        const menuPreviewActive = document.querySelector('[data-menu-preview-active]');
        const menuPreviewItems = Array.from(document.querySelectorAll('[data-menu-preview-item]'));
        const menuPreviewHover = document.querySelector('[data-menu-preview-hover]');
        const menuLogo = document.querySelector('[data-menu-preview-logo]');
        const menuName = document.querySelector('[data-menu-preview-name]');
        const menuInputs = Array.from(document.querySelectorAll('[data-menu-field]'));

        function syncMenuPreview() {
            if (!menuPreview) {
                return;
            }

            const background = document.getElementById('appearance_menu_background')?.value || '#0D1B30';
            const border = document.getElementById('appearance_menu_border')?.value || '#263B57';
            const text = document.getElementById('appearance_menu_text')?.value || '#F8FAFC';
            const sidebarBackground = document.getElementById('appearance_menu_sidebar_background')?.value || '#0D1B30';
            const sidebarText = document.getElementById('appearance_menu_sidebar_text')?.value || '#A9B8CC';
            const hoverBackground = document.getElementById('appearance_menu_hover_background')?.value || '#142944';
            const hoverText = document.getElementById('appearance_menu_hover_text')?.value || '#F8FAFC';
            const activeBackground = document.getElementById('appearance_menu_active_background')?.value || '#142944';
            const activeText = document.getElementById('appearance_menu_active_text')?.value || '#0D6EFD';
            const height = Number(document.getElementById('appearance_menu_logo_height')?.value || 40);
            const showName = document.getElementById('appearance_menu_show_name')?.checked ?? true;
            const centerLogo = document.getElementById('appearance_menu_center_logo')?.checked ?? true;

            menuPreview.style.backgroundColor = background;
            menuPreview.style.borderColor = border;
            menuPreview.style.color = text;

            if (menuPreviewSidebar) {
                menuPreviewSidebar.style.backgroundColor = sidebarBackground;
            }

            if (menuPreviewAnchor) {
                menuPreviewAnchor.classList.toggle('flex-1', ! showName && centerLogo);
                menuPreviewAnchor.classList.toggle('justify-center', ! showName && centerLogo);
            }

            menuPreviewItems.forEach((item) => {
                item.style.color = sidebarText;
            });

            if (menuPreviewActive) {
                menuPreviewActive.style.backgroundColor = activeBackground;
                menuPreviewActive.style.color = activeText;
            }

            if (menuPreviewHover) {
                menuPreviewHover.style.backgroundColor = hoverBackground;
                menuPreviewHover.style.color = hoverText;
            }

            if (menuLogo) {
                menuLogo.style.height = `${height}px`;
                menuLogo.style.backgroundColor = `color-mix(in srgb, ${text} 10%, transparent)`;
            }

            if (menuName) {
                menuName.style.color = text;
                menuName.style.display = showName ? '' : 'none';
            }

            [
                'background', 'border', 'text',
                'sidebar_background', 'sidebar_text',
                'hover_background', 'hover_text',
                'active_background', 'active_text',
            ].forEach((key) => {
                const label = document.querySelector(`[data-menu-hex="${key}"]`);
                const input = document.getElementById(`appearance_menu_${key}`);

                if (label && input) {
                    label.textContent = input.value.toUpperCase();
                }
            });
        }

        menuInputs.forEach((input) => {
            input.addEventListener('input', syncMenuPreview);
            input.addEventListener('change', syncMenuPreview);
        });

        // Login preview
        const loginPreview = document.querySelector('[data-login-preview]');
        const loginBrand = document.querySelector('[data-login-brand]');
        const loginPanel = document.querySelector('[data-login-panel]');
        const loginLogo = document.querySelector('[data-login-logo]');
        const loginButton = document.querySelector('[data-login-button]');
        const loginInputs = Array.from(document.querySelectorAll('[data-login-field]'));

        function syncLoginPreview() {
            if (!loginPreview) {
                return;
            }

            const background = document.getElementById('appearance_login_background')?.value || '#071426';
            const panel = document.getElementById('appearance_login_panel')?.value || '#0D1B30';
            const text = document.getElementById('appearance_login_text')?.value || '#F8FAFC';
            const muted = document.getElementById('appearance_login_muted')?.value || '#A9B8CC';
            const accent = document.getElementById('appearance_login_accent')?.value || '#0D6EFD';
            const height = Number(document.getElementById('appearance_login_logo_height')?.value || 80);

            loginPreview.style.backgroundColor = background;

            if (loginBrand) {
                loginBrand.style.background = `linear-gradient(145deg, ${accent}, color-mix(in srgb, ${accent} 55%, #000 45%))`;
            }

            if (loginPanel) {
                loginPanel.style.backgroundColor = panel;
                loginPanel.style.color = text;
            }

            const loginMuted = document.querySelector('[data-login-welcome-subtitle]');

            if (loginMuted) {
                loginMuted.style.color = muted;
            }

            if (loginLogo) {
                loginLogo.style.height = `${height}px`;
            }

            if (loginButton) {
                loginButton.style.backgroundColor = accent;
            }

            const textTargets = {
                eyebrow: '[data-login-eyebrow]',
                headline: '[data-login-headline]',
                description: '[data-login-description]',
                welcome_title: '[data-login-welcome-title]',
                welcome_subtitle: '[data-login-welcome-subtitle]',
                button_text: '[data-login-button]',
            };

            Object.entries(textTargets).forEach(([key, selector]) => {
                const input = document.querySelector(`[data-login-field="${key}"]`);
                const target = document.querySelector(selector);

                if (input && target) {
                    target.textContent = input.value;
                }
            });

            ['background', 'panel', 'text', 'muted', 'accent'].forEach((key) => {
                const label = document.querySelector(`[data-login-hex="${key}"]`);
                const input = document.getElementById(`appearance_login_${key}`);

                if (label && input) {
                    label.textContent = input.value.toUpperCase();
                }
            });
        }

        loginInputs.forEach((input) => {
            input.addEventListener('input', syncLoginPreview);
            input.addEventListener('change', syncLoginPreview);
        });

        syncMenuPreview();
        syncLoginPreview();

        // Component clicks filter the color groups used by the element.
        const componentColorGroups = {
            messages: ['messages'],
            cards: ['structure', 'texts'],
            buttons: ['brand'],
            badges: ['messages'],
            forms: ['structure', 'texts', 'brand'],
            texts: ['texts'],
            table: ['structure', 'texts'],
            tabs: ['brand', 'structure'],
            modal: ['structure'],
        };

        const allColorGroups = Array.from(document.querySelectorAll('[data-color-group]'));
        const showAllColorsButton = document.getElementById('showAllColors');

        function showAllColors() {
            allColorGroups.forEach((group) => group.classList.remove('hidden'));
            showAllColorsButton?.classList.add('hidden');
            document.querySelectorAll('[data-component-section], [data-component-target]').forEach((element) => {
                element.classList.remove('ring-2', 'ring-cyan');
            });
        }

        function filterComponentColors(key) {
            const groups = componentColorGroups[key];

            if (!groups) {
                return;
            }

            allColorGroups.forEach((group) => {
                group.classList.toggle('hidden', ! groups.includes(group.dataset.colorGroup));
            });

            showAllColorsButton?.classList.remove('hidden');

            const previewTarget = document.querySelector(`[data-component-section="${key}"]`);
            previewTarget?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            previewTarget?.classList.add('ring-2', 'ring-cyan');

            const firstGroup = document.querySelector('[data-theme-panel]:not(.hidden) [data-color-group="' + groups[0] + '"]')
                ?? document.querySelector(`[data-color-group="${groups[0]}"]`);

            firstGroup?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }

        showAllColorsButton?.addEventListener('click', showAllColors);

        document.querySelectorAll('[data-component-target]').forEach((button) => {
            button.addEventListener('click', () => filterComponentColors(button.dataset.componentTarget));
        });

        document.querySelectorAll('[data-component-section]').forEach((section) => {
            section.title = 'Clique para filtrar as cores deste componente';
            section.style.cursor = 'pointer';
            section.addEventListener('click', () => filterComponentColors(section.dataset.componentSection));
        });

        // Theme palette editor (same behavior as the old settings page).
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
            colorKeys.forEach((key) => {
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
            palettePanels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.themePanel !== mode));
            paletteTabs.forEach((tab) => tab.setAttribute('aria-selected', tab.dataset.themeTab === mode ? 'true' : 'false'));
            syncPreview(mode);
        }

        paletteTabs.forEach((tab) => {
            tab.addEventListener('click', () => showPalette(tab.dataset.themeTab));
        });

        themeSelect.addEventListener('change', () => {
            if (themeSelect.value === 'light' || themeSelect.value === 'dark') {
                showPalette(themeSelect.value);
            }
        });

        colorInputs.forEach((input) => {
            input.addEventListener('input', () => {
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
@endpush
