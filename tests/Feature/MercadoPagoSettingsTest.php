<?php

namespace Tests\Feature;

use App\Models\Network;
use App\Models\PaymentGatewaySetting;
use App\Models\Unit;
use App\Models\User;
use App\Support\NetworkTheme;
use App\Support\Payments\MercadoPagoCredentials;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MercadoPagoSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $networkAdmin;

    private Network $network;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();

        $this->actingAs($this->networkAdmin);
    }

    /** @test */
    public function test_network_admin_can_store_encrypted_mercado_pago_credentials(): void
    {
        $this->get(route('admin.network.settings.edit'))
            ->assertOk()
            ->assertSee('data-settings-tab="pagamentos"', false)
            ->assertSee('Mercado Pago da rede')
            ->assertSee('Access Token');

        $this->put(route('admin.network.settings.update'), array_merge([
            'name' => $this->network->name,
            'mercado_pago_active' => 1,
            'mercado_pago_environment' => 'production',
            'mercado_pago_public_key' => 'APP_USR-public-key',
            'mercado_pago_access_token' => 'APP_USR-secret-token',
            'mercado_pago_webhook_secret' => 'webhook-secret',
        ], $this->themePayload()))->assertRedirect(route('admin.network.settings.edit'));

        $setting = PaymentGatewaySetting::firstOrFail();

        $this->assertNull($setting->unit_id);
        $this->assertTrue($setting->is_active);
        $this->assertTrue($setting->isUsable());
        $this->assertSame('production', $setting->environment);
        $this->assertSame('APP_USR-public-key', $setting->public_key);
        $this->assertSame('APP_USR-secret-token', $setting->access_token);
        $this->assertSame('webhook-secret', $setting->webhook_secret);

        $raw = $this->network->fresh()->paymentGatewaySettings()->firstOrFail();
        $this->assertNotSame('APP_USR-secret-token', $raw->getRawOriginal('access_token'));

        $this->put(route('admin.network.settings.update'), array_merge([
            'name' => $this->network->name,
            'mercado_pago_active' => 1,
            'mercado_pago_environment' => 'production',
            'mercado_pago_public_key' => 'APP_USR-other-key',
        ], $this->themePayload()))->assertRedirect(route('admin.network.settings.edit'));

        $setting->refresh();

        $this->assertSame('APP_USR-secret-token', $setting->access_token);
        $this->assertSame('APP_USR-other-key', $setting->public_key);

        $this->put(route('admin.network.settings.update'), array_merge([
            'name' => $this->network->name,
            'mercado_pago_clear' => 1,
        ], $this->themePayload()))->assertRedirect(route('admin.network.settings.edit'));

        $this->assertDatabaseCount('payment_gateway_settings', 0);
    }

    /** @test */
    public function test_unit_credentials_are_saved_and_never_exposed(): void
    {
        $this->putJson(route('admin.units.update', $this->unit), [
            'network_id' => $this->network->id,
            'name' => $this->unit->name,
            'status' => 'active',
            'mercado_pago_active' => 1,
            'mercado_pago_environment' => 'sandbox',
            'mercado_pago_public_key' => 'UNIT-public-key',
            'mercado_pago_access_token' => 'UNIT-secret-token',
            'mercado_pago_webhook_secret' => 'UNIT-webhook-secret',
        ])->assertOk()->assertJsonPath('success', true);

        $setting = PaymentGatewaySetting::where('unit_id', $this->unit->id)->firstOrFail();

        $this->assertSame('UNIT-secret-token', $setting->access_token);
        $this->assertSame('UNIT-webhook-secret', $setting->webhook_secret);

        $response = $this->getJson(route('admin.units.show', $this->unit))
            ->assertOk()
            ->assertJsonPath('mercado_pago.configured', true)
            ->assertJsonPath('mercado_pago.environment', 'sandbox');

        $payload = $response->json('mercado_pago');

        $this->assertArrayNotHasKey('access_token', $payload);
        $this->assertArrayNotHasKey('webhook_secret', $payload);
        $this->assertStringNotContainsString('UNIT-secret-token', $response->getContent());

        $this->get(route('admin.units.show', $this->unit))
            ->assertOk()
            ->assertSee('Pagamento online')
            ->assertSee('Credenciais da unidade');

        $this->putJson(route('admin.units.update', $this->unit), [
            'network_id' => $this->network->id,
            'name' => 'Unidade Renomeada',
            'status' => 'active',
        ])->assertOk();

        $this->assertSame('UNIT-secret-token', $setting->fresh()->access_token);
    }

    /** @test */
    public function test_credentials_resolver_prefers_unit_then_network(): void
    {
        $resolver = app(MercadoPagoCredentials::class);

        $this->assertNull($resolver->resolve($this->network, $this->unit));

        $networkSetting = PaymentGatewaySetting::create([
            'network_id' => $this->network->id,
            'unit_id' => null,
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADO_PAGO,
            'environment' => 'sandbox',
            'access_token' => 'network-token',
            'is_active' => true,
        ]);

        $this->assertSame($networkSetting->id, $resolver->resolve($this->network, $this->unit)?->id);

        $unitSetting = PaymentGatewaySetting::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADO_PAGO,
            'environment' => 'sandbox',
            'access_token' => 'unit-token',
            'is_active' => true,
        ]);

        $this->assertSame($unitSetting->id, $resolver->resolve($this->network, $this->unit)?->id);

        $unitSetting->update(['is_active' => false]);

        $this->assertSame($networkSetting->id, $resolver->resolve($this->network, $this->unit)?->id);

        $summary = $resolver->summary($this->network, $this->unit);

        $this->assertFalse($summary['unit_configured']);
        $this->assertTrue($summary['network_configured']);
        $this->assertSame('network', $summary['source']);
    }

    /**
     * @return array<string, mixed>
     */
    private function themePayload(): array
    {
        return [
            'theme_mode' => 'dark',
            'theme' => [
                'dark' => NetworkTheme::DEFAULTS['dark'],
                'light' => NetworkTheme::DEFAULTS['light'],
            ],
        ];
    }
}
