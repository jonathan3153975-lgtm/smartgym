@php
    $brandLogo = \App\Support\NetworkTheme::logoUrl($activeNetwork ?? null);
    $brandName = \App\Support\NetworkTheme::companyName($activeNetwork ?? null);
@endphp

<aside id="sidebar" class="sidebar flex flex-col">
    {{-- Logo/Brand --}}
    <div class="flex items-center justify-between h-16 px-4 border-b border-custom">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 min-w-0">
            @if ($brandLogo)
                <img src="{{ $brandLogo }}"
                     alt="{{ $brandName }}"
                     class="w-10 h-10 rounded-xl object-contain flex-shrink-0 bg-surface-elevated p-1 border border-custom">
            @else
                <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background: linear-gradient(135deg, var(--color-cyan-neon), var(--color-purple-neon));">
                    <i class="fas fa-dumbbell text-xl" style="color: var(--color-bg-primary);"></i>
                </div>
            @endif
            <span class="text-xl font-bold bg-clip-text text-transparent truncate"
                  style="background-image: linear-gradient(90deg, var(--color-cyan-neon), var(--color-purple-neon));">
                {{ $brandName }}
            </span>
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

            {{-- Students Module --}}
            <div class="nav-group">
                <button type="button"
                        class="nav-group-toggle w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200"
                        aria-expanded="false"
                        aria-controls="studentsSubmenu">
                    <span class="flex items-center gap-3">
                        <i class="fas fa-user-graduate w-5 text-center"></i>
                        <span>Alunos</span>
                    </span>
                    <i class="fas fa-chevron-down text-xs transition-transform duration-200"></i>
                </button>

                <div id="studentsSubmenu" class="nav-submenu hidden mt-1 ml-8 space-y-1" role="region" aria-label="Submenu Alunos">
                    <a href="{{ route('students.index') }}"
                       class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200"
                       {{ request()->routeIs('students.*') ? 'aria-current="page"' : '' }}>
                        <i class="fas fa-list w-4 text-center"></i>
                        <span>Listar Alunos</span>
                    </a>
                    <a href="{{ route('students.create') }}"
                       class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200"
                       {{ request()->routeIs('students.create') ? 'aria-current="page"' : '' }}>
                        <i class="fas fa-plus w-4 text-center"></i>
                        <span>Novo Aluno</span>
                    </a>
                    <a href="{{ route('students.inactive') }}"
                       class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200"
                       {{ request()->routeIs('students.inactive') ? 'aria-current="page"' : '' }}>
                        <i class="fas fa-user-slash w-4 text-center"></i>
                        <span>Inativos</span>
                    </a>
                </div>
            </div>

            {{-- Network administration --}}
            @can('network.settings')
                @php($managementNetwork = auth()->user()->getActiveVinculo()?->network)
                @if ($managementNetwork)
                    <div class="pt-4 mt-4 border-t border-custom">
                        <div class="px-3 py-2 text-xs font-medium text-text-secondary uppercase tracking-wider">
                            Minha rede
                        </div>

                        <div class="space-y-1">
                            <a href="{{ route('admin.dashboard') }}"
                               class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200 group">
                                <i class="fas fa-chart-pie w-5 text-center group-hover:text-cyan transition-colors"></i>
                                <span>Painel da rede</span>
                            </a>

                            <a href="{{ route('admin.networks.show', $managementNetwork) }}"
                               class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200 group">
                                <i class="fas fa-diagram-project w-5 text-center group-hover:text-cyan transition-colors"></i>
                                <span>Visão da rede</span>
                            </a>

                            <a href="{{ route('admin.network.settings.edit') }}"
                               class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-lg text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200 group">
                                <i class="fas fa-gear w-5 text-center group-hover:text-cyan transition-colors"></i>
                                <span>Configurações</span>
                            </a>
                        </div>
                    </div>
                @endif
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

                            @can('users.manage')
                                <a href="{{ route('admin.users.index') }}"
                                   class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                          {{ request()->routeIs('admin.users.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                     <i class="fas fa-users w-4 text-center"></i>
                                     <span>Usuários</span>
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

                            @can('plans.manage')
                                <a href="{{ route('admin.plans.index') }}"
                                   class="nav-subitem flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-text-secondary hover:text-text-primary hover:bg-surface-elevated transition-all duration-200
                                          {{ request()->routeIs('admin.plans.*') ? 'text-cyan bg-cyan/10' : '' }}">
                                     <i class="fas fa-layer-group w-4 text-center"></i>
                                     <span>Planos</span>
                                 </a>
                            @endcan
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