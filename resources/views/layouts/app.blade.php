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

        // Global SweetAlert helpers
        window.alertSuccess = (title, text) => Swal.fire({ title, text, icon: 'success', confirmButtonColor: '#00E5FF' });
        window.alertError = (title, text) => Swal.fire({ title, text, icon: 'error', confirmButtonColor: '#FF3864' });
        window.alertWarning = (title, text) => Swal.fire({ title, text, icon: 'warning', confirmButtonColor: '#FFB300' });
        window.alertInfo = (title, text) => Swal.fire({ title, text, icon: 'info', confirmButtonColor: '#00E5FF' });
        window.alertConfirm = (title, text, callback) => Swal.fire({
            title, text, icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#00E5FF',
            cancelButtonColor: '#FF3864',
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => { if (result.isConfirmed && callback) callback(); });
    </script>

    @stack('scripts')
</body>
</html>