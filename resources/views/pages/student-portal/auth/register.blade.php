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

    <title>Cadastro - {{ $companyName }}</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @if ($themeCss)
        <style id="network-theme">{!! $themeCss !!}</style>
    @endif
</head>
<body class="bg-body flex min-h-screen items-center justify-center p-4"
      style="background-image: radial-gradient(circle at 15% 10%, color-mix(in srgb, var(--color-primary) 16%, transparent), transparent 38%), radial-gradient(circle at 85% 90%, color-mix(in srgb, var(--color-info) 12%, transparent), transparent 42%);">
    <div class="w-full max-w-lg py-6">
        <div class="mb-6 text-center">
            <img src="{{ $logoUrl }}" alt="{{ $companyName }}"
                 class="mx-auto h-20 w-auto max-w-[240px] object-contain sm:h-24">
        </div>

        <div class="card">
            <div class="mb-5 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Novo aluno</p>
                <h2 class="mt-2 text-xl font-bold">Crie sua conta</h2>
                <p class="mt-1 text-sm text-text-secondary">Cadastre-se para conhecer os planos disponíveis.</p>
            </div>

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

            <form method="POST" action="{{ route('student.portal.register.store', $network) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="label-field">Nome completo <span class="text-red">*</span></label>
                    <input type="text" id="name" name="name" class="input-field"
                           value="{{ old('name') }}" required autocomplete="name" autofocus>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="email" class="label-field">E-mail <span class="text-red">*</span></label>
                        <input type="email" id="email" name="email" class="input-field"
                               value="{{ old('email') }}" required autocomplete="email">
                    </div>

                    <div>
                        <label for="phone" class="label-field">Telefone / WhatsApp</label>
                        <input type="text" id="phone" name="phone" class="input-field"
                               value="{{ old('phone') }}" maxlength="20" autocomplete="tel">
                    </div>

                    <div>
                        <label for="cpf" class="label-field">CPF</label>
                        <input type="text" id="cpf" name="cpf" class="input-field"
                               value="{{ old('cpf') }}" maxlength="14" placeholder="000.000.000-00">
                    </div>

                    <div>
                        <label for="birth_date" class="label-field">Data de nascimento</label>
                        <input type="date" id="birth_date" name="birth_date" class="input-field"
                               value="{{ old('birth_date') }}">
                    </div>
                </div>

                <div>
                    <label for="unit_id" class="label-field">Unidade de interesse <span class="text-red">*</span></label>
                    <select id="unit_id" name="unit_id" required class="input-field">
                        <option value="">Selecione a unidade</option>
                        @foreach ($units as $unit)
                            <option value="{{ $unit->id }}" @selected((string) old('unit_id') === (string) $unit->id)>
                                {{ $unit->name }}{{ $unit->city ? ' - '.$unit->city.'/'.$unit->state : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="label-field">Senha <span class="text-red">*</span></label>
                        <input type="password" id="password" name="password" class="input-field"
                               required minlength="8" autocomplete="new-password" placeholder="Mínimo de 8 caracteres">
                    </div>

                    <div>
                        <label for="password_confirmation" class="label-field">Confirmar senha <span class="text-red">*</span></label>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                               class="input-field" required minlength="8" autocomplete="new-password">
                    </div>
                </div>

                <button type="submit" class="btn-primary w-full py-3">
                    <i class="fas fa-user-plus mr-2"></i>
                    Criar conta
                </button>
            </form>

            <p class="mt-5 text-center text-sm text-text-secondary">
                Já tem acesso?
                <a href="{{ route('student.portal.login', $network) }}" class="font-semibold text-cyan hover:underline">
                    Entrar
                </a>
            </p>
        </div>
    </div>
</body>
</html>
