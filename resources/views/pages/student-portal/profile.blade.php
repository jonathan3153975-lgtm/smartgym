@extends('layouts.student')

@section('title', 'Meus dados')

@section('content')
@php($student = $account->student)

<div class="mx-auto w-full max-w-3xl space-y-6">
    <h1 class="text-2xl font-bold">Meus dados</h1>

    <form method="POST" action="{{ route('student.portal.profile.update', $network) }}" class="card space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="profile_name" class="label-field">Nome completo <span class="text-red">*</span></label>
                <input type="text" id="profile_name" name="name" required maxlength="255" class="input-field"
                       value="{{ old('name', $student?->name) }}">
                @error('name')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="profile_email" class="label-field">E-mail de acesso <span class="text-red">*</span></label>
                <input type="email" id="profile_email" name="email" required maxlength="255" class="input-field"
                       value="{{ old('email', $account->email) }}">
                @error('email')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="profile_phone" class="label-field">Telefone</label>
                <input type="text" id="profile_phone" name="phone" maxlength="20" class="input-field" data-phone
                       placeholder="(00) 00000-0000" value="{{ old('phone', $student?->phone) }}">
                @error('phone')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="profile_whatsapp" class="label-field">WhatsApp</label>
                <input type="text" id="profile_whatsapp" name="whatsapp" maxlength="20" class="input-field" data-phone
                       placeholder="(00) 00000-0000" value="{{ old('whatsapp', $student?->whatsapp) }}">
                @error('whatsapp')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="profile_birth_date" class="label-field">Data de nascimento</label>
                <input type="date" id="profile_birth_date" name="birth_date" class="input-field"
                       value="{{ old('birth_date', $student?->birth_date?->format('Y-m-d')) }}">
                @error('birth_date')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <p class="label-field">CPF</p>
                <p class="text-sm font-medium">{{ $student?->cpf ?? 'Não informado' }}</p>
            </div>

            <div class="sm:col-span-2">
                <p class="label-field">Unidades</p>
                <p class="text-sm font-medium">
                    {{ $student?->units?->map(fn ($unit) => $unit->unit?->name)->filter()->implode(', ') ?: 'Não informada' }}
                </p>
            </div>
        </div>

        <div class="flex justify-end border-t border-custom pt-4">
            <button type="submit" class="btn-primary justify-center">
                <i class="fas fa-save mr-2"></i>
                Salvar dados
            </button>
        </div>
    </form>

    <form method="POST" action="{{ route('student.portal.password.update', $network) }}" class="card space-y-4">
        @csrf

        <div>
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-lock text-cyan"></i>
                Alterar senha
            </h2>
            <p class="mt-1 text-sm text-text-secondary">Use uma senha com pelo menos 8 caracteres.</p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="profile_current_password" class="label-field">Senha atual <span class="text-red">*</span></label>
                <input type="password" id="profile_current_password" name="current_password" required
                       class="input-field" autocomplete="current-password">
                @error('current_password')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="profile_password" class="label-field">Nova senha <span class="text-red">*</span></label>
                <input type="password" id="profile_password" name="password" required minlength="8"
                       class="input-field" autocomplete="new-password">
                @error('password')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="profile_password_confirmation" class="label-field">Confirmar nova senha <span class="text-red">*</span></label>
                <input type="password" id="profile_password_confirmation" name="password_confirmation" required minlength="8"
                       class="input-field" autocomplete="new-password">
            </div>
        </div>

        <div class="flex justify-end border-t border-custom pt-4">
            <button type="submit" class="btn-primary justify-center">
                <i class="fas fa-key mr-2"></i>
                Alterar senha
            </button>
        </div>
    </form>
</div>
@endsection
