<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Verificar E-mail - {{ config('app.name') }}</title>

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
            <a href="{{ route('login') }}" class="inline-flex items-center gap-3">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background: linear-gradient(135deg, var(--color-cyan-neon), var(--color-purple-neon));">
                    <i class="fas fa-dumbbell text-2xl" style="color: var(--color-bg-primary);"></i>
                </div>
                <span class="text-2xl font-bold bg-gradient-to-r from-cyan-400 to-purple-500 bg-clip-text text-transparent">
                    SmartGym
                </span>
            </a>
            <p class="text-text-secondary mt-2">Verifique seu endereço de e-mail</p>
        </div>

        {{-- Verify Email Card --}}
        <div class="card text-center">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6" style="background: rgba(0, 229, 255, 0.15);">
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
</body>
</html>