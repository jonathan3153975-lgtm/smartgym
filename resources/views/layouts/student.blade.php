@php
    $portalNetwork = $portalNetwork ?? auth('student')->user()?->network;
    $companyName = \App\Support\NetworkTheme::companyName($portalNetwork);
    $logoUrl = \App\Support\NetworkTheme::logoUrl($portalNetwork) ?: asset('images/logo.png');
    $themeMode = \App\Support\NetworkTheme::mode($portalNetwork);
    $themeCss = \App\Support\NetworkTheme::css($portalNetwork);
    $portalAccount = auth('student')->user();
    $portalStudent = $portalAccount?->student;
    $studentName = $portalStudent?->name ?? 'Aluno';
    $mustChangePassword = (bool) ($portalAccount?->must_change_password ?? false);
    $hasActivePlan = ! $mustChangePassword && $portalStudent?->activeSubscription() !== null;
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

    <title>{{ $title ?? 'Portal do aluno' }} - {{ $companyName }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if ($themeCss)
        <style id="network-theme">{!! $themeCss !!}</style>
    @endif
</head>
<body class="bg-primary text-primary min-h-screen">
    <header class="sticky top-0 z-40 border-b border-custom bg-secondary">
        <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <a href="{{ route('student.portal.home', $portalNetwork) }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ $logoUrl }}" alt="{{ $companyName }}"
                     class="h-10 w-10 flex-shrink-0 rounded-xl border border-custom bg-surface-elevated object-contain p-1">
                <span class="min-w-0">
                    <span class="block truncate text-base font-bold">{{ $companyName }}</span>
                    <span class="block text-[0.65rem] uppercase tracking-[0.18em] text-text-secondary">Portal do aluno</span>
                </span>
            </a>

            <div class="flex items-center justify-between gap-4 sm:justify-end">
                <nav class="flex items-center gap-1" aria-label="Menu do aluno">
                    @unless ($mustChangePassword)
                        <a href="{{ route('student.portal.home', $portalNetwork) }}"
                           class="rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('student.portal.home') ? 'bg-surface text-cyan' : 'text-text-secondary hover:bg-surface-elevated hover:text-text-primary' }}">
                            <i class="fas fa-house mr-1.5"></i>
                            Home
                        </a>
                        <a href="{{ route('student.portal.plans', $portalNetwork) }}"
                           class="rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('student.portal.plans') ? 'bg-surface text-cyan' : 'text-text-secondary hover:bg-surface-elevated hover:text-text-primary' }}">
                            <i class="fas fa-layer-group mr-1.5"></i>
                            Planos
                        </a>
                        <a href="{{ route('student.portal.classes.index', $portalNetwork) }}"
                           class="rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('student.portal.classes.*') ? 'bg-surface text-cyan' : 'text-text-secondary hover:bg-surface-elevated hover:text-text-primary' }}">
                            <i class="fas fa-calendar-days mr-1.5"></i>
                            Turmas
                        </a>
                        @if ($hasActivePlan)
                            <a href="{{ route('student.portal.assessment.show', $portalNetwork) }}"
                               class="rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('student.portal.assessment.*') ? 'bg-surface text-cyan' : 'text-text-secondary hover:bg-surface-elevated hover:text-text-primary' }}">
                                <i class="fas fa-clipboard-check mr-1.5"></i>
                                Avaliação
                            </a>
                            <a href="{{ route('student.portal.workouts.index', $portalNetwork) }}"
                               class="rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('student.portal.workouts.*') ? 'bg-surface text-cyan' : 'text-text-secondary hover:bg-surface-elevated hover:text-text-primary' }}">
                                <i class="fas fa-dumbbell mr-1.5"></i>
                                Treinos
                            </a>
                            <a href="{{ route('student.portal.measurements.index', $portalNetwork) }}"
                               class="rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('student.portal.measurements.*') ? 'bg-surface text-cyan' : 'text-text-secondary hover:bg-surface-elevated hover:text-text-primary' }}">
                                <i class="fas fa-weight-scale mr-1.5"></i>
                                Medidas
                            </a>
                        @endif
                        <a href="{{ route('student.portal.dashboard', $portalNetwork) }}"
                           class="rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('student.portal.dashboard') ? 'bg-surface text-cyan' : 'text-text-secondary hover:bg-surface-elevated hover:text-text-primary' }}">
                            <i class="fas fa-chart-pie mr-1.5"></i>
                            Dashboard
                        </a>
                    @endunless
                </nav>

                <div class="flex items-center gap-2 border-l border-custom pl-3">
                    <span class="hidden max-w-[10rem] truncate text-sm font-medium sm:inline">{{ $studentName }}</span>
                    <form action="{{ route('student.portal.logout', $portalNetwork) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-red"
                                title="Sair">
                            <i class="fas fa-arrow-right-from-bracket"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 sm:py-8">
        @if (session('success'))
            <div class="mb-4 rounded-lg px-4 py-3 text-sm"
                 style="background: var(--color-success-soft); border: 1px solid var(--color-success); color: var(--color-success);">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 rounded-lg px-4 py-3 text-sm"
                 style="background: var(--color-danger-soft); border: 1px solid var(--color-danger); color: var(--color-danger);">
                <ul class="list-inside list-disc space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="mx-auto w-full max-w-6xl px-4 pb-6 text-center text-xs text-text-secondary sm:px-6">
        &copy; {{ date('Y') }} {{ $companyName }} &middot; Portal do aluno
    </footer>
</body>
</html>
