<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Esqueci a Senha - {{ config('app.name') }}</title>

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
            <p class="text-text-secondary mt-2">Recupere o acesso à sua conta</p>
        </div>

        {{-- Forgot Password Card --}}
        <div class="card">
            <h1 class="text-xl font-bold mb-2 text-center">Esqueci a senha</h1>
            <p class="text-text-secondary text-center mb-6">Digite seu e-mail e enviaremos um link para redefinir sua senha.</p>

            @if (session('success'))
                <div class="mb-4 p-3 rounded-lg text-sm" style="background: rgba(0, 230, 118, 0.1); border: 1px solid var(--color-green); color: var(--color-green);">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="label-field">E-mail</label>
                    <input type="email"
                           id="email"
                           name="email"
                           class="input-field"
                           value="{{ old('email') }}"
                           required
                           autocomplete="email"
                           autofocus>
                    @error('email')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Submit --}}
                <button type="submit" class="btn-primary w-full py-3">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Enviar link de recuperação
                </button>
            </form>

            {{-- Back to Login --}}
            <div class="mt-6 text-center text-text-secondary">
                <p><a href="{{ route('login') }}" class="text-cyan hover:underline">Voltar para o login</a></p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-center mt-6 text-xs text-text-secondary">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>