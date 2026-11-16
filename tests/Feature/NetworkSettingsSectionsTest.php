<?php

namespace Tests\Feature;

use App\Models\Network;
use App\Models\User;
use App\Support\NetworkTheme;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetworkSettingsSectionsTest extends TestCase
{
    use RefreshDatabase;

    private User $networkAdmin;

    private Network $network;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
    }

    /** @test */
    public function test_network_settings_has_separate_screens(): void
    {
        $this->actingAs($this->networkAdmin)
            ->get(route('admin.network.settings.company'))
            ->assertOk()
            ->assertSee('Dados da empresa')
            ->assertSee('Endereço');

        $this->actingAs($this->networkAdmin)
            ->get(route('admin.network.settings.policies'))
            ->assertOk()
            ->assertSee('Políticas organizacionais')
            ->assertSee('Pessoas e alunos');

        $this->actingAs($this->networkAdmin)
            ->get(route('admin.network.settings.receipts'))
            ->assertOk()
            ->assertSee('Valor padrão da diária')
            ->assertSee('Mercado Pago da rede');

        $this->actingAs($this->networkAdmin)
            ->get(route('admin.network.settings.appearance'))
            ->assertOk()
            ->assertSee('Temas e layout')
            ->assertSee('Prévia do topo do menu')
            ->assertSee('Login do aluno')
            ->assertSee('Componentes do sistema');
    }

    /** @test */
    public function test_company_settings_can_be_saved_from_the_dedicated_screen(): void
    {
        $this->actingAs($this->networkAdmin)
            ->put(route('admin.network.settings.company.update'), [
                'name' => 'Rede Tela Própria',
                'company_name' => 'Empresa Tela Própria',
                'city' => 'Curitiba',
                'state' => 'pr',
            ])
            ->assertRedirect(route('admin.network.settings.company'));

        $network = $this->network->fresh();

        $this->assertSame('Rede Tela Própria', $network->name);
        $this->assertSame('Empresa Tela Própria', $network->settings['company_name']);
        $this->assertSame('PR', $network->state);
    }

    /** @test */
    public function test_policies_can_be_saved_from_the_dedicated_screen(): void
    {
        $this->actingAs($this->networkAdmin)
            ->put(route('admin.network.settings.policies.update'), [
                'policies' => [
                    'pessoas' => [
                        'scope' => 'unit',
                        'primary_unit_required' => '0',
                    ],
                ],
            ])
            ->assertRedirect(route('admin.network.settings.policies'));

        $policies = $this->network->fresh()->settings['policies'];

        $this->assertSame('unit', $policies['pessoas']['scope']);
        $this->assertFalse($policies['pessoas']['primary_unit_required']);
    }

    /** @test */
    public function test_receipt_settings_can_be_saved_from_the_dedicated_screen(): void
    {
        $this->actingAs($this->networkAdmin)
            ->put(route('admin.network.settings.receipts.update'), [
                'day_pass_price' => 42.50,
                'mercado_pago_active' => 1,
                'mercado_pago_environment' => 'sandbox',
                'mercado_pago_public_key' => 'APP_USR-receipt-key',
                'mercado_pago_access_token' => 'APP_USR-receipt-token',
            ])
            ->assertRedirect(route('admin.network.settings.receipts'));

        $this->assertDatabaseHas('payment_gateway_settings', [
            'network_id' => $this->network->id,
            'public_key' => 'APP_USR-receipt-key',
        ]);
    }

    /** @test */
    public function test_appearance_settings_are_saved_and_applied_to_menu_and_student_login(): void
    {
        $payload = [
            'theme_mode' => 'dark',
            'theme' => $this->themePayload(),
            'appearance' => [
                'menu' => [
                    'show_name' => 0,
                    'center_logo' => 1,
                    'logo_height' => 64,
                    'background' => '#112233',
                    'border' => '#223344',
                    'text' => '#FFFFFF',
                    'sidebar_background' => '#334455',
                    'sidebar_text' => '#DDEEFF',
                    'hover_background' => '#445566',
                    'hover_text' => '#FFFFFF',
                    'active_background' => '#556677',
                    'active_text' => '#00AAFF',
                ],
                'login' => [
                    'eyebrow' => 'Portal exclusivo',
                    'headline' => 'Treine com a nossa equipe.',
                    'description' => 'Acesse seus treinos e acompanhe sua evolução.',
                    'welcome_title' => 'Que bom ter você aqui',
                    'welcome_subtitle' => 'Entre para continuar seu treino.',
                    'button_text' => 'Acessar portal',
                    'logo_height' => 96,
                    'background' => '#123456',
                    'panel' => '#234567',
                    'text' => '#F8FAFC',
                    'muted' => '#AABBCC',
                    'accent' => '#0D6EFD',
                ],
            ],
        ];

        $this->actingAs($this->networkAdmin)
            ->put(route('admin.network.settings.appearance.update'), $payload)
            ->assertRedirect(route('admin.network.settings.appearance'));

        $appearance = $this->network->fresh()->settings['appearance'];

        $this->assertFalse((bool) $appearance['menu']['show_name']);
        $this->assertTrue((bool) $appearance['menu']['center_logo']);
        $this->assertSame(64, (int) $appearance['menu']['logo_height']);
        $this->assertSame('#112233', $appearance['menu']['background']);
        $this->assertSame('#334455', $appearance['menu']['sidebar_background']);
        $this->assertSame('Portal exclusivo', $appearance['login']['eyebrow']);
        $this->assertSame('#AABBCC', $appearance['login']['muted']);

        $this->actingAs($this->networkAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-brand-bar', false)
            ->assertSee('background-color: #112233', false)
            ->assertSee('height: 64px', false)
            ->assertSee('background-color: #334455', false)
            ->assertDontSee('data-brand-name', false);

        $this->get(route('student.portal.login', $this->network))
            ->assertOk()
            ->assertSee('Treine com a nossa equipe.')
            ->assertSee('Que bom ter você aqui')
            ->assertSee('Acessar portal')
            ->assertSee('background-color: #123456', false)
            ->assertSee('color: #AABBCC', false);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function themePayload(): array
    {
        return NetworkTheme::DEFAULTS;
    }
}
