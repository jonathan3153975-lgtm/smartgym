@extends('layouts.app')

@section('title', 'Meu Perfil')

@section('content')
<div class="container mx-auto max-w-3xl p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Meu Perfil</h1>
            <p class="text-text-secondary mt-1">Gerencie suas informações pessoais e segurança</p>
        </div>
    </div>

    {{-- Profile Information --}}
    <div class="card mb-6">
        <h2 class="font-semibold mb-4">Informações do Perfil</h2>

        <form action="{{ route('profile.update') }}" method="POST" class="space-y-4">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="label-field">Nome</label>
                    <input type="text"
                           id="name"
                           name="name"
                           class="input-field"
                           value="{{ old('name', $user->name) }}"
                           required
                           autofocus>
                    @error('name')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="label-field">E-mail</label>
                    <input type="email"
                           id="email"
                           name="email"
                           class="input-field"
                           value="{{ old('email', $user->email) }}"
                           required>
                    @error('email')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-custom">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-save mr-2"></i>
                    Salvar Alterações
                </button>
            </div>
        </form>
    </div>

    {{-- Change Password --}}
    <div class="card mb-6">
        <h2 class="font-semibold mb-4">Alterar Senha</h2>

        <form action="{{ route('profile.password') }}" method="POST" class="space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="current_password" class="label-field">Senha Atual</label>
                    <input type="password"
                           id="current_password"
                           name="current_password"
                           class="input-field"
                           required
                           autocomplete="current-password">
                    @error('current_password')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="label-field">Nova Senha</label>
                    <input type="password"
                           id="password"
                           name="password"
                           class="input-field"
                           required
                           autocomplete="new-password">
                    @error('password')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="label-field">Confirmar Nova Senha</label>
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           class="input-field"
                           required
                           autocomplete="new-password">
                </div>
            </div>

            <div class="flex justify-end pt-4 border-t border-custom">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-key mr-2"></i>
                    Atualizar Senha
                </button>
            </div>
        </form>
    </div>

    {{-- Danger Zone --}}
    <div class="card border-red/30">
        <h2 class="font-semibold mb-4 text-red flex items-center gap-2">
            <i class="fas fa-exclamation-triangle"></i>
            Área de Perigo
        </h2>
        <p class="text-text-secondary mb-4">
            Uma vez excluída, sua conta não poderá ser recuperada. Todos os seus dados serão permanentemente removidos.
        </p>

        <form action="{{ route('profile.destroy') }}" method="POST" class="space-y-4">
            @csrf
            @method('DELETE')

            <div>
                <label for="confirm_password" class="label-field">Digite sua senha para confirmar</label>
                <input type="password"
                       id="confirm_password"
                       name="password"
                       class="input-field"
                       required
                       autocomplete="current-password">
                @error('password')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end pt-4 border-t border-custom">
                <button type="submit"
                        class="btn-danger"
                        onclick="return confirm('Tem certeza que deseja excluir sua conta? Esta ação é irreversível.')">
                    <i class="fas fa-trash mr-2"></i>
                    Excluir Conta
                </button>
            </div>
        </form>
    </div>
</div>
@endsection