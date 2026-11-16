@extends('layouts.app')

@section('title', 'Configurações da unidade')

@section('content')
<div class="container mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
    @include('pages.unit._nav', ['unit' => $unit, 'units' => $units])

    <div class="card">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan">Configurações da unidade</p>
                <h2 class="mt-1 text-xl font-bold">Valores e integrações</h2>
                <p class="mt-1 text-sm text-text-secondary">
                    O que não for definido aqui será herdado da região ou da rede.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($unit->region && auth()->user()->can('units.manage'))
                    <a href="{{ route('admin.regions.settings', $unit->region) }}" class="btn-outline">
                        <i class="fas fa-sitemap mr-2"></i>
                        Padrões da região
                    </a>
                @endif
                <span class="badge badge-info">{{ $unit->identifier }}</span>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg px-4 py-3 text-sm"
             style="background: var(--color-success-soft); color: var(--color-success);">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-lg px-4 py-3 text-sm"
             style="background: var(--color-danger-soft); color: var(--color-danger);">
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('unit.settings.update', ['unit_id' => $unit->id]) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="card space-y-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="flex items-center gap-2 font-semibold">
                        <i class="fas fa-ticket text-cyan"></i>
                        Valor da diária
                    </h2>
                    <p class="mt-1 text-sm text-text-secondary">
                        Valor usado na venda de diárias e em planos com período “Diária”.
                    </p>
                </div>

                <span class="badge {{ $resolved['source'] === 'unit' ? 'badge-success' : 'badge-warning' }}">
                    {{ $resolved['source'] === 'unit' ? 'Definido na unidade' : ($resolved['source'] === 'region' ? 'Herdado da região' : ($resolved['source'] === 'network' ? 'Herdado da rede' : 'Não definido')) }}
                </span>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="day_pass_price" class="label-field">Valor na unidade</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm font-medium text-text-secondary">R$</span>
                        <input type="text" id="day_pass_price" data-currency data-currency-target="day_pass_price_value"
                               class="input-field pl-12"
                               value="{{ $resolved['unit_price'] !== null ? number_format($resolved['unit_price'], 2, ',', '.') : '' }}"
                               placeholder="Herdar valor" inputmode="decimal">
                        <input type="hidden" id="day_pass_price_value" name="day_pass_price" value="{{ $resolved['unit_price'] }}">
                    </div>
                </div>

                <div class="flex items-end">
                    <label class="flex cursor-pointer items-center gap-2 pb-3 text-sm">
                        <input type="checkbox" id="inherit_day_pass_price" name="inherit_day_pass_price" value="1"
                               class="rounded border-custom bg-bg-secondary text-cyan focus:ring-cyan"
                               @checked(old('inherit_day_pass_price', $resolved['unit_price'] === null))>
                        <span>Usar valor herdado (região/rede)</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 rounded-xl border border-custom bg-bg-secondary p-3 text-sm sm:grid-cols-3">
                <div>
                    <p class="text-xs text-text-secondary">Unidade</p>
                    <p class="font-medium">{{ $resolved['unit_price'] !== null ? 'R$ '.number_format($resolved['unit_price'], 2, ',', '.') : 'Não definido' }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">Região</p>
                    <p class="font-medium">{{ $resolved['region_price'] !== null ? 'R$ '.number_format($resolved['region_price'], 2, ',', '.') : 'Não definido' }}</p>
                </div>
                <div>
                    <p class="text-xs text-text-secondary">Rede</p>
                    <p class="font-medium">{{ $resolved['network_price'] !== null ? 'R$ '.number_format($resolved['network_price'], 2, ',', '.') : 'Não definido' }}</p>
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
                                <td colspan="4" class="py-4 text-center text-sm text-text-secondary">
                                    Nenhuma alteração registrada.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card space-y-5">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="flex items-center gap-2 font-semibold">
                        <i class="fas fa-credit-card text-cyan"></i>
                        Credenciais Mercado Pago
                    </h2>
                    <p class="mt-1 text-sm text-text-secondary">
                        Credenciais da unidade. Sem elas, a unidade usa as credenciais da região e depois as da rede.
                    </p>
                </div>

                @if ($credentialSummary)
                    <span class="badge {{ $credentialSummary['unit_configured'] ? 'badge-success' : 'badge-warning' }}">
                        {{ $credentialSummary['unit_configured'] ? 'Credenciais da unidade' : ($credentialSummary['region_configured'] ? 'Usando credenciais da região' : ($credentialSummary['network_configured'] ? 'Usando credenciais da rede' : 'Não configurado')) }}
                    </span>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="flex cursor-pointer items-center gap-2">
                        <input type="checkbox" id="mercado_pago_active" name="mercado_pago_active" value="1"
                               class="rounded border-custom bg-bg-secondary text-cyan focus:ring-cyan"
                               @checked(old('mercado_pago_active', $mercadoPagoSetting?->is_active ?? false))>
                        <span class="text-sm font-medium">Ativar pagamento online na unidade</span>
                    </label>
                </div>

                <div>
                    <label for="mercado_pago_environment" class="label-field">Ambiente</label>
                    <select id="mercado_pago_environment" name="mercado_pago_environment" class="input-field">
                        @foreach (\App\Models\PaymentGatewaySetting::ENVIRONMENTS as $environmentValue => $environmentLabel)
                            <option value="{{ $environmentValue }}" @selected(old('mercado_pago_environment', $mercadoPagoSetting?->environment ?? 'sandbox') === $environmentValue)>
                                {{ $environmentLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="mercado_pago_public_key" class="label-field">Public Key</label>
                    <input type="text" id="mercado_pago_public_key" name="mercado_pago_public_key"
                           maxlength="255" class="input-field"
                           value="{{ old('mercado_pago_public_key', $mercadoPagoSetting?->public_key) }}"
                           placeholder="APP_USR-...">
                </div>

                <div>
                    <label for="mercado_pago_access_token" class="label-field">Access Token</label>
                    <input type="password" id="mercado_pago_access_token" name="mercado_pago_access_token"
                           maxlength="1000" class="input-field" autocomplete="new-password"
                           placeholder="{{ $mercadoPagoSetting?->access_token ? 'Deixe em branco para manter' : 'APP_USR-...' }}">
                </div>

                <div>
                    <label for="mercado_pago_webhook_secret" class="label-field">Segredo do webhook</label>
                    <input type="password" id="mercado_pago_webhook_secret" name="mercado_pago_webhook_secret"
                           maxlength="1000" class="input-field" autocomplete="new-password"
                           placeholder="{{ $mercadoPagoSetting?->webhook_secret ? 'Deixe em branco para manter' : 'Opcional' }}">
                </div>

                @if ($mercadoPagoSetting)
                    <div class="sm:col-span-2">
                        <label class="flex cursor-pointer items-center gap-2 text-sm">
                            <input type="checkbox" name="mercado_pago_clear" value="1"
                                   class="rounded border-custom bg-bg-secondary text-red focus:ring-red">
                            Remover as credenciais desta unidade e voltar a herdar região/rede
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

<script>
    (function () {
        const inherit = document.getElementById('inherit_day_pass_price');
        const price = document.getElementById('day_pass_price');
        const priceValue = document.getElementById('day_pass_price_value');

        function syncPrice() {
            if (!inherit || !price) {
                return;
            }

            price.disabled = inherit.checked;

            if (inherit.checked) {
                price.value = '';

                if (priceValue) {
                    priceValue.value = '';
                }
            }
        }

        inherit?.addEventListener('change', syncPrice);
        syncPrice();
    })();
</script>
@endsection
