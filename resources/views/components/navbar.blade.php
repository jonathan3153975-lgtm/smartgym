<header id="navbar" class="navbar flex items-center justify-between px-4">
    {{-- ============================================
         LEFT SECTION: Sidebar Toggle (mobile only)
         Desktop: sidebar is always fixed and visible
         Mobile: opens the off-canvas sidebar
         ============================================ --}}
    <button
        id="sidebarToggle"
        class="lg:hidden p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary flex-shrink-0"
        aria-label="Alternar menu lateral"
        aria-expanded="false"
        aria-controls="sidebar"
    >
        <i class="fas fa-bars text-xl"></i>
    </button>

    {{-- ============================================
         CENTER SECTION: Global Search (takes remaining space)
         Desktop: max-w-2xl centered | Mobile: full width between buttons
         ============================================ --}}
    <div class="flex-1 min-w-0 lg:max-w-2xl lg:mx-auto px-4">
        {{-- Global Search --}}
        <div class="relative w-full">
                <button
                    type="button"
                    id="globalSearchBtn"
                    class="w-full px-4 py-2 rounded-lg bg-surface-elevated border border-custom text-text-secondary placeholder-text-secondary transition-all duration-200 flex items-center gap-2 justify-between"
                    aria-label="Busca global"
                >
                    <span class="flex items-center gap-2 flex-1 min-w-0">
                        <i class="fas fa-search text-text-secondary flex-shrink-0"></i>
                        <span class="truncate">Buscar alunos, unidades, usuários...</span>
                    </span>
                    <kbd class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-bg-primary border border-custom text-text-secondary flex-shrink-0">
                        <kbd class="bg-bg-secondary px-1.5 py-0.5 rounded">⌘</kbd>
                        <kbd class="bg-bg-secondary px-1.5 py-0.5 rounded">K</kbd>
                    </kbd>
                </button>

                {{-- Search Dropdown --}}
                <div id="globalSearchDropdown"
                     class="hidden absolute top-full left-0 right-0 mt-2 rounded-xl border border-custom bg-surface-elevated shadow-lg overflow-hidden z-50"
                     role="listbox"
                     aria-label="Resultados da busca">
                    <div class="p-3 border-b border-custom">
                        <input type="text"
                               id="globalSearchInput"
                               class="w-full px-3 py-2 rounded-lg bg-bg-secondary border border-custom text-text-primary placeholder-text-secondary focus:border-cyan focus:ring-2 focus:ring-cyan/20"
                               placeholder="Digite para buscar..."
                               autocomplete="off"
                               aria-label="Termo de busca">
                    </div>
                    <div id="globalSearchResults" class="max-h-96 overflow-y-auto">
                        <div class="p-4 text-center text-text-secondary">
                            <i class="fas fa-search text-2xl mb-2 block"></i>
                            Digite algo para buscar...
                        </div>
                    </div>
                </div>
            </div>
    </div>

    {{-- ============================================
         RIGHT SECTION: Actions Dropdown (Notifications, Theme, User)
         Single button with ellipsis icon on ALL screens
         ============================================ --}}
    <div class="flex items-center gap-2 flex-shrink-0">
        <div class="relative" id="actionsMenuContainer">
            <button
                type="button"
                id="actionsMenuBtn"
                class="flex items-center gap-2 p-2 rounded-lg hover:bg-surface-elevated transition-colors text-text-secondary"
                aria-expanded="false"
                aria-haspopup="true"
                aria-controls="actionsMenu"
                aria-label="Ações e perfil"
            >
                <i class="fas fa-ellipsis-v text-xl"></i>
            </button>

            {{-- Actions Dropdown Menu --}}
            <div id="actionsMenu"
                 class="hidden absolute right-0 top-full mt-2 w-64 rounded-xl border border-custom bg-surface-elevated shadow-lg overflow-hidden z-50"
                 role="menu"
                 aria-orientation="vertical">
                
                {{-- Notifications --}}
                <div class="p-3 border-b border-custom flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background: color-mix(in srgb, var(--color-danger) 15%, transparent);">
                        <i class="fas fa-bell text-red text-lg"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate">Notificações</p>
                        <p class="text-xs text-text-secondary truncate">3 novas notificações</p>
                    </div>
                    <span class="w-4 h-4 rounded-full flex items-center justify-center text-xs font-bold" style="background: var(--color-red); color: white;">3</span>
                </div>
                
                <a href="#" class="flex items-center gap-2 px-3 py-2 text-sm text-text-secondary hover:text-text-primary hover:bg-surface transition-colors" role="menuitem">
                    <i class="fas fa-bell w-4 text-center"></i>
                    Ver todas
                </a>

                <hr class="my-1 border-custom">

                {{-- Theme Toggle --}}
                <button
                    type="button"
                    id="themeToggleDropdown"
                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-text-secondary hover:text-text-primary hover:bg-surface transition-colors"
                    role="menuitem"
                    aria-label="Alternar tema"
                >
                    <i class="fas fa-moon w-4 text-center" id="themeToggleIcon"></i>
                    <span id="themeToggleLabel">Tema claro</span>
                </button>

                <hr class="my-1 border-custom">

                {{-- User Info --}}
                <div class="p-3 border-b border-custom">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center bg-gradient-to-r from-cyan-400 to-purple-500 flex-shrink-0">
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

                <div class="py-1">
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-2 px-3 py-2 text-sm text-text-secondary hover:text-text-primary hover:bg-surface transition-colors"
                       role="menuitem">
                        <i class="fas fa-user w-4 text-center"></i>
                        Meu Perfil
                    </a>
                    <a href="{{ route('profile.edit') }}"
                       class="flex items-center gap-2 px-3 py-2 text-sm text-text-secondary hover:text-text-primary hover:bg-surface transition-colors"
                       role="menuitem">
                        <i class="fas fa-key w-4 text-center"></i>
                        Alterar Senha
                    </a>

                    @can('admin.manage')
                        <hr class="my-1 border-custom">
                        <a href="{{ route('admin.dashboard') }}"
                           class="flex items-center gap-2 px-3 py-2 text-sm text-text-secondary hover:text-text-primary hover:bg-surface transition-colors"
                           role="menuitem">
                            <i class="fas fa-cogs w-4 text-center"></i>
                            Painel Admin
                        </a>
                    @endcan

                    <hr class="my-1 border-custom">
                    <form action="{{ route('logout') }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit"
                                class="flex items-center gap-2 w-full px-3 py-2 text-sm text-red hover:bg-red/10 transition-colors"
                                role="menuitem">
                            <i class="fas fa-sign-out-alt w-4 text-center"></i>
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // ============================================
        // SIDEBAR TOGGLE (mobile only)
        // ============================================
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        function openSidebar() {
            sidebar?.classList.add('open');
            overlay?.classList.remove('hidden');
            document.body.classList.add('sidebar-open');
            sidebarToggle?.setAttribute('aria-expanded', 'true');
        }

        function closeSidebar() {
            sidebar?.classList.remove('open');
            overlay?.classList.add('hidden');
            document.body.classList.remove('sidebar-open');
            sidebarToggle?.setAttribute('aria-expanded', 'false');
        }

        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function(e) {
                e.stopPropagation();

                if (sidebar.classList.contains('open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });
        }

        overlay?.addEventListener('click', closeSidebar);

        // Close sidebar on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar?.classList.contains('open')) {
                closeSidebar();
            }
        });

        // Desktop shows the sidebar permanently; reset any mobile state when resizing
        let resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (window.innerWidth >= 1024) {
                    closeSidebar();
                }
            }, 100);
        });

        // ============================================
        // ACTIONS MENU (Notifications, Theme, User)
        // ============================================
        const actionsMenuBtn = document.getElementById('actionsMenuBtn');
        const actionsMenu = document.getElementById('actionsMenu');
        const actionsMenuContainer = document.getElementById('actionsMenuContainer');

        if (actionsMenuBtn && actionsMenu) {
            actionsMenuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', !isExpanded);
                actionsMenu.classList.toggle('hidden');
            });
        }

        // Close actions menu when clicking outside
        document.addEventListener('click', function(e) {
            if (actionsMenuContainer && !actionsMenuContainer.contains(e.target)) {
                actionsMenuBtn?.setAttribute('aria-expanded', 'false');
                actionsMenu?.classList.add('hidden');
            }
        });

        // ============================================
        // GLOBAL SEARCH
        // ============================================
        const globalSearchBtn = document.getElementById('globalSearchBtn');
        const globalSearchDropdown = document.getElementById('globalSearchDropdown');
        const globalSearchInput = document.getElementById('globalSearchInput');

        if (globalSearchBtn && globalSearchDropdown) {
            globalSearchBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                globalSearchDropdown.classList.toggle('hidden');
                if (!globalSearchDropdown.classList.contains('hidden')) {
                    globalSearchInput?.focus();
                }
            });
        }

        // Close global search when clicking outside
        document.addEventListener('click', function(e) {
            if (globalSearchDropdown && !globalSearchDropdown.contains(e.target) && !globalSearchBtn?.contains(e.target)) {
                globalSearchDropdown.classList.add('hidden');
            }
        });

        // Keyboard shortcut: Cmd/Ctrl + K for global search
        document.addEventListener('keydown', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                globalSearchBtn?.click();
            }
        });

        // Live results for the global search
        const globalSearchResults = document.getElementById('globalSearchResults');
        const globalSearchUrl = @json(route('global.search'));
        let globalSearchTimer = null;
        let globalSearchRequest = null;

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, function(char) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char];
            });
        }

        function renderSearchResults(html) {
            if (globalSearchResults) {
                globalSearchResults.innerHTML = html;
            }
        }

        globalSearchInput?.addEventListener('input', function() {
            const query = this.value.trim();

            clearTimeout(globalSearchTimer);
            globalSearchRequest?.abort();

            if (query.length < 2) {
                renderSearchResults('<div class="p-4 text-center text-text-secondary"><i class="fas fa-search mb-2 block text-2xl"></i>Digite ao menos 2 caracteres...</div>');
                return;
            }

            globalSearchTimer = setTimeout(function() {
                globalSearchRequest = new AbortController();

                renderSearchResults('<div class="p-4 text-center text-text-secondary"><i class="fas fa-spinner fa-spin mr-2"></i>Buscando...</div>');

                fetch(globalSearchUrl + '?q=' + encodeURIComponent(query), {
                    headers: { 'Accept': 'application/json' },
                    signal: globalSearchRequest.signal,
                })
                    .then(function(response) {
                        return response.ok ? response.json() : Promise.reject(new Error('search_failed'));
                    })
                    .then(function(data) {
                        const results = data.results || [];

                        if (results.length === 0) {
                            renderSearchResults('<div class="p-4 text-center text-text-secondary">Nenhum resultado encontrado.</div>');
                            return;
                        }

                        let html = '<ul class="divide-y divide-custom">';

                        results.forEach(function(result) {
                            const icon = String(result.icon || 'fa-circle').replace(/[^a-z0-9-]/gi, '');
                            let actions = '';

                            (result.actions || []).forEach(function(action) {
                                actions += '<a href="' + escapeHtml(action.url) + '" class="text-xs font-semibold text-cyan hover:underline">'
                                    + escapeHtml(action.label) + '</a>';
                            });

                            html += '<li class="flex items-center gap-3 px-3 py-2.5 transition-colors hover:bg-surface">'
                                + '<a href="' + escapeHtml(result.url) + '" class="flex min-w-0 flex-1 items-center gap-3">'
                                + '<span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-surface text-cyan"><i class="fas ' + icon + '"></i></span>'
                                + '<span class="min-w-0">'
                                + '<span class="block truncate text-sm font-medium">' + escapeHtml(result.label) + '</span>'
                                + '<span class="block truncate text-xs text-text-secondary">' + escapeHtml(result.subtitle) + '</span>'
                                + '</span>'
                                + '</a>'
                                + (actions ? '<span class="flex flex-shrink-0 items-center gap-3">' + actions + '</span>' : '')
                                + '</li>';
                        });

                        renderSearchResults(html + '</ul>');
                    })
                    .catch(function(error) {
                        if (error && error.name === 'AbortError') {
                            return;
                        }

                        renderSearchResults('<div class="p-4 text-center text-text-secondary">Não foi possível buscar agora.</div>');
                    });
            }, 250);
        });

        // ============================================
        // THEME TOGGLE (in dropdown)
        // ============================================
        function initThemeToggle(toggleId, iconId) {
            const themeToggle = document.getElementById(toggleId);
            const themeIcon = document.getElementById(iconId);
            const themeLabel = document.getElementById('themeToggleLabel');
            
            if (!themeToggle || !themeIcon) return;

            const applyThemeState = (isDark) => {
                if (isDark) {
                    themeIcon.classList.replace('fa-moon', 'fa-sun');
                    if (themeLabel) themeLabel.textContent = 'Tema escuro';
                } else {
                    themeIcon.classList.replace('fa-sun', 'fa-moon');
                    if (themeLabel) themeLabel.textContent = 'Tema claro';
                }
            };

            applyThemeState(document.documentElement.classList.contains('dark'));

            themeToggle.addEventListener('click', function() {
                const isCurrentlyDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('theme', isCurrentlyDark ? 'dark' : 'light');
                applyThemeState(isCurrentlyDark);
                
                // Close dropdown after theme change
                actionsMenu?.classList.add('hidden');
                actionsMenuBtn?.setAttribute('aria-expanded', 'false');
            });
        }

        // Initialize theme toggle in dropdown
        initThemeToggle('themeToggleDropdown', 'themeToggleIcon');
    });
</script>