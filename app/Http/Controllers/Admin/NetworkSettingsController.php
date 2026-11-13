<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewaySetting;
use App\Support\NetworkPolicy;
use App\Support\NetworkTheme;
use App\Support\Payments\MercadoPagoCredentials;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NetworkSettingsController extends Controller
{
    public function __construct(private readonly MercadoPagoCredentials $mercadoPago) {}

    /**
     * Display the settings of the current user's network.
     */
    public function edit(): View
    {
        $network = TenantContext::current()->network();

        abort_unless($network, 403, 'Nenhuma rede ativa encontrada para este usuário.');

        $network->load(['plan.parameters']);

        $policyDomains = NetworkPolicy::schema();
        $policyValues = NetworkPolicy::forNetwork($network);
        $mercadoPagoSetting = $this->mercadoPago->forNetwork($network);

        return view('pages.admin.network-settings.edit', compact(
            'network',
            'policyDomains',
            'policyValues',
            'mercadoPagoSetting'
        ));
    }

    /**
     * Update the settings of the current user's network.
     */
    public function update(Request $request): RedirectResponse
    {
        $tenant = TenantContext::current();
        $network = $tenant->network();

        abort_unless($network, 403, 'Nenhuma rede ativa encontrada para este usuário.');

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:80'],
            'theme_mode' => ['required', Rule::in(['light', 'dark', 'system'])],
            'logo' => [
                'nullable',
                'image',
                'mimes:png,jpg,jpeg,webp',
                'max:2048',
                'dimensions:min_width=128,min_height=128,max_width=2048,max_height=2048',
            ],
            'remove_logo' => ['nullable', 'boolean'],
            'document' => ['nullable', 'string', 'max:18'],
            'zip_code' => ['nullable', 'string', 'max:9'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2'],
            'responsible_name' => ['nullable', 'string', 'max:255'],
            'responsible_cpf' => ['nullable', 'string', 'max:14'],
            'responsible_email' => ['nullable', 'email', 'max:255'],
            'responsible_phone' => ['nullable', 'string', 'max:20'],
            'mercado_pago_active' => ['nullable', 'boolean'],
            'mercado_pago_environment' => ['nullable', Rule::in(array_keys(PaymentGatewaySetting::ENVIRONMENTS))],
            'mercado_pago_public_key' => ['nullable', 'string', 'max:255'],
            'mercado_pago_access_token' => ['nullable', 'string', 'max:1000'],
            'mercado_pago_webhook_secret' => ['nullable', 'string', 'max:1000'],
            'mercado_pago_clear' => ['nullable', 'boolean'],
        ];

        foreach (NetworkTheme::DEFAULTS as $mode => $palette) {
            foreach (array_keys($palette) as $colorKey) {
                $rules["theme.{$mode}.{$colorKey}"] = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
            }
        }

        $rules = array_merge($rules, NetworkPolicy::rules());

        $validated = $request->validate($rules);

        if (! empty($validated['state'])) {
            $validated['state'] = strtoupper($validated['state']);
        }

        $settings = $network->settings;
        $settings['company_name'] = ($validated['company_name'] ?? null) ?: $validated['name'];
        $settings['theme_mode'] = $validated['theme_mode'];
        $settings['theme'] = $validated['theme'];
        $settings['policies'] = NetworkPolicy::merge($validated, $network);

        unset($settings['primary_color'], $settings['secondary_color'], $settings['accent_color']);

        $currentLogo = $settings['logo'] ?? null;

        if ($request->boolean('remove_logo') && $currentLogo) {
            Storage::disk('public')->delete($currentLogo);
            unset($settings['logo']);
            $currentLogo = null;
        }

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('networks/'.$network->id, 'public');

            if ($currentLogo) {
                Storage::disk('public')->delete($currentLogo);
            }

            $settings['logo'] = $path;
        }

        $update = Arr::except($validated, [
            'company_name',
            'theme_mode',
            'theme',
            'policies',
            'logo',
            'remove_logo',
            'mercado_pago_active',
            'mercado_pago_environment',
            'mercado_pago_public_key',
            'mercado_pago_access_token',
            'mercado_pago_webhook_secret',
            'mercado_pago_clear',
        ]);

        $update['settings'] = $settings;

        DB::transaction(function () use ($network, $update, $validated) {
            $network->update($update);
            $this->mercadoPago->save($network, null, $validated);
        });

        return redirect()->route('admin.network.settings.edit')
            ->with('success', 'Configurações da rede atualizadas com sucesso.');
    }
}
