@php
    $companyName = \App\Support\NetworkTheme::companyName($network);
    $logoUrl = \App\Support\NetworkTheme::logoUrl($network) ?: asset('images/logo.png');
    $themeMode = \App\Support\NetworkTheme::mode($network);
    $themeCss = \App\Support\NetworkTheme::css($network);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" href="{{ $logoUrl }}">
    <link rel="apple-touch-icon" href="{{ $logoUrl }}">

    <script>
        (function () {
            var networkDefault = @json($themeMode);
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = networkDefault === 'dark' || (networkDefault !== 'light' && prefersDark);

            document.documentElement.classList.toggle('dark', isDark);
        })();
    </script>

    <title>Entrar - {{ $companyName }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if ($themeCss)
        <style id="network-theme">{!! $themeCss !!}</style>
    @endif
</head>
<body class="min-h-screen bg-body text-text-primary">
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden p-4 sm:p-6">
        <div class="pointer-events-none absolute inset-0"
             style="background:
                radial-gradient(circle at 12% 8%, color-mix(in srgb, var(--color-primary) 28%, transparent), transparent 34%),
                radial-gradient(circle at 88% 92%, color-mix(in srgb, var(--color-info) 22%, transparent), transparent 38%),
                radial-gradient(circle at 50% 50%, color-mix(in srgb, var(--color-accent) 8%, transparent), transparent 55%);"></div>

        <div class="relative grid w-full max-w-5xl overflow-hidden rounded-3xl border border-custom bg-secondary shadow-2xl lg:grid-cols-[1.05fr_0.95fr]">
            {{-- Brand panel --}}
            <div class="relative hidden flex-col justify-between overflow-hidden p-10 lg:flex"
                 style="background:
                    linear-gradient(145deg, color-mix(in srgb, var(--color-primary) 92%, #000 8%), color-mix(in srgb, var(--color-info) 55%, #000 45%));">
                <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full border border-white/15"></div>
                <div class="pointer-events-none absolute -bottom-24 -left-16 h-72 w-72 rounded-full border border-white/10"></div>

                <div class="relative">
                    <img src="{{ $logoUrl }}" alt="{{ $companyName }}"
                         class="h-20 w-auto max-w-[240px] rounded-2xl bg-white/10 object-contain p-2 backdrop-blur">
                    <p class="mt-6 text-xs font-semibold uppercase tracking-[0.28em] text-white/70">
                        Portal do aluno
                    </p>
                    <h1 class="mt-3 max-w-sm text-4xl font-black leading-tight text-white">
                        Seu treino, sua evolução, no seu ritmo.
                    </h1>
                    <p class="mt-4 max-w-sm text-sm leading-relaxed text-white/75">
                        Acesse seus treinos, acompanhe suas medidas e converse com a equipe da sua unidade.
                    </p>
                </div>

                <div class="relative grid gap-3 text-sm text-white/85">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                            <i class="fas fa-dumbbell"></i>
                        </span>
                        <span>Planos de treino montados para você</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                            <i class="fas fa-chart-line"></i>
                        </span>
                        <span>Evolução de cargas, medidas e frequência</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                            <i class="fas fa-calendar-check"></i>
                        </span>
                        <span>Turmas e aulas da sua unidade</span>
                    </div>
                </div>
            </div>

            {{-- Form panel --}}
            <div class="flex flex-col justify-center bg-secondary p-6 sm:p-10">
                <div class="mb-8 text-center lg:text-left">
                    <div class="mb-5 flex justify-center lg:hidden">
                        <img src="{{ $logoUrl }}" alt="{{ $companyName }}"
                             class="h-20 w-auto max-w-[240px] object-contain">
                    </div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-cyan">Acesso do aluno</p>
                    <h2 class="mt-2 text-3xl font-black tracking-tight">Bem-vindo de volta</h2>
                    <p class="mt-2 text-sm text-text-secondary">
                        Entre com seu e-mail e senha para acessar o portal.
                    </p>
                </div>

                @if ($errors->any())
                    <div class="mb-5 rounded-xl px-4 py-3 text-sm"
                         style="background: var(--color-danger-soft); border: 1px solid var(--color-danger); color: var(--color-danger);">
                        <ul class="list-inside list-disc space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('student.portal.login.store', $network) }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="label-field">E-mail</label>
                        <div class="relative">
                            <i class="fas fa-envelope pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                            <input type="email" id="email" name="email" class="input-field pl-10"
                                   value="{{ old('email') }}" required autocomplete="email" autofocus
                                   placeholder="seu@email.com">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="label-field">Senha</label>
                        <div class="relative">
                            <i class="fas fa-lock pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-text-secondary"></i>
                            <input type="password" id="password" name="password" class="input-field pl-10 pr-12"
                                   required autocomplete="current-password" placeholder="Sua senha">
                            <button type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary transition-colors hover:text-text-primary"
                                    onclick="toggleStudentPassword(this)"
                                    aria-label="Mostrar senha">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <label class="flex cursor-pointer items-center gap-2">
                            <input type="checkbox" name="remember" value="1"
                                   class="h-4 w-4 rounded border-custom bg-bg-secondary text-cyan focus:ring-cyan">
                            <span class="text-sm text-text-secondary">Lembrar-me</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-primary w-full py-3 text-base">
                        <i class="fas fa-right-to-bracket mr-2"></i>
                        Entrar
                    </button>
                </form>

                <div class="mt-7 rounded-xl border border-custom bg-surface-elevated px-4 py-3 text-center text-sm text-text-secondary">
                    Ainda não tem acesso?
                    <a href="{{ route('student.portal.register', $network) }}" class="font-semibold text-cyan hover:underline">
                        Cadastre-se gratuitamente
                    </a>
                </div>

                <p class="mt-6 text-center text-xs text-text-secondary">
                    {{ $companyName }} &middot; Portal do aluno
                </p>
            </div>
        </div>
    </div>

    <script>
        function toggleStudentPassword(button) {
            const input = document.getElementById('password');
            const icon = button.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }
    </script>
</body>
</html>
