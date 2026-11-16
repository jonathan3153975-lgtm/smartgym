@extends('layouts.app')

@section('title', 'Configurações - '.$region->name)

@section('content')
<div class="container mx-auto max-w-4xl space-y-6 p-4 sm:p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('admin.regions.show', $region) }}" class="text-sm text-text-secondary hover:text-cyan">
                <i class="fas fa-arrow-left mr-1"></i>
                Voltar para a região
            </a>
            <h1 class="mt-1 text-2xl font-bold">Configurações da região</h1>
            <p class="mt-1 text-text-secondary">{{ $region->name }} &middot; {{ $region->network?->name }}</p>
        </div>
        <span class="badge badge-info">Padrões regionais</span>
    </div>

    <form method="POST" action="{{ route('admin.regions.settings.update', $region) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="card space-y-4">
            <div>
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-ticket text-cyan"></i>
                    Valor padrão da diária
                </h2>
                <p class="mt-1 text-sm text-text-secondary">
                    Unidades da região herdam este valor quando não definirem um valor próprio.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="day_pass_price" class="label-field">Valor da região</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-text-secondary">R$</span>
                        <input type="number" id="day_pass_price" name="day_pass_price" min="0" step="0.01"
                               class="input-field pl-12" value="{{ old('day_pass_price', $regionPrice) }}"
                               placeholder="Herdar da rede">
                    </div>
                </div>
                <div class="flex items-end pb-2 text-sm text-text-secondary">
                    Rede: {{ $networkPrice !== null ? 'R$ '.number_format((float) $networkPrice, 2, ',', '.') : 'Não definido' }}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Esfera</th>
                            <th>Valor</th>
                            <th>Alterado por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($history as $entry)
                            <tr>
                                <td class="text-sm">{{ $entry->created_at?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="text-sm">{{ $entry->scope_label }}</td>
                                <td class="text-sm">{{ $entry->price_label }}</td>
                                <td class="text-sm">{{ $entry->changedBy?->name ?? 'Sistema' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-center text-sm text-text-secondary">Nenhuma alteração registrada.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card space-y-4">
            <div>
                <h2 class="flex items-center gap-2 font-semibold">
                    <i class="fas fa-credit-card text-cyan"></i>
                    Mercado Pago da região
                </h2>
                <p class="mt-1 text-sm text-text-secondary">
                    Usado pelas unidades da região que não tiverem credenciais próprias. A rede continua como último fallback.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" name="mercado_pago_active" value="1"
                               class="rounded border-custom bg-bg-secondary text-cyan focus:ring-cyan"
                               @checked(old('mercado_pago_active', $mercadoPagoSetting?->is_active ?? false))>
                        <span class="text-sm font-medium">Ativar pagamento online na região</span>
                    </label>
                </div>
                <div>
                    <label for="mercado_pago_environment" class="label-field">Ambiente</label>
                    <select id="mercado_pago_environment" name="mercado_pago_environment" class="input-field">
                        @foreach (\App\Models\PaymentGatewaySetting::ENVIRONMENTS as $value => $label)
                            <option value="{{ $value }}" @selected(old('mercado_pago_environment', $mercadoPagoSetting?->environment ?? 'sandbox') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="mercado_pago_public_key" class="label-field">Public Key</label>
                    <input type="text" id="mercado_pago_public_key" name="mercado_pago_public_key" maxlength="255"
                           class="input-field" value="{{ old('mercado_pago_public_key', $mercadoPagoSetting?->public_key) }}">
                </div>
                <div>
                    <label for="mercado_pago_access_token" class="label-field">Access Token</label>
                    <input type="password" id="mercado_pago_access_token" name="mercado_pago_access_token" maxlength="1000"
                           class="input-field" autocomplete="new-password"
                           placeholder="{{ $mercadoPagoSetting?->access_token ? 'Deixe em branco para manter' : 'APP_USR-...' }}">
                </div>
                <div>
                    <label for="mercado_pago_webhook_secret" class="label-field">Segredo do webhook</label>
                    <input type="password" id="mercado_pago_webhook_secret" name="mercado_pago_webhook_secret" maxlength="1000"
                           class="input-field" autocomplete="new-password"
                           placeholder="{{ $mercadoPagoSetting?->webhook_secret ? 'Deixe em branco para manter' : 'Opcional' }}">
                </div>
                @if ($mercadoPagoSetting)
                    <div class="sm:col-span-2">
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input type="checkbox" name="mercado_pago_clear" value="1"
                                   class="rounded border-custom bg-bg-secondary text-red focus:ring-red">
                            Remover as credenciais desta região e voltar a herdar da rede
                        </label>
                    </div>
                @endif
            </div>
        </section>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>
                Salvar configurações
            </button>
        </div>
    </form>
</div>
@endsection
