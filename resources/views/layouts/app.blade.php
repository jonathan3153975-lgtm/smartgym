@php
    $themeNetwork = $activeNetwork ?? (auth()->check() ? auth()->user()->getActiveVinculo()?->network : null);
    $themeMode = \App\Support\NetworkTheme::mode($themeNetwork);
    $themeCss = \App\Support\NetworkTheme::css($themeNetwork);
    $companyName = \App\Support\NetworkTheme::companyName($themeNetwork);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" type="image/png" sizes="64x64" href="{{ asset('images/favicon-64.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/apple-touch-icon.png') }}">

    {{-- Apply the saved/network theme before rendering to avoid flashes --}}
    <script>
        (function () {
            var savedTheme = null;

            try {
                savedTheme = window.localStorage.getItem('theme');
            } catch (error) {
                // Ignore storage restrictions.
            }

            var networkDefault = @json($themeMode);
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = savedTheme
                ? savedTheme === 'dark'
                : (networkDefault === 'dark' || (networkDefault !== 'light' && prefersDark));

            document.documentElement.classList.toggle('dark', isDark);
        })();
    </script>

    <title>{{ $title ?? 'SmartGym' }} - {{ $companyName }}</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')

    @if ($themeCss)
        <style id="network-theme">{!! $themeCss !!}</style>
    @endif
</head>
<body class="bg-primary text-primary min-h-screen">
    {{-- Sidebar --}}
    @if (auth()->check())
        @include('components.sidebar')
    @endif

    {{-- Main Wrapper --}}
    <div class="wrapper">
        {{-- Navbar --}}
        @if (auth()->check())
            @include('components.navbar')
        @endif

        {{-- Main Content --}}
        <main class="main-content" id="mainContent">
            @include('components.flash-messages')

            @isset($slot)
                {{ $slot }}
            @else
                @yield('content')
            @endisset
        </main>
    </div>

    {{-- SweetAlert2 JS --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- Custom JS --}}
    <script>
        // Global app configuration
        window.SmartGym = {
            csrfToken: '{{ csrf_token() }}',
            user: @json(auth()->user() ?? null),
            network: @json(session('active_network') ?? null),
            unit: @json(session('active_unit') ?? null),
            region: @json(session('active_region') ?? null),
        };

        // Global SweetAlert helpers (follow the active network theme)
        const themeColor = (name, fallback) => {
            const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();

            return value || fallback;
        };

        const swalTheme = () => ({
            background: themeColor('--color-surface-elevated', '#222F46'),
            color: themeColor('--color-text-primary', '#F1F5F9'),
        });

        window.alertSuccess = (title, text) => Swal.fire({
            ...swalTheme(),
            title, text, icon: 'success',
            confirmButtonColor: themeColor('--color-primary', '#00E5FF'),
        });

        window.alertError = (title, text) => Swal.fire({
            ...swalTheme(),
            title, text, icon: 'error',
            confirmButtonColor: themeColor('--color-danger', '#FF3864'),
        });

        window.alertWarning = (title, text) => Swal.fire({
            ...swalTheme(),
            title, text, icon: 'warning',
            confirmButtonColor: themeColor('--color-warning', '#FFB300'),
        });

        window.alertInfo = (title, text) => Swal.fire({
            ...swalTheme(),
            title, text, icon: 'info',
            confirmButtonColor: themeColor('--color-info', '#38BDF8'),
        });

        window.alertConfirm = (title, text, callback) => Swal.fire({
            ...swalTheme(),
            title, text, icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: themeColor('--color-primary', '#00E5FF'),
            cancelButtonColor: themeColor('--color-danger', '#FF3864'),
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => { if (result.isConfirmed && callback) callback(); });
    </script>

    @stack('scripts')
</body>
</html>