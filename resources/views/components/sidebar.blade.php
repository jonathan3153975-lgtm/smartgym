@php
    $brandLogo = \App\Support\NetworkTheme::logoUrl($activeNetwork ?? null) ?: asset('images/logo.png');
    $brandName = \App\Support\NetworkTheme::companyName($activeNetwork ?? null);
    $tenantContext = \App\Support\TenantContext::current();
    $sidebarNetwork = $activeNetwork ?? (auth()->check() ? auth()->user()->getActiveVinculo()?->network : null);
    $menuAppearance = \App\Support\NetworkAppearance::menu($sidebarNetwork);
    $managementNetwork = auth()->check() ? auth()->user()->getActiveVinculo()?->network : null;
@endphp

<aside id="sidebar" class="sidebar flex flex-col">
    <style>
        #sidebar {
            background-color: {{ $menuAppearance['sidebar_background'] }};
        }

        #sidebar .nav-item,
        #sidebar .nav-subitem,
        #sidebar .nav-group-toggle {
            color: {{ $menuAppearance['sidebar_text'] }};
        }

        #sidebar .nav-item:hover,
        #sidebar .nav-subitem:hover,
        #sidebar .nav-group-toggle:hover {
            background-color: {{ $menuAppearance['hover_background'] }} !important;
            color: {{ $menuAppearance['hover_text'] }} !important;
        }

        #sidebar .nav-item[aria-current="page"],
        #sidebar .nav-item.text-cyan,
        #sidebar .nav-subitem.text-cyan {
            background-color: {{ $menuAppearance['active_background'] }} !important;
            color: {{ $menuAppearance['active_text'] }} !important;
        }

        #sidebar .nav-item[aria-current="page"] i,
        #sidebar .nav-item.text-cyan i,
        #sidebar .nav-subitem.text-cyan i {
            color: {{ $menuAppearance['active_text'] }} !important;
        }
    </style>

    {{-- Logo/Brand --}}
    <div class="flex items-center justify-between min-h-16 px-4 py-2 border-b"
         data-brand-bar
         style="background-color: {{ $menuAppearance['background'] }}; border-color: {{ $menuAppearance['border'] }}; color: {{ $menuAppearance['text'] }};">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 min-w-0 {{ ! $menuAppearance['show_name'] && $menuAppearance['center_logo'] ? 'flex-1 justify-center' : '' }}">
            <img src="{{ $brandLogo }}"
                 alt="{{ $brandName }}"
                 data-brand-logo
                 class="rounded-xl object-contain flex-shrink-0 p-1"
                 style="height: {{ $menuAppearance['logo_height'] }}px; width: auto; max-width: 180px; background: color-mix(in srgb, {{ $menuAppearance['text'] }} 10%, transparent); border: 1px solid color-mix(in srgb, {{ $menuAppearance['text'] }} 12%, transparent);">
            @if ($menuAppearance['show_name'])
                <span data-brand-name
                      class="text-xl font-bold truncate"
                      style="color: {{ $menuAppearance['text'] }};">
                    {{ $brandName }}
                </span>
            @endif
        </a>

        {{-- Mobile close button --}}
        <button
            id="sidebarClose"
            class="lg:hidden p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
            aria-label="Fechar menu"
        >
            <i class="fas fa-times"></i>
        </button>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 p-4 space-y-1 overflow-y-auto" aria-label="Navegação principal">
        {{-- Network/Region/Unit Context --}}
        @if (auth()->check() && session('active_network'))
            <div class="mb-4 p-3 rounded-lg border border-custom bg-bg-secondary">
                <div class="flex items-center gap-2 text-xs text-text-secondary mb-2">
                    <i class="fas fa-building"></i>
                    <span>Contexto Ativo</span>
                </div>
                <div class="space-y-1 text-sm">
                    @if (session('active_network'))
                        <div class="flex items-center gap-2 text-text-primary">
                            <i class="fas fa-sitemap text-cyan"></i>
                            <span class="font-medium truncate">{{ session('active_network.name') }}</span>
                        </div>
                    @endif
                    @if (session('active_region'))
                        <div class="flex items-center gap-2 pl-6 text-text-secondary">
                            <i class="fas fa-map-marked-alt text-purple"></i>
                            <span class="truncate">{{ session('active_region.name') }}</span>
                        </div>
                    @endif
                    @if (session('active_unit'))
                        <div class="flex items-center gap-2 pl-6 text-text-primary">
                            <i class="fas fa-dumbbell text-lime"></i>
                            <span class="font-medium truncate">{{ session('active_unit.name') }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Main Navigation --}}
        <div class="space-y-1">
            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
               class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200 group"
               {{ request()->routeIs('dashboard') ? 'aria-current="page"' : '' }}>
                <i class="fas fa-tachometer-alt w-5 text-center group-hover:text-cyan transition-colors"></i>
                <span>Dashboard</span>
            </a>

            {{-- Network overview and settings --}}
            @can('network.settings')
                @if ($managementNetwork)
                    <a href="{{ route('admin.networks.show', $managementNetwork) }}"
                       class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200 group
                              {{ request()->routeIs('admin.networks.show') ? 'text-cyan bg-cyan/10' : '' }}">
                        <i class="fas fa-chart-line w-5 text-center group-hover:text-cyan transition-colors"></i>
                        <span>Visão geral da rede</span>
                    </a>

                    <div class="nav-group">
                        <button type="button"
                                class="nav-group-toggle w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200"
                                aria-expanded="{{ request()->routeIs('admin.network.settings.*') ? 'true' : 'false' }}"
                                aria-controls="networkSettingsSubmenu">
                            <span class="flex items-center gap-3">
                                <i class="fas fa-gear w-5 text-center"></i>
                                <span>Configurações</span>
                            </span>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200"
                               style="transform: {{ request()->routeIs('admin.network.settings.*') ? 'rotate(180deg)' : 'rotate(0deg)' }};"></i>
                        </button>

                        <div id="networkSettingsSubmenu"
                             class="nav-submenu {{ request()->routeIs('admin.network.settings.*') ? '' : 'hidden' }} mt-1 ml-8 space-y-1"
                             role="region"
                             aria-label="Submenu Configurações">
                            <a href="{{ route('admin.network.settings.company') }}"
                               class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                      {{ request()->routeIs('admin.network.settings.company*') || request()->routeIs('admin.network.settings.edit') ? 'text-cyan bg-cyan/10' : '' }}">
                                <i class="fas fa-building w-4 text-center"></i>
                                <span>Dados da empresa</span>
                            </a>

                            <a href="{{ route('admin.network.settings.policies') }}"
                               class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                      {{ request()->routeIs('admin.network.settings.policies*') ? 'text-cyan bg-cyan/10' : '' }}">
                                <i class="fas fa-sitemap w-4 text-center"></i>
                                <span>Políticas</span>
                            </a>

                            <a href="{{ route('admin.network.settings.receipts') }}"
                               class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                      {{ request()->routeIs('admin.network.settings.receipts*') ? 'text-cyan bg-cyan/10' : '' }}">
                                <i class="fas fa-credit-card w-4 text-center"></i>
                                <span>Recebimentos</span>
                            </a>

                            <a href="{{ route('admin.network.settings.appearance') }}"
                               class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                      {{ request()->routeIs('admin.network.settings.appearance*') ? 'text-cyan bg-cyan/10' : '' }}">
                                <i class="fas fa-palette w-4 text-center"></i>
                                <span>Temas e layout</span>
                            </a>
                        </div>
                    </div>
                @endif
            @endcan

            {{-- Management --}}
            @can('students.view')
                <div class="pt-4 mt-4 border-t border-custom">
                    <div class="px-3 py-2 text-xs font-medium text-text-secondary uppercase tracking-wider">
                        Gerenciamento
                    </div>

                    <div class="nav-group">
                        <button type="button"
                                class="nav-group-toggle w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200"
                                aria-expanded="{{ request()->routeIs('management.*') ? 'true' : 'false' }}"
                                aria-controls="managementSubmenu">
                            <span class="flex items-center gap-3">
                                <i class="fas fa-briefcase w-5 text-center"></i>
                                <span>Gerenciamento</span>
                            </span>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200"
                               style="transform: {{ request()->routeIs('management.*') ? 'rotate(180deg)' : 'rotate(0deg)' }};"></i>
                        </button>

                        <div id="managementSubmenu"
                             class="nav-submenu {{ request()->routeIs('management.*') ? '' : 'hidden' }} mt-1 ml-8 space-y-1"
                             role="region"
                             aria-label="Submenu Gerenciamento">
                            <a href="{{ route('students.index') }}"
                               class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                      {{ request()->routeIs('students.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                <i class="fas fa-user-graduate w-4 text-center"></i>
                                <span>Alunos</span>
                            </a>

                            <a href="{{ route('management.cash.index') }}"
                               class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                      {{ request()->routeIs('management.cash.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                <i class="fas fa-cash-register w-4 text-center"></i>
                                <span>Caixa</span>
                            </a>
                        </div>
                    </div>
                </div>
            @endcan

            {{-- Unit operations panel --}}
            @can('unit.panel')
                <div class="pt-4 mt-4 border-t border-custom">
                    <div class="px-3 py-2 text-xs font-medium text-text-secondary uppercase tracking-wider">
                        Minha unidade
                    </div>

                    <a href="{{ route('unit.dashboard') }}"
                       class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200 group {{ request()->routeIs('unit.*') ? 'text-cyan bg-cyan/10' : '' }}">
                        <i class="fas fa-building-user w-5 text-center group-hover:text-cyan transition-colors"></i>
                        <span>Painel da unidade</span>
                    </a>
                </div>
            @endcan

            {{-- Cadastros --}}
            @can('admin.manage')
                <div class="pt-4 mt-4 border-t border-custom">
                    <div class="px-3 py-2 text-xs font-medium text-text-secondary uppercase tracking-wider">
                        Cadastros
                    </div>

                    <div class="nav-group">
                        <button type="button"
                                class="nav-group-toggle w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200"
                                aria-expanded="false"
                                aria-controls="cadastroSubmenu">
                            <span class="flex items-center gap-3">
                                <i class="fas fa-database w-5 text-center"></i>
                                <span>Cadastros</span>
                            </span>
                            <i class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                        </button>

                        <div id="cadastroSubmenu" class="nav-submenu hidden mt-1 ml-8 space-y-1" role="region" aria-label="Submenu Cadastros">
                            @can('networks.manage')
                                <a href="{{ route('admin.networks.index') }}"
                                   class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                          {{ request()->routeIs('admin.networks.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                     <i class="fas fa-sitemap w-4 text-center"></i>
                                     <span>Redes</span>
                                 </a>
                            @endcan

                            @can('regions.manage')
                                <a href="{{ route('admin.regions.index') }}"
                                   class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                          {{ request()->routeIs('admin.regions.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                     <i class="fas fa-map-marked-alt w-4 text-center"></i>
                                     <span>Regiões</span>
                                 </a>
                            @endcan

                            @can('units.manage')
                                <a href="{{ route('admin.units.index') }}"
                                   class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                          {{ request()->routeIs('admin.units.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                     <i class="fas fa-building w-4 text-center"></i>
                                     <span>Unidades</span>
                                 </a>
                            @endcan

                            @can('people.view')
                                <a href="{{ route('admin.people.index') }}"
                                   class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                          {{ request()->routeIs('admin.people.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                     <i class="fas fa-address-book w-4 text-center"></i>
                                     <span>Pessoas</span>
                                 </a>
                            @endcan

                            @can('roles.manage')
                                <a href="{{ route('admin.roles.index') }}"
                                   class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                          {{ request()->routeIs('admin.roles.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                     <i class="fas fa-user-shield w-4 text-center"></i>
                                     <span>Perfis</span>
                                 </a>
                            @endcan

                            @can('users.manage')
                                <a href="{{ route('admin.users.index') }}"
                                   class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                          {{ request()->routeIs('admin.users.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                     <i class="fas fa-users w-4 text-center"></i>
                                     <span>Usuários</span>
                                 </a>
                            @endcan

                            @unless ($tenantContext->isPlatform())
                                @can('modalities.view')
                                    <a href="{{ route('admin.modalities.index') }}"
                                       class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                              {{ request()->routeIs('admin.modalities.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                         <i class="fas fa-dumbbell w-4 text-center"></i>
                                         <span>Modalidades</span>
                                     </a>
                                @endcan

                                @can('membership_plans.view')
                                    <a href="{{ route('admin.membership-plans.index') }}"
                                       class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                              {{ request()->routeIs('admin.membership-plans.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                         <i class="fas fa-layer-group w-4 text-center"></i>
                                         <span>Planos</span>
                                     </a>
                                @endcan

                                @can('classes.manage')
                                    <a href="{{ route('admin.classes.index') }}"
                                       class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                              {{ request()->routeIs('admin.classes.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                         <i class="fas fa-chalkboard w-4 text-center"></i>
                                         <span>Turmas</span>
                                     </a>
                                @endcan

                                @can('exercises.manage')
                                    <a href="{{ route('admin.exercises.index') }}"
                                       class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                              {{ request()->routeIs('admin.exercises.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                         <i class="fas fa-person-running w-4 text-center"></i>
                                         <span>Exercícios</span>
                                     </a>
                                @endcan

                                @can('workouts.manage')
                                    <a href="{{ route('admin.workout-plans.index') }}"
                                       class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                              {{ request()->routeIs('admin.workout-plans.*') || request()->routeIs('admin.workout-sessions.*') || request()->routeIs('admin.workout-exercises.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                         <i class="fas fa-clipboard-list w-4 text-center"></i>
                                         <span>Treinos</span>
                                     </a>
                                @endcan

                            @endunless

                            @if ($tenantContext->isPlatform())
                                @can('plans.manage')
                                    <a href="{{ route('admin.plans.index') }}"
                                       class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                              {{ request()->routeIs('admin.plans.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                         <i class="fas fa-layer-group w-4 text-center"></i>
                                         <span>Planos SaaS</span>
                                     </a>
                                @endcan
                            @endif
                        </div>
                    </div>
                </div>
            @endcan
        </div>
    </nav>

    {{-- Sidebar Footer --}}
    <div class="p-4 border-t border-custom">
        <div class="flex items-center gap-3 px-3 py-2 rounded-lg bg-surface-elevated">
            <div class="w-8 h-8 rounded-full flex items-center justify-center bg-gradient-to-r from-cyan-400 to-purple-500">
                @if (auth()->user()->avatar)
                    <img src="{{ auth()->user()->avatar }}" alt="" class="w-full h-full rounded-full object-cover">
                @else
                    <span class="text-sm font-bold" style="color: var(--color-bg-primary);">
                        {{ strtoupper(auth()->user()->name[0]) }}
                    </span>
                @endif
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                <p class="text-xs text-text-secondary truncate">{{ auth()->user()->email }}</p>
            </div>
        </div>
    </div>
</aside>

 {{-- Sidebar Overlay for Mobile (outside the aside so it is not affected by its transform) --}}
<div id="sidebarOverlay" class="fixed inset-0 z-[55] bg-black/60 hidden lg:hidden" aria-hidden="true"></div>

<script>
    // Sidebar submenu toggles
    function initSidebarSubmenus() {
        document.querySelectorAll('.nav-group-toggle').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const submenu = document.getElementById(this.getAttribute('aria-controls'));
                const icon = this.querySelector('.fa-chevron-down');
                const isExpanded = this.getAttribute('aria-expanded') === 'true';

                this.setAttribute('aria-expanded', String(!isExpanded));
                submenu?.classList.toggle('hidden', isExpanded);

                if (icon) {
                    icon.style.transform = isExpanded ? 'rotate(0deg)' : 'rotate(180deg)';
                }
            });
        });
    }

    // Executar imediatamente se o DOM já estiver pronto, ou aguardar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarSubmenus);
    } else {
        initSidebarSubmenus();
    }

    // Mobile sidebar close button
    const closeBtn = document.getElementById('sidebarClose');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const sidebarToggle = document.getElementById('sidebarToggle');

    function closeMobileSidebar() {
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.add('hidden');
        document.body.classList.remove('sidebar-open');
        sidebarToggle?.setAttribute('aria-expanded', 'false');
    }

    if (closeBtn && sidebar && overlay) {
        closeBtn.addEventListener('click', closeMobileSidebar);
    }

    overlay?.addEventListener('click', closeMobileSidebar);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar?.classList.contains('open')) {
            closeMobileSidebar();
        }
    });

    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth >= 1024) {
                closeMobileSidebar();
            }
        }, 100);
    });
</script>
