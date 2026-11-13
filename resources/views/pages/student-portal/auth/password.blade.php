@extends('layouts.student')

@section('title', 'Alterar senha')

@section('content')
<div class="mx-auto w-full max-w-lg">
    <div class="card space-y-5">
        <div class="text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Primeiro acesso</p>
            <h1 class="mt-2 text-2xl font-bold">Defina uma nova senha</h1>
            <p class="mt-2 text-sm text-text-secondary">
                Sua senha atual é temporária. Para continuar, defina uma nova senha de acesso.
            </p>
        </div>

        <form method="POST" action="{{ route('student.portal.password.update', $network) }}" class="space-y-4">
            @csrf

            <div>
                <label for="current_password" class="label-field">Senha atual (temporária)</label>
                <input type="password" id="current_password" name="current_password" class="input-field" required
                       autocomplete="current-password">
            </div>

            <div>
                <label for="password" class="label-field">Nova senha</label>
                <input type="password" id="password" name="password" class="input-field" required minlength="8"
                       autocomplete="new-password" placeholder="Mínimo de 8 caracteres">
            </div>

            <div>
                <label for="password_confirmation" class="label-field">Confirmar nova senha</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="input-field"
                       required minlength="8" autocomplete="new-password">
            </div>

            <button type="submit" class="btn-primary w-full py-3">
                <i class="fas fa-key mr-2"></i>
                Salvar nova senha
            </button>
        </form>

        <p class="text-center text-xs text-text-secondary">
            O e-mail de acesso é {{ $account->email }}.
        </p>
    </div>
</div>
@endsection
