{{-- Shared login card (mobile themed, desktop forced-light by .login-form-scope) --}}
<div class="card login-card">
    <h1 class="text-xl font-bold mb-6 text-center">Entrar na sua conta</h1>

    {{-- Flash Messages --}}
    @if (session('success'))
        <div class="mb-4 p-3 rounded-lg text-sm" style="background: var(--color-success-soft); border: 1px solid var(--color-success); color: var(--color-success);">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 p-3 rounded-lg text-sm" style="background: var(--color-danger-soft); border: 1px solid var(--color-danger); color: var(--color-danger);">
            {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
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

        {{-- Password --}}
        <div>
            <label for="password" class="label-field">Senha</label>
            <div class="relative">
                <input type="password"
                       id="password"
                       name="password"
                       class="input-field pr-12"
                       required
                       autocomplete="current-password">
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

        {{-- Remember & Forgot Password --}}
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-custom bg-bg-secondary text-cyan focus:ring-cyan">
                <span class="text-sm text-text-secondary">Lembrar-me</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-sm text-cyan hover:underline">Esqueci a senha</a>
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-primary w-full py-3">
            <i class="fas fa-sign-in-alt mr-2"></i>
            Entrar
        </button>
    </form>

    {{-- Register Link --}}
    <div class="mt-6 text-center text-text-secondary">
        <p>Não tem uma conta? <a href="{{ route('register') }}" class="text-cyan hover:underline font-medium">Registre-se</a></p>
    </div>
</div>
