@php($account = $student->account)

<div class="card">
    <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h2 class="flex items-center gap-2 font-semibold">
                <i class="fas fa-key text-lime"></i>
                Acesso do aluno
            </h2>
            <p class="mt-1 text-xs text-text-secondary">
                Credenciais utilizadas pelo aluno no portal da rede.
            </p>
            <p class="mt-1 text-xs" style="color: var(--color-warning);">
                A senha definida aqui é temporária: o aluno deverá alterá-la no primeiro acesso.
            </p>
        </div>
        <div class="flex flex-wrap gap-2 self-start">
            @if ($account)
                <span class="badge {{ $account->status === 'active' ? 'badge-success' : 'badge-secondary' }}">
                    {{ $account->status === 'active' ? 'Acesso ativo' : 'Acesso inativo' }}
                </span>
                @if ($account->must_change_password)
                    <span class="badge badge-warning">Senha temporária</span>
                @endif
            @else
                <span class="badge badge-warning">Sem acesso</span>
            @endif
        </div>
    </div>

    @if ($account)
        <div class="mb-4 grid grid-cols-1 gap-3 rounded-lg border border-custom bg-bg-secondary p-3 text-sm sm:grid-cols-2">
            <div>
                <p class="text-xs text-text-secondary">E-mail de acesso</p>
                <p class="mt-1 font-medium">{{ $account->email }}</p>
            </div>
            <div>
                <p class="text-xs text-text-secondary">Último acesso</p>
                <p class="mt-1 font-medium">
                    {{ $account->last_login_at?->format('d/m/Y H:i') ?? 'Nunca acessou' }}
                </p>
            </div>
        </div>
    @endif

    <form action="{{ route('students.access.update', $student) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="access_email" class="label-field">E-mail de acesso <span class="text-red">*</span></label>
                <input type="email" id="access_email" name="email" required maxlength="255" class="input-field"
                       value="{{ old('email', $account?->email ?? $student->email) }}">
                @error('email')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="access_status" class="label-field">Status do acesso <span class="text-red">*</span></label>
                <select id="access_status" name="status" required class="input-field">
                    <option value="active" @selected(old('status', $account?->status ?? 'active') === 'active')>Ativo</option>
                    <option value="inactive" @selected(old('status', $account?->status ?? 'active') === 'inactive')>Inativo</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="access_password" class="label-field">
                    Senha {{ $account ? '' : '*' }}
                </label>
                <input type="password" id="access_password" name="password" class="input-field"
                       autocomplete="new-password" placeholder="Mínimo de 8 caracteres">
                @if ($account)
                    <p class="mt-1 text-xs text-text-secondary">Deixe em branco para manter a senha atual.</p>
                @endif
                @error('password')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="access_password_confirmation" class="label-field">Confirmar senha</label>
                <input type="password" id="access_password_confirmation" name="password_confirmation" class="input-field"
                       autocomplete="new-password">
            </div>
        </div>

        <div class="flex justify-end border-t border-custom pt-4">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>
                {{ $account ? 'Atualizar acesso' : 'Criar acesso' }}
            </button>
        </div>
    </form>
</div>
