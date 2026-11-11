<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Confirmar Senha - {{ config('app.name') }}</title>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-primary min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background: linear-gradient(135deg, var(--color-cyan-neon), var(--color-purple-neon));">
                    <i class="fas fa-dumbbell text-2xl" style="color: var(--color-bg-primary);"></i>
                </div>
                <span class="text-2xl font-bold bg-gradient-to-r from-cyan-400 to-purple-500 bg-clip-text text-transparent">
                    SmartGym
                </span>
            </a>
            <p class="text-text-secondary mt-2">Confirme sua senha para continuar</p>
        </div>

        {{-- Confirm Password Card --}}
        <div class="card">
            <h1 class="text-xl font-bold mb-6 text-center">Confirmar senha</h1>

            <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
                @csrf

                {{-- Password --}}
                <div>
                    <label for="password" class="label-field">Senha</label>
                    <div class="relative">
                        <input type="password"
                               id="password"
                               name="password"
                               class="input-field pr-12"
                               required
                               autocomplete="current-password"
                               autofocus>
                        <button type="button"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-text-secondary hover:text-text-primary"
                                onclick="togglePassword('password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-primary w-full py-3">
                    <i class="fas fa-lock mr-2"></i>
                    Confirmar
                </button>
            </form>

            {{-- Cancel --}}
            <div class="mt-4 text-center">
                <a href="{{ url()->previous() }}" class="text-text-secondary hover:text-text-primary text-sm">
                    <i class="fas fa-times mr-1"></i>
                    Cancelar
                </a>
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-6 text-xs text-text-secondary">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
        </div>
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
    </script>
</body>
</html>