<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Verificar E-mail - {{ config('app.name') }}</title>

    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/favicon-64.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">

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
<body class="bg-body min-h-screen flex items-center justify-center p-4"
      style="background-image: radial-gradient(circle at 15% 10%, color-mix(in srgb, var(--color-primary) 16%, transparent), transparent 38%), radial-gradient(circle at 85% 90%, color-mix(in srgb, var(--color-info) 12%, transparent), transparent 42%);">
    <button type="button"
            id="authThemeToggle"
            class="fixed right-4 top-4 z-20 flex h-10 w-10 items-center justify-center rounded-xl border transition-colors"
            style="background: var(--jw-surface); border-color: var(--color-border); color: var(--color-text-secondary);"
            aria-label="Alternar tema claro/escuro">
        <i class="fas fa-sun" id="authThemeIcon"></i>
    </button>
    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <a href="{{ route('login') }}" class="inline-flex flex-col items-center gap-3">
                <span class="flex h-24 w-24 items-center justify-center rounded-3xl border p-3"
                      style="background: var(--jw-surface); border-color: var(--color-border); box-shadow: 0 0 45px color-mix(in srgb, var(--color-primary) 22%, transparent);">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="h-full w-full object-contain">
                </span>
                <span class="text-3xl font-bold" style="color: var(--color-text-primary);">
                    SmartGym
                </span>
            </a>
            <p class="text-text-secondary mt-2">Verifique seu endereço de e-mail</p>
        </div>

        {{-- Verify Email Card --}}
        <div class="card text-center">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6" style="background: color-mix(in srgb, var(--color-primary) 15%, transparent);">
                <i class="fas fa-envelope-open-text text-3xl" style="color: var(--color-cyan-neon);"></i>
            </div>

            <h1 class="text-xl font-bold mb-2">Verifique seu e-mail</h1>
            <p class="text-text-secondary mb-6">
                Enviamos um link de verificação para <strong>{{ auth()->user()->email }}</strong>.
                Clique no link para ativar sua conta.
            </p>

            <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
                @csrf

                <button type="submit" class="btn-primary w-full py-3">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Reenviar link de verificação
                </button>
            </form>

            <p class="mt-4 text-text-secondary text-sm">
                Não recebeu o e-mail? Verifique sua caixa de spam ou
                <a href="{{ route('login') }}" class="text-cyan hover:underline">faça login novamente</a>.
            </p>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-6 text-xs text-text-secondary">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
        </div>
    </div>
    <script>
        (function () {
            var toggle = document.getElementById('authThemeToggle');
            var icon = document.getElementById('authThemeIcon');

            function syncIcon() {
                var isDark = document.documentElement.classList.contains('dark');

                if (icon) {
                    icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
                }
            }

            toggle?.addEventListener('click', function () {
                var isDark = document.documentElement.classList.toggle('dark');

                try {
                    window.localStorage.setItem('theme', isDark ? 'dark' : 'light');
                } catch (error) {
                    // Ignore storage restrictions.
                }

                syncIcon();
            });

            syncIcon();
        })();
    </script>

</body>
</html>