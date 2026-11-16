<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Network;
use App\Models\PaymentGatewaySetting;
use App\Support\NetworkAppearance;
use App\Support\NetworkPolicy;
use App\Support\NetworkTheme;
use App\Support\Payments\MercadoPagoCredentials;
use App\Support\Settings\DayPassPrice;
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
    public function __construct(
        private readonly MercadoPagoCredentials $mercadoPago,
        private readonly DayPassPrice $dayPassPrice,
    ) {}

    /**
     * Display the settings of the current user's network.
     */
    public function edit(): View
    {
        $network = $this->network();
        $network->load(['plan.parameters']);

        return view('pages.admin.network-settings.edit', $this->settingsData($network));
    }

    public function company(): View
    {
        return $this->sectionView('company');
    }

    public function policies(): View
    {
        return $this->sectionView('policies');
    }

    public function receipts(): View
    {
        return $this->sectionView('receipts');
    }

    public function appearance(): View
    {
        $network = $this->network();

        return view('pages.admin.network-settings.appearance', [
            'network' => $network,
            'themes' => NetworkTheme::forNetwork($network),
            'appearance' => NetworkAppearance::forNetwork($network),
            'themeMode' => NetworkTheme::mode($network),
            'logoUrl' => NetworkTheme::logoUrl($network),
        ]);
    }

    public function updateCompany(Request $request): RedirectResponse
    {
        return $this->updateSection($request, 'company');
    }

    public function updatePolicies(Request $request): RedirectResponse
    {
        return $this->updateSection($request, 'policies');
    }

    public function updateReceipts(Request $request): RedirectResponse
    {
        return $this->updateSection($request, 'receipts');
    }

    public function updateAppearance(Request $request): RedirectResponse
    {
        $network = $this->network();

        $rules = [
            'theme_mode' => ['required', Rule::in(['light', 'dark', 'system'])],
            'appearance.menu.show_name' => ['nullable', 'boolean'],
            'appearance.menu.center_logo' => ['nullable', 'boolean'],
            'appearance.menu.logo_height' => ['required', 'integer', 'min:24', 'max:120'],
            'appearance.menu.background' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.menu.border' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.menu.text' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.menu.sidebar_background' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.menu.sidebar_text' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.menu.hover_background' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.menu.hover_text' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.menu.active_background' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.menu.active_text' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.login.eyebrow' => ['nullable', 'string', 'max:80'],
            'appearance.login.headline' => ['nullable', 'string', 'max:180'],
            'appearance.login.description' => ['nullable', 'string', 'max:400'],
            'appearance.login.welcome_title' => ['nullable', 'string', 'max:120'],
            'appearance.login.welcome_subtitle' => ['nullable', 'string', 'max:240'],
            'appearance.login.button_text' => ['nullable', 'string', 'max:40'],
            'appearance.login.logo_height' => ['required', 'integer', 'min:32', 'max:180'],
            'appearance.login.background' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.login.panel' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.login.text' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.login.muted' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'appearance.login.accent' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];

        foreach (NetworkTheme::DEFAULTS as $mode => $palette) {
            foreach (array_keys($palette) as $colorKey) {
                $rules["theme.{$mode}.{$colorKey}"] = ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'];
            }
        }

        $validated = $request->validate($rules);

        $settings = $network->settings;
        $settings['theme_mode'] = $validated['theme_mode'];
        $settings['theme'] = $validated['theme'];
        $settings['appearance'] = [
            'menu' => array_merge($validated['appearance']['menu'], [
                'show_name' => $request->boolean('appearance.menu.show_name'),
                'center_logo' => $request->boolean('appearance.menu.center_logo'),
            ]),
            'login' => $validated['appearance']['login'],
        ];

        $network->update(['settings' => $settings]);

        return redirect()->route('admin.network.settings.appearance')
            ->with('success', 'Aparência da rede atualizada com sucesso.');
    }

    private function sectionView(string $section): View
    {
        $network = $this->network();
        $network->load(['plan.parameters']);

        return view('pages.admin.network-settings.edit', array_merge(
            $this->settingsData($network),
            ['section' => $section]
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsData(Network $network): array
    {
        return [
            'network' => $network,
            'policyDomains' => NetworkPolicy::schema(),
            'policyValues' => NetworkPolicy::forNetwork($network),
            'mercadoPagoSetting' => $this->mercadoPago->forNetwork($network),
            'dayPassResolved' => $this->dayPassPrice->resolveForTenant(TenantContext::current()),
        ];
    }

    private function network(): Network
    {
        $network = TenantContext::current()->network();

        abort_unless($network, 403, 'Nenhuma rede ativa encontrada para este usuário.');

        return $network;
    }

    private function updateSection(Request $request, string $section): RedirectResponse
    {
        $network = $this->network();

        if ($section === 'company') {
            $rules = [
                'name' => ['required', 'string', 'max:255'],
                'company_name' => ['nullable', 'string', 'max:80'],
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
            ];

            $validated = $request->validate($rules);

            if (! empty($validated['state'])) {
                $validated['state'] = strtoupper($validated['state']);
            }

            $settings = $network->settings;
            $settings['company_name'] = ($validated['company_name'] ?? null) ?: $validated['name'];

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

            $update = Arr::except($validated, ['company_name', 'logo', 'remove_logo']);
            $update['settings'] = $settings;

            $network->update($update);

            return redirect()->route('admin.network.settings.company')
                ->with('success', 'Dados da empresa atualizados com sucesso.');
        }

        if ($section === 'policies') {
            $validated = $request->validate(NetworkPolicy::rules());

            $settings = $network->settings;
            $settings['policies'] = NetworkPolicy::merge($validated, $network);

            $network->update(['settings' => $settings]);

            return redirect()->route('admin.network.settings.policies')
                ->with('success', 'Políticas atualizadas com sucesso.');
        }

        $validated = $request->validate([
            'mercado_pago_active' => ['nullable', 'boolean'],
            'mercado_pago_environment' => ['nullable', Rule::in(array_keys(PaymentGatewaySetting::ENVIRONMENTS))],
            'mercado_pago_public_key' => ['nullable', 'string', 'max:255'],
            'mercado_pago_access_token' => ['nullable', 'string', 'max:1000'],
            'mercado_pago_webhook_secret' => ['nullable', 'string', 'max:1000'],
            'mercado_pago_clear' => ['nullable', 'boolean'],
            'day_pass_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ]);

        $this->mercadoPago->save($network, null, $validated);
        $this->dayPassPrice->setNetworkPrice(
            $network,
            isset($validated['day_pass_price']) && $validated['day_pass_price'] !== ''
                ? (float) $validated['day_pass_price']
                : null,
            auth()->id()
        );

        return redirect()->route('admin.network.settings.receipts')
            ->with('success', 'Recebimentos atualizados com sucesso.');
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
            'day_pass_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
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
            'day_pass_price',
        ]);

        $update['settings'] = $settings;

        DB::transaction(function () use ($network, $update, $validated) {
            $network->update($update);
            $this->mercadoPago->save($network, null, $validated);
            $this->dayPassPrice->setNetworkPrice(
                $network,
                isset($validated['day_pass_price']) && $validated['day_pass_price'] !== ''
                    ? (float) $validated['day_pass_price']
                    : null,
                auth()->id()
            );
        });

        return redirect()->route('admin.network.settings.edit')
            ->with('success', 'Configurações da rede atualizadas com sucesso.');
    }
}
