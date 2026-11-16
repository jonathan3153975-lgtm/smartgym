@php
    $portalNetwork = $portalNetwork ?? auth('student')->user()?->network;
    $companyName = \App\Support\NetworkTheme::companyName($portalNetwork);
    $logoUrl = \App\Support\NetworkTheme::logoUrl($portalNetwork) ?: asset('images/logo.png');
    $themeMode = \App\Support\NetworkTheme::mode($portalNetwork);
    $themeCss = \App\Support\NetworkTheme::css($portalNetwork);
    $portalAccount = auth('student')->user();
    $portalStudent = $portalAccount?->student;
    $studentName = $portalStudent?->name ?? 'Aluno';
    $studentAvatar = $portalStudent?->avatar_url;
    $studentInitial = strtoupper(mb_substr($studentName, 0, 1));
    $primaryUnitName = null;

    if ($portalStudent) {
        $portalStudent->loadMissing('units.unit');
        $primaryUnitName = ($portalStudent->units->firstWhere('is_primary') ?? $portalStudent->units->first())?->unit?->name;
    }

    $portalUnitName = $primaryUnitName ?: $companyName;
    $mustChangePassword = (bool) ($portalAccount?->must_change_password ?? false);
    $hasActivePlan = ! $mustChangePassword && $portalStudent?->activeSubscription() !== null;
    $pendingChargeCount = $mustChangePassword || ! $portalStudent
        ? 0
        : (int) $portalStudent->charges()->where('status', 'pending')->count();

    $portalNavigation = $mustChangePassword ? [] : array_values(array_filter([
        ['route' => 'student.portal.home', 'active' => 'student.portal.home', 'icon' => 'fa-house', 'label' => 'Home'],
        ['route' => 'student.portal.dashboard', 'active' => 'student.portal.dashboard', 'icon' => 'fa-chart-pie', 'label' => 'Dashboard'],
        $hasActivePlan ? ['route' => 'student.portal.assessment.show', 'active' => 'student.portal.assessment.*', 'icon' => 'fa-clipboard-check', 'label' => 'Avaliação'] : null,
        $hasActivePlan ? ['route' => 'student.portal.workouts.index', 'active' => 'student.portal.workouts.*', 'icon' => 'fa-dumbbell', 'label' => 'Treinos'] : null,
        ['route' => 'student.portal.classes.index', 'active' => 'student.portal.classes.*', 'icon' => 'fa-calendar-days', 'label' => 'Turmas'],
        ['separator' => true],
        ['route' => 'student.portal.finance.index', 'active' => 'student.portal.finance.*', 'icon' => 'fa-receipt', 'label' => 'Financeiro', 'badge' => $pendingChargeCount],
        ['route' => 'student.portal.plans', 'active' => 'student.portal.plans', 'icon' => 'fa-layer-group', 'label' => 'Planos'],
        ['route' => 'student.portal.profile', 'active' => 'student.portal.profile', 'icon' => 'fa-user', 'label' => 'Meus dados'],
    ]));
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
    <header class="fixed inset-x-0 top-0 z-50 border-b border-custom bg-secondary">
        <div class="flex h-16 items-center gap-3 px-4 sm:px-6">
            <button type="button"
                    class="rounded-lg p-2 text-text-secondary transition-colors hover:bg-surface-elevated hover:text-text-primary lg:hidden"
                    data-portal-menu-toggle
                    aria-controls="portalSidebar"
                    aria-expanded="false"
                    aria-label="Abrir menu">
                <i class="fas fa-bars text-lg"></i>
            </button>

            <a href="{{ route('student.portal.home', $portalNetwork) }}" class="flex min-w-0 items-center gap-3">
                @if ($studentAvatar)
                    <img src="{{ $studentAvatar }}" alt="{{ $studentName }}"
                         class="h-10 w-10 flex-shrink-0 rounded-full border border-custom object-cover">
                @else
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold"
                          style="background: var(--color-info-soft); color: var(--color-info);">
                        {{ $studentInitial }}
                    </span>
                @endif

                <span class="min-w-0">
                    <span class="block truncate text-base font-bold">{{ $portalUnitName }}</span>
                    <span class="block truncate text-[0.65rem] uppercase tracking-[0.18em] text-text-secondary">{{ $studentName }}</span>
                </span>
            </a>

            <div class="ml-auto flex items-center gap-2">
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
    </header>

    <div id="portalMenuOverlay" class="fixed inset-0 z-30 hidden bg-black/60 lg:hidden" data-portal-menu-close></div>

    <aside id="portalSidebar"
           class="fixed bottom-0 left-0 top-16 z-40 w-72 max-w-[85vw] -translate-x-full overflow-y-auto border-r border-custom bg-secondary transition-transform duration-200 ease-out lg:translate-x-0"
           aria-label="Menu do aluno">
        <nav class="space-y-1 p-3">
            @foreach ($portalNavigation as $item)
                @if (! empty($item['separator']))
                    <div class="my-2 border-t border-custom" role="separator"></div>
                    @continue
                @endif

                @php($isActive = request()->routeIs($item['active']))
                <a href="{{ route($item['route'], $portalNetwork) }}"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors {{ $isActive ? 'bg-surface text-cyan' : 'text-text-secondary hover:bg-surface-elevated hover:text-text-primary' }}"
                   @if ($isActive) aria-current="page" @endif>
                    <i class="fas {{ $item['icon'] }} w-5 text-center"></i>
                    <span class="flex-1">{{ $item['label'] }}</span>
                    @if (! empty($item['badge']))
                        <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[0.65rem] font-bold text-white"
                              style="background: var(--color-warning);">
                            {{ $item['badge'] }}
                        </span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="mt-2 border-t border-custom p-3">
            <div class="flex items-center gap-3 rounded-xl bg-surface-elevated px-3 py-2.5">
                @if ($studentAvatar)
                    <img src="{{ $studentAvatar }}" alt="{{ $studentName }}"
                         class="h-9 w-9 flex-shrink-0 rounded-full border border-custom object-cover">
                @else
                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold"
                          style="background: var(--color-info-soft); color: var(--color-info);">
                        {{ $studentInitial }}
                    </span>
                @endif
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold">{{ $studentName }}</p>
                    <p class="truncate text-[0.65rem] uppercase tracking-wider text-text-secondary">{{ $portalUnitName }}</p>
                </div>
            </div>
        </div>
    </aside>

    <main class="min-h-screen px-4 pb-12 pt-20 sm:px-6 sm:pt-24 lg:pl-80">
        <div class="mx-auto w-full max-w-6xl space-y-4">
            @if (session('success'))
                <div class="rounded-lg px-4 py-3 text-sm"
                     style="background: var(--color-success-soft); border: 1px solid var(--color-success); color: var(--color-success);">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="rounded-lg px-4 py-3 text-sm"
                     style="background: var(--color-danger-soft); border: 1px solid var(--color-danger); color: var(--color-danger);">
                    <ul class="list-inside list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    <footer class="px-4 pb-6 text-center text-xs text-text-secondary lg:pl-80">
        &copy; {{ date('Y') }} {{ $companyName }} &middot; Portal do aluno
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        (function () {
            const sidebar = document.getElementById('portalSidebar');
            const overlay = document.getElementById('portalMenuOverlay');
            const toggle = document.querySelector('[data-portal-menu-toggle]');
            const closeButtons = document.querySelectorAll('[data-portal-menu-close]');

            if (!sidebar || !overlay || !toggle) {
                return;
            }

            function openMenu() {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                toggle.setAttribute('aria-expanded', 'true');
            }

            function closeMenu() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                toggle.setAttribute('aria-expanded', 'false');
            }

            toggle.addEventListener('click', function () {
                if (sidebar.classList.contains('-translate-x-full')) {
                    openMenu();
                } else {
                    closeMenu();
                }
            });

            closeButtons.forEach(function (button) {
                button.addEventListener('click', closeMenu);
            });

            sidebar.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', closeMenu);
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeMenu();
                }
            });
        })();
    </script>
</body>
</html>
