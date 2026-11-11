<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Login - {{ config('app.name') }}</title>

    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/favicon-64.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">

    {{-- Apply the saved/system theme before rendering to avoid flashes --}}
    <script>
        (function () {
            var savedTheme = null;

            try {
                savedTheme = window.localStorage.getItem('theme');
            } catch (error) {
                // Ignore storage restrictions.
            }

            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = savedTheme ? savedTheme === 'dark' : prefersDark;

            document.documentElement.classList.toggle('dark', isDark);
        })();
    </script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="login-body bg-body min-h-screen"
      style="background-image: radial-gradient(circle at 15% 10%, color-mix(in srgb, var(--color-primary) 16%, transparent), transparent 38%), radial-gradient(circle at 85% 90%, color-mix(in srgb, var(--color-info) 12%, transparent), transparent 42%);">
    {{-- Theme switch is only relevant on mobile; desktop uses the fixed split layout --}}
    <button type="button"
            id="loginThemeToggle"
            class="fixed right-4 top-4 z-20 flex h-10 w-10 items-center justify-center rounded-xl border transition-colors lg:hidden"
            style="background: var(--jw-surface); border-color: var(--color-border); color: var(--color-text-secondary);"
            aria-label="Alternar tema claro/escuro">
        <i class="fas fa-sun" id="loginThemeIcon"></i>
    </button>

    {{-- ==========================================================
         MOBILE (mantém o layout atual, apenas com o logo)
         ========================================================== --}}
    <div class="flex min-h-screen w-full items-center justify-center p-4 lg:hidden">
        <div class="w-full max-w-md">
            <div class="mb-10 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center justify-center">
                    <img src="{{ asset('images/logo.png') }}"
                         alt="{{ config('app.name') }}"
                         class="h-28 w-auto max-w-full object-contain sm:h-32">
                </a>
            </div>

            @include('pages.auth._login-form')

            <div class="text-center mt-6 text-xs text-text-secondary">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
            </div>
        </div>
    </div>

    {{-- ==========================================================
         DESKTOP (70% painel azul / 30% card de login)
         ========================================================== --}}
    <div class="login-desktop hidden min-h-screen w-full items-stretch lg:flex">
        @php
            $loginFeatures = [
                ['icon' => 'fa-network-wired', 'title' => 'Multi-rede e multi-unidade', 'text' => 'Redes, regiões e unidades isoladas.'],
                ['icon' => 'fa-users', 'title' => 'Pessoas e alunos', 'text' => 'Cadastro único com vínculos.'],
                ['icon' => 'fa-user-shield', 'title' => 'Permissões por escopo', 'text' => 'Acesso calculado por vínculo.'],
                ['icon' => 'fa-layer-group', 'title' => 'Planos e modalidades', 'text' => 'Configuração por rede.'],
                ['icon' => 'fa-palette', 'title' => 'White-label', 'text' => 'Tema e logo por rede.'],
                ['icon' => 'fa-shield-halved', 'title' => 'Auditoria e segurança', 'text' => 'Rastreabilidade das ações.'],
            ];
        @endphp

        {{-- Painel azul escuro — 70% e tela inteira --}}
        <section class="login-info-panel relative z-10 flex w-[70%] shrink-0 flex-col px-10 py-10 xl:px-16 xl:py-12">
            <div class="mx-auto flex w-full max-w-4xl flex-1 flex-col">
                <div class="flex items-center justify-between gap-4">
                    <img src="{{ asset('images/logo.png') }}"
                         alt="{{ config('app.name') }}"
                         class="h-28 w-auto max-w-[300px] object-contain xl:h-32"
                         style="filter: drop-shadow(0 14px 30px rgba(0, 212, 255, 0.22));">
                    <span class="hidden rounded-full border border-white/15 bg-white/5 px-3 py-1 text-[0.6rem] font-semibold uppercase tracking-[0.18em] text-white/70 xl:inline-flex">
                        Plataforma SaaS
                    </span>
                </div>

                <div class="mt-10 xl:mt-14">
                    <h2 class="max-w-2xl text-3xl font-bold leading-[1.15] text-white xl:text-[2.7rem]">
                        Toda a sua rede de academias em um só lugar.
                    </h2>
                    <p class="mt-4 max-w-xl text-sm leading-relaxed text-white/60 xl:text-base">
                        Centralize alunos, equipe, unidades, planos e indicadores com isolamento por rede, permissões por escopo e identidade visual própria.
                    </p>
                </div>

                <ul class="mt-8 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($loginFeatures as $feature)
                        <li class="flex items-start gap-3 rounded-xl border border-white/10 bg-white/[0.035] px-3.5 py-3">
                            <span class="mt-0.5 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg"
                                  style="background: rgba(0, 212, 255, 0.12); color: #00D4FF;">
                                <i class="fas {{ $feature['icon'] }} text-[0.7rem]"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-white">{{ $feature['title'] }}</span>
                                <span class="mt-0.5 block text-[0.7rem] leading-snug text-white/50">{{ $feature['text'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-auto flex items-center justify-between gap-4 pt-8 text-[0.65rem] text-white/35">
                    <span>&copy; {{ date('Y') }} {{ config('app.name') }} &middot; JW SmartG-code</span>
                    <span class="inline-flex items-center gap-1.5">
                        <i class="fas fa-lock text-[0.55rem]"></i>
                        Ambiente seguro
                    </span>
                </div>
            </div>
        </section>

        {{-- Card de login — 30% e tela inteira --}}
        <section class="login-form-panel relative flex min-h-screen w-[30%] items-center justify-center px-6 py-10 xl:px-10">
            <div class="login-form-scope w-full max-w-sm">
                <p class="mb-3 text-center text-[0.62rem] font-semibold uppercase tracking-[0.24em]" style="color: #0D6EFD;">
                    Acesso seguro
                </p>

                @include('pages.auth._login-form')
            </div>
        </section>
    </div>

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        const loginThemeToggle = document.getElementById('loginThemeToggle');
        const loginThemeIcon = document.getElementById('loginThemeIcon');

        function syncLoginThemeIcon() {
            const isDark = document.documentElement.classList.contains('dark');

            if (loginThemeIcon) {
                loginThemeIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }
        }

        loginThemeToggle?.addEventListener('click', function () {
            const isDark = document.documentElement.classList.toggle('dark');

            try {
                window.localStorage.setItem('theme', isDark ? 'dark' : 'light');
            } catch (error) {
                // Ignore storage restrictions.
            }

            syncLoginThemeIcon();
        });

        syncLoginThemeIcon();
    </script>
</body>
</html>
