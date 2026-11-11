@extends('layouts.app')

@section('title', 'Configurações da Rede')

@php
    $themes = \App\Support\NetworkTheme::forNetwork($network);
    $logoUrl = \App\Support\NetworkTheme::logoUrl($network);
    $themeMode = \App\Support\NetworkTheme::mode($network);
    $companyName = \App\Support\NetworkTheme::companyName($network);
@endphp

@section('content')
<div class="container mx-auto max-w-3xl p-6 space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Configurações da rede</h1>
        <p class="text-text-secondary mt-1">{{ $network->name }} &middot; {{ $network->slug }}</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg px-4 py-3 text-sm" style="background: rgba(0, 230, 118, 0.1); border: 1px solid var(--color-green); color: var(--color-green);">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg px-4 py-3 text-sm" style="background: rgba(255, 56, 100, 0.1); border: 1px solid var(--color-red); color: var(--color-red);">
            <p class="font-medium mb-1">Verifique os campos destacados:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.network.settings.update') }}"
          method="POST"
          enctype="multipart/form-data"
          class="card space-y-8">
        @csrf
        @method('PUT')

        {{-- Brand --}}
        <section class="space-y-4">
            <h2 class="font-semibold flex items-center gap-2">
                <i class="fas fa-palette text-cyan"></i>
                Identidade da marca
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="company_name" class="label-field">Nome da empresa (menu)</label>
                    <input type="text" id="company_name" name="company_name" maxlength="80" class="input-field"
                           value="{{ old('company_name', $network->settings['company_name'] ?? $network->name) }}"
                           placeholder="Nome exibido no menu do sistema">
                    @error('company_name')
                        <p class="mt-1 text-sm text-red">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="theme_mode" class="label-field">Tema padrão da rede</label>
                    <select id="theme_mode" name="theme_mode" class="input-field">
                        <option value="system" @selected(old('theme_mode', $themeMode) === 'system')>Seguir o sistema</option>
                        <option value="light" @selected(old('theme_mode', $themeMode) === 'light')>Claro</option>
                        <option value="dark" @selected(old('theme_mode', $themeMode) === 'dark')>Escuro</option>
                    </select>
                    <p class="text-xs text-text-secondary mt-1">Usuários que já escolheram um tema mantêm a preferência salva.</p>
                </div>
            </div>

            <div>
                <label for="name" class="label-field">Nome da rede <span class="text-red">*</span></label>
                <input type="text" id="name" name="name" required maxlength="255" class="input-field"
                       value="{{ old('name', $network->name) }}">
                @error('name')
                    <p class="mt-1 text-sm text-red">{{ $message }}</p>
                @enderror
            </div>

            {{-- Logo --}}
            <div class="rounded-xl border border-custom bg-bg-secondary p-4">
                <div class="flex flex-col sm:flex-row gap-4">
                    <div class="flex-shrink-0">
                        <img id="logoPreview"
                             src="{{ $logoUrl ?? '' }}"
                             alt="Logo atual"
                             class="w-24 h-24 rounded-xl object-contain border border-custom bg-surface-elevated p-2 {{ $logoUrl ? '' : 'hidden' }}">
                        <div id="logoPlaceholder" class="w-24 h-24 rounded-xl border border-dashed border-custom flex items-center justify-center {{ $logoUrl ? 'hidden' : '' }}">
                            <i class="fas fa-image text-2xl text-text-secondary"></i>
                        </div>
                    </div>

                    <div class="flex-1 space-y-2">
                        <label for="logo" class="label-field">Logo da rede</label>
                        <input type="file"
                               id="logo"
                               name="logo"
                               accept="image/png,image/jpeg,image/webp"
                               class="input-field file:mr-3 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-cyan/10 file:text-cyan">
                        @error('logo')
                            <p class="text-sm text-red">{{ $message }}</p>
                        @enderror

                        <div class="text-xs text-text-secondary space-y-1">
                            <p class="font-medium text-text-secondary">Como deve ser a imagem:</p>
                            <ul class="list-disc list-inside space-y-0.5">
                                <li>Formatos aceitos: <strong>PNG, JPG ou WebP</strong>.</li>
                                <li>Preferencialmente <strong>quadrada (1:1)</strong>, com fundo transparente (PNG/WebP).</li>
                                <li>Tamanho recomendado: <strong>512 × 512 px</strong>.</li>
                                <li>Mínimo de <strong>128 × 128 px</strong> e máximo de <strong>2048 × 2048 px</strong>.</li>
                                <li>Arquivo de até <strong>2 MB</strong>.</li>
                                <li>Evite textos pequenos: o logo aparece com cerca de 40 px no menu.</li>
                            </ul>
                        </div>

                        @if ($logoUrl)
                            <label class="inline-flex items-center gap-2 text-sm text-red cursor-pointer">
                                <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))>
                                Remover o logo atual
                            </label>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="document" class="label-field">CNPJ</label>
                    <input type="text" id="document" name="document" maxlength="18" class="input-field"
                           value="{{ old('document', $network->document) }}">
                </div>
            </div>
        </section>

        {{-- Address --}}
        <section class="space-y-4">
            <h2 class="font-semibold flex items-center gap-2">
                <i class="fas fa-location-dot text-purple"></i>
                Endereço
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-6 gap-4" data-cep-scope>
                <div class="sm:col-span-2">
                    <label for="zip_code" class="label-field">CEP</label>
                    <input type="text" id="zip_code" name="zip_code" maxlength="9" class="input-field"
                           value="{{ old('zip_code', $network->zip_code) }}" data-cep>
                </div>

                <div class="sm:col-span-3">
                    <label for="street" class="label-field">Rua / Logradouro</label>
                    <input type="text" id="street" name="street" maxlength="255" class="input-field"
                           value="{{ old('street', $network->street) }}" data-cep-field="street">
                </div>

                <div class="sm:col-span-1">
                    <label for="number" class="label-field">Número</label>
                    <input type="text" id="number" name="number" maxlength="20" class="input-field"
                           value="{{ old('number', $network->number) }}" data-cep-field="number">
                </div>

                <div class="sm:col-span-2">
                    <label for="complement" class="label-field">Complemento</label>
                    <input type="text" id="complement" name="complement" maxlength="255" class="input-field"
                           value="{{ old('complement', $network->complement) }}">
                </div>

                <div class="sm:col-span-2">
                    <label for="neighborhood" class="label-field">Bairro</label>
                    <input type="text" id="neighborhood" name="neighborhood" maxlength="255" class="input-field"
                           value="{{ old('neighborhood', $network->neighborhood) }}" data-cep-field="neighborhood">
                </div>

                <div class="sm:col-span-1">
                    <label for="city" class="label-field">Cidade</label>
                    <input type="text" id="city" name="city" maxlength="255" class="input-field"
                           value="{{ old('city', $network->city) }}" data-cep-field="city">
                </div>

                <div class="sm:col-span-1">
                    <label for="state" class="label-field">UF</label>
                    <input type="text" id="state" name="state" maxlength="2" class="input-field uppercase"
                           value="{{ old('state', $network->state) }}" data-cep-field="state">
                </div>
            </div>
        </section>

        {{-- Responsible --}}
        <section class="space-y-4">
            <h2 class="font-semibold flex items-center gap-2">
                <i class="fas fa-user-tie text-lime"></i>
                Responsável
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="responsible_name" class="label-field">Nome do responsável</label>
                    <input type="text" id="responsible_name" name="responsible_name" maxlength="255" class="input-field"
                           value="{{ old('responsible_name', $network->responsible_name) }}">
                </div>

                <div>
                    <label for="responsible_cpf" class="label-field">CPF do responsável</label>
                    <input type="text" id="responsible_cpf" name="responsible_cpf" maxlength="14" class="input-field"
                           value="{{ old('responsible_cpf', $network->responsible_cpf) }}">
                </div>

                <div>
                    <label for="responsible_email" class="label-field">E-mail do responsável</label>
                    <input type="email" id="responsible_email" name="responsible_email" maxlength="255" class="input-field"
                           value="{{ old('responsible_email', $network->responsible_email) }}">
                </div>

                <div>
                    <label for="responsible_phone" class="label-field">Telefone do responsável</label>
                    <input type="text" id="responsible_phone" name="responsible_phone" maxlength="20" class="input-field"
                           value="{{ old('responsible_phone', $network->responsible_phone) }}">
                </div>
            </div>
        </section>

        {{-- Light theme --}}
        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold flex items-center gap-2">
                    <i class="fas fa-sun text-yellow"></i>
                    Ambiente claro
                </h2>
                <span class="badge badge-info">Tema light</span>
            </div>

            @include('pages.admin.network-settings._color-fields', ['mode' => 'light', 'palette' => $themes['light']])
        </section>

        {{-- Dark theme --}}
        <section class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold flex items-center gap-2">
                    <i class="fas fa-moon text-purple"></i>
                    Ambiente escuro
                </h2>
                <span class="badge badge-info">Tema dark</span>
            </div>

            @include('pages.admin.network-settings._color-fields', ['mode' => 'dark', 'palette' => $themes['dark']])
        </section>

        <div class="flex justify-end gap-3 pt-4 border-t border-custom">
            <a href="{{ route('admin.dashboard') }}" class="btn-outline">Voltar</a>
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>
                Salvar configurações
            </button>
        </div>
    </form>

    <div class="card">
        <h2 class="font-semibold mb-4 flex items-center gap-2">
            <i class="fas fa-layer-group text-purple"></i>
            Plano contratado
        </h2>

        @if ($network->plan)
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <p class="font-medium">{{ $network->plan->name }}</p>
                    <p class="text-sm text-text-secondary">{{ $network->plan->description ?? 'Sem descrição' }}</p>
                </div>
                @if ($network->plan->isActive())
                    <span class="badge badge-success">Ativo</span>
                @else
                    <span class="badge badge-secondary">Inativo</span>
                @endif
            </div>

            @if ($network->plan->parameters->count() > 0)
                <div class="space-y-2">
                    @foreach ($network->plan->parameters as $parameter)
                        <div class="flex items-center justify-between rounded-lg bg-bg-secondary px-4 py-3">
                            <span class="text-sm">{{ $parameter->description }}</span>
                            <span class="font-medium">R$ {{ number_format((float) $parameter->value, 2, ',', '.') }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-text-secondary">Este plano não possui parâmetros cadastrados.</p>
            @endif
        @else
            <p class="text-sm text-text-secondary">Nenhum plano vinculado a esta rede.</p>
        @endif
    </div>
</div>

<script>
    (function () {
        const logoInput = document.getElementById('logo');
        const logoPreview = document.getElementById('logoPreview');
        const logoPlaceholder = document.getElementById('logoPlaceholder');

        logoInput?.addEventListener('change', function () {
            const file = this.files && this.files[0];

            if (!file) {
                return;
            }

            const url = URL.createObjectURL(file);
            logoPreview.src = url;
            logoPreview.classList.remove('hidden');
            logoPlaceholder?.classList.add('hidden');
        });

        document.querySelectorAll('input[type="color"]').forEach(function (input) {
            const hexLabel = input.parentElement.querySelector('[data-color-hex]');

            input.addEventListener('input', function () {
                if (hexLabel) {
                    hexLabel.textContent = input.value.toUpperCase();
                }
            });
        });
    })();
</script>
@endsection
