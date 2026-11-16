<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewaySetting;
use App\Support\Payments\MercadoPagoCredentials;
use App\Support\Settings\DayPassPrice;
use App\Support\UnitPanelContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly DayPassPrice $dayPassPrice,
        private readonly MercadoPagoCredentials $mercadoPago,
    ) {}

    public function edit(Request $request): View
    {
        $unit = UnitPanelContext::unit($request);
        $units = UnitPanelContext::units();
        $unit->load(['network', 'region']);

        $resolved = $this->dayPassPrice->resolve($unit);
        $history = $this->dayPassPrice->historyForUnit($unit);
        $mercadoPagoSetting = $unit->network
            ? $this->mercadoPago->forUnit($unit->network, $unit)
            : null;
        $credentialSummary = $unit->network
            ? $this->mercadoPago->summary($unit->network, $unit)
            : null;

        return view('pages.unit.settings.edit', compact(
            'unit',
            'units',
            'resolved',
            'history',
            'mercadoPagoSetting',
            'credentialSummary'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $unit = UnitPanelContext::unit($request);
        $unit->load(['network', 'region']);

        $validated = $request->validate([
            'day_pass_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'inherit_day_pass_price' => ['nullable', 'boolean'],
            'mercado_pago_active' => ['nullable', 'boolean'],
            'mercado_pago_environment' => ['nullable', Rule::in(array_keys(PaymentGatewaySetting::ENVIRONMENTS))],
            'mercado_pago_public_key' => ['nullable', 'string', 'max:255'],
            'mercado_pago_access_token' => ['nullable', 'string', 'max:1000'],
            'mercado_pago_webhook_secret' => ['nullable', 'string', 'max:1000'],
            'mercado_pago_clear' => ['nullable', 'boolean'],
        ]);

        $inherit = $request->boolean('inherit_day_pass_price');
        $price = $validated['day_pass_price'] ?? null;

        $this->dayPassPrice->setUnitPrice(
            $unit,
            $inherit || $price === null || $price === '' ? null : (float) $price,
            auth()->id()
        );

        if ($unit->network) {
            $this->mercadoPago->save($unit->network, $unit, $validated);
        }

        return redirect()
            ->route('unit.settings.edit', ['unit_id' => $unit->id])
            ->with('success', 'Configurações da unidade atualizadas com sucesso.');
    }
}
