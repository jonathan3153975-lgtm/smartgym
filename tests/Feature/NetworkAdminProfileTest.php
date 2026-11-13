<?php

namespace Tests\Feature;

use App\Models\Network;
use App\Models\Permission;
use App\Models\Person;
use App\Models\Region;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserVinculo;
use App\Support\NetworkTheme;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NetworkAdminProfileTest extends TestCase
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

        $this->actingAs($this->networkAdmin);
    }

    /** @test */
    public function test_network_admin_can_access_the_management_panel_and_menu(): void
    {
        $html = $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Painel administrativo')
            ->assertSee('Minha rede')
            ->assertSee('<span>Regiões</span>', false)
            ->assertSee('<span>Unidades</span>', false)
            ->assertSee('<span>Pessoas</span>', false)
            ->assertSee('<span>Usuários</span>', false)
            ->assertSee('<span>Perfis</span>', false)
            ->assertSee('<span>Modalidades</span>', false)
            ->assertSee('<span>Planos</span>', false)
            ->assertDontSee('<span>Redes</span>', false)
            ->assertDontSee('<span>Planos SaaS</span>', false)
            ->getContent();

        $this->assertStringContainsString('Visão da rede', $html);
        $this->assertStringContainsString('Configurações', $html);
    }

    /** @test */
    public function test_network_admin_cannot_access_platform_modules(): void
    {
        $this->get(route('admin.networks.index'))->assertForbidden();
        $this->get(route('admin.plans.index'))->assertForbidden();
    }

    /** @test */
    public function test_network_admin_only_sees_data_from_their_network(): void
    {
        $otherNetwork = $this->createOtherNetwork();
        $otherRegion = Region::create([
            'network_id' => $otherNetwork->id,
            'name' => 'Região Externa',
            'status' => 'active',
        ]);
        $otherUnit = Unit::create([
            'network_id' => $otherNetwork->id,
            'region_id' => $otherRegion->id,
            'name' => 'Unidade Externa',
            'identifier' => 'UNID-EXTERNA',
            'status' => 'active',
        ]);
        $otherUser = User::factory()->create(['email' => 'externo@outrarede.com']);
        UserVinculo::create([
            'user_id' => $otherUser->id,
            'network_id' => $otherNetwork->id,
            'role_id' => Role::where('name', 'network_admin')->firstOrFail()->id,
            'status' => 'active',
        ]);

        $this->get(route('admin.regions.index'))
            ->assertOk()
            ->assertSee('Região Sul')
            ->assertDontSee('Região Externa');

        $this->get(route('admin.units.index'))
            ->assertOk()
            ->assertSee('SmartGym Matriz')
            ->assertDontSee('Unidade Externa');

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('externo@outrarede.com');

        $this->get(route('admin.units.show', $otherUnit))->assertNotFound();
        $this->get(route('admin.regions.show', $otherRegion))->assertNotFound();
        $this->get(route('admin.users.show', $otherUser))->assertNotFound();
    }

    /** @test */
    public function test_network_admin_can_view_their_own_network_but_not_another(): void
    {
        $otherNetwork = $this->createOtherNetwork();

        $this->get(route('admin.networks.show', $this->network))
            ->assertOk()
            ->assertSee('Visualização da estrutura');

        $this->get(route('admin.networks.show', $otherNetwork))->assertNotFound();
    }

    /** @test */
    public function test_network_admin_can_update_their_network_settings(): void
    {
        $this->get(route('admin.network.settings.edit'))
            ->assertOk()
            ->assertSee('Configurações da rede')
            ->assertSee('Tema padrão da rede')
            ->assertSee('Exemplos de elementos')
            ->assertSee('Políticas organizacionais')
            ->assertSee('Pessoas e alunos')
            ->assertSee('Planos e matrículas')
            ->assertSee('Modalidades e serviços')
            ->assertSee('Governança e auditoria')
            ->assertSee('Fundo da mensagem de sucesso')
            ->assertSee('Fundo da mensagem de erro')
            ->assertSee('Fundo da mensagem de informação')
            ->assertSee('data-settings-tab="empresa"', false)
            ->assertSee('data-settings-tab="tema"', false)
            ->assertSee('data-settings-tab="politica"', false)
            ->assertSee('id="settingsTabSelect"', false)
            ->assertSee('data-initial-settings-tab="empresa"', false);

        $this->put(route('admin.network.settings.update'), array_merge([
            'name' => 'SmartGym Demo Renomeada',
            'company_name' => 'Academia Forte',
            'document' => '00.000.000/0001-00',
            'city' => 'Curitiba',
            'state' => 'pr',
        ], $this->themePayload()))->assertRedirect(route('admin.network.settings.edit'));

        $network = $this->network->fresh();

        $this->assertSame('SmartGym Demo Renomeada', $network->name);
        $this->assertSame('PR', $network->state);
        $this->assertSame('Academia Forte', $network->settings['company_name']);
        $this->assertSame('#101010', $network->settings['theme']['dark']['background_primary']);
        $this->assertSame('#EFEFEF', $network->settings['theme']['light']['background_primary']);
    }

    /** @test */
    public function test_network_admin_can_upload_and_remove_the_logo(): void
    {
        Storage::fake('public');

        $this->put(route('admin.network.settings.update'), array_merge([
            'name' => $this->network->name,
            'theme_mode' => 'system',
        ], $this->themePayload(), [
            'logo' => UploadedFile::fake()->image('logo.png', 512, 512),
        ]))->assertRedirect(route('admin.network.settings.edit'));

        $network = $this->network->fresh();
        $logoPath = $network->settings['logo'] ?? null;

        $this->assertNotNull($logoPath);
        Storage::disk('public')->assertExists($logoPath);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('/network/logo/'.$this->network->id, false);

        $this->get(route('network.logo', $this->network))->assertOk();

        $this->put(route('admin.network.settings.update'), array_merge([
            'name' => $this->network->name,
            'theme_mode' => 'system',
            'remove_logo' => 1,
        ], $this->themePayload()))->assertRedirect(route('admin.network.settings.edit'));

        $this->assertArrayNotHasKey('logo', $this->network->fresh()->settings);
        Storage::disk('public')->assertMissing($logoPath);
    }

    /** @test */
    public function test_invalid_fields_open_the_matching_settings_tab(): void
    {
        $this->withSession([
            'errors' => [
                'default' => [
                    'format' => ':message',
                    'messages' => [
                        'theme.dark.background_primary' => ['Informe uma cor válida.'],
                    ],
                ],
            ],
        ])
            ->get(route('admin.network.settings.edit'))
            ->assertOk()
            ->assertSee('data-initial-settings-tab="tema"', false);

        $this->withSession([
            'errors' => [
                'default' => [
                    'format' => ':message',
                    'messages' => [
                        'policies.pessoas.scope' => ['Escopo inválido.'],
                    ],
                ],
            ],
        ])
            ->get(route('admin.network.settings.edit'))
            ->assertOk()
            ->assertSee('data-initial-settings-tab="politica"', false);
    }

    /** @test */
    public function test_network_admin_can_configure_the_organizational_policies(): void
    {
        $this->put(route('admin.network.settings.update'), array_merge([
            'name' => $this->network->name,
            'theme_mode' => 'system',
            'policies' => [
                'pessoas' => [
                    'scope' => 'unit',
                    'management_scope' => 'region',
                    'share_across_units' => '0',
                    'primary_unit_required' => '1',
                    'transfer_allowed' => '1',
                    'cpf_unique_scope' => 'unit',
                ],
                'planos' => [
                    'scope' => 'region',
                    'management_scope' => 'network',
                    'inherit_definitions' => '1',
                    'allow_local_plans' => '1',
                    'require_approval' => '1',
                    'price_by_unit' => '0',
                    'student_visibility' => 'region',
                    'enforce_student_unit' => '1',
                ],
                'modalidades' => [
                    'scope' => 'network',
                    'management_scope' => 'unit',
                    'inherit_definitions' => '1',
                    'allow_local_modalities' => '0',
                    'availability' => 'all',
                    'allow_cross_unit' => '0',
                ],
                'equipe' => [
                    'scope' => 'network',
                    'management_scope' => 'network',
                    'allow_multiple_vinculos' => '1',
                    'require_network_approval' => '0',
                    'unit_manages_users' => '0',
                ],
                'governanca' => [
                    'unit_autonomy' => 'high',
                    'regions_enabled' => '1',
                    'audit_policy_changes' => '1',
                ],
            ],
        ], $this->themePayload()))->assertRedirect(route('admin.network.settings.edit'));

        $policies = $this->network->fresh()->settings['policies'];

        $this->assertSame('unit', $policies['pessoas']['scope']);
        $this->assertSame('region', $policies['pessoas']['management_scope']);
        $this->assertFalse($policies['pessoas']['share_across_units']);
        $this->assertSame('unit', $policies['pessoas']['cpf_unique_scope']);
        $this->assertSame('region', $policies['planos']['student_visibility']);
        $this->assertTrue($policies['planos']['allow_local_plans']);
        $this->assertSame('all', $policies['modalidades']['availability']);
        $this->assertFalse($policies['modalidades']['allow_cross_unit']);
        $this->assertTrue($policies['equipe']['allow_multiple_vinculos']);
        $this->assertSame('high', $policies['governanca']['unit_autonomy']);
    }

    /** @test */
    public function test_organizational_policies_are_validated_and_default_when_missing(): void
    {
        $this->put(route('admin.network.settings.update'), array_merge([
            'name' => $this->network->name,
            'theme_mode' => 'system',
            'policies' => [
                'pessoas' => ['scope' => 'invalid-scope'],
            ],
        ], $this->themePayload()))->assertSessionHasErrors('policies.pessoas.scope');

        $this->put(route('admin.network.settings.update'), array_merge([
            'name' => $this->network->name,
            'theme_mode' => 'system',
        ], $this->themePayload()))->assertRedirect(route('admin.network.settings.edit'));

        $policies = $this->network->fresh()->settings['policies'];

        $this->assertSame('network', $policies['pessoas']['scope']);
        $this->assertTrue($policies['pessoas']['primary_unit_required']);
    }

    /** @test */
    public function test_logo_route_does_not_serve_files_from_another_network(): void
    {
        Storage::fake('public');

        $path = UploadedFile::fake()->image('logo.png', 512, 512)->store('networks/999', 'public');

        $settings = $this->network->settings;
        $settings['logo'] = $path;
        $this->network->update(['settings' => $settings]);

        $this->get(route('network.logo', $this->network))->assertNotFound();
    }

    /** @test */
    public function test_logo_upload_requires_a_valid_image(): void
    {
        Storage::fake('public');

        $this->put(route('admin.network.settings.update'), array_merge([
            'name' => $this->network->name,
            'theme_mode' => 'system',
            'logo' => UploadedFile::fake()->create('logo.txt', 10, 'text/plain'),
        ], $this->themePayload()))->assertSessionHasErrors('logo');
    }

    /** @test */
    public function test_network_theme_css_and_company_name_are_rendered(): void
    {
        $settings = $this->network->settings;
        $settings['company_name'] = 'Academia Forte';
        $settings['theme_mode'] = 'dark';
        $settings['theme'] = [
            'dark' => array_merge(NetworkTheme::DEFAULTS['dark'], ['background_primary' => '#112233']),
            'light' => NetworkTheme::DEFAULTS['light'],
        ];

        $this->network->update(['settings' => $settings]);

        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Academia Forte')
            ->assertSee(':root.dark{', false)
            ->assertSee('--color-bg-primary:#112233', false)
            ->assertSee('--color-success-soft:', false)
            ->assertSee('--color-info:', false)
            ->assertSee('--color-on-danger:', false);
    }

    /** @test */
    public function test_flash_messages_use_the_network_theme_colors(): void
    {
        $this->withSession(['success' => 'Configurações salvas.'])
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('var(--color-success-soft)', false)
            ->assertSee('var(--color-on-success)', false);
    }

    /** @test */
    public function test_network_admin_region_creation_is_forced_to_their_network(): void
    {
        $otherNetwork = $this->createOtherNetwork();

        $this->postJson(route('admin.regions.store'), [
            'network_id' => $otherNetwork->id,
            'name' => 'Região Forçada',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('regions', [
            'name' => 'Região Forçada',
            'network_id' => $this->network->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function test_network_admin_unit_creation_is_forced_to_their_network(): void
    {
        $otherNetwork = $this->createOtherNetwork();
        $otherRegion = Region::create([
            'network_id' => $otherNetwork->id,
            'name' => 'Região Externa',
            'status' => 'active',
        ]);

        $this->postJson(route('admin.units.store'), [
            'network_id' => $otherNetwork->id,
            'name' => 'Unidade Forçada',
            'identifier' => 'UNID-FORCADA',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('units', [
            'name' => 'Unidade Forçada',
            'network_id' => $this->network->id,
            'status' => 'active',
        ]);

        $this->postJson(route('admin.units.store'), [
            'network_id' => $this->network->id,
            'region_id' => $otherRegion->id,
            'name' => 'Unidade Região Externa',
            'identifier' => 'UNID-REGIAO-EXTERNA',
        ])->assertStatus(422)->assertJsonValidationErrors('region_id');
    }

    /** @test */
    public function test_network_admin_user_creation_is_scoped_and_cannot_use_platform_roles(): void
    {
        $otherNetwork = $this->createOtherNetwork();
        $otherRegion = Region::create([
            'network_id' => $otherNetwork->id,
            'name' => 'Região Externa',
            'status' => 'active',
        ]);

        $networkRole = Role::where('name', 'network_admin')->firstOrFail();
        $person = Person::factory()->staff()->create(['network_id' => $this->network->id]);

        $this->postJson(route('admin.users.store'), [
            'person_id' => $person->id,
            'name' => 'Usuário da Rede',
            'email' => 'usuario.rede@smartgym.com.br',
            'password' => 'Senha12345',
            'password_confirmation' => 'Senha12345',
            'network_id' => $otherNetwork->id,
            'role_id' => $networkRole->id,
            'vinculo_status' => 'active',
        ])->assertOk()->assertJsonPath('success', true);

        $user = User::where('email', 'usuario.rede@smartgym.com.br')->firstOrFail();

        $this->assertDatabaseHas('user_vinculos', [
            'user_id' => $user->id,
            'network_id' => $this->network->id,
            'role_id' => $networkRole->id,
        ]);

        $this->postJson(route('admin.users.store'), [
            'person_id' => $person->id,
            'name' => 'Usuário Plataforma',
            'email' => 'usuario.plataforma@smartgym.com.br',
            'password' => 'Senha12345',
            'password_confirmation' => 'Senha12345',
            'role_id' => Role::where('name', 'platform_admin')->firstOrFail()->id,
            'vinculo_status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('role_id');

        $this->postJson(route('admin.users.store'), [
            'person_id' => $person->id,
            'name' => 'Usuário Região Externa',
            'email' => 'usuario.regiao.externa@smartgym.com.br',
            'password' => 'Senha12345',
            'password_confirmation' => 'Senha12345',
            'network_id' => $this->network->id,
            'region_id' => $otherRegion->id,
            'role_id' => $networkRole->id,
            'vinculo_status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('region_id');
    }

    /** @test */
    public function test_network_admin_role_management_is_scoped_to_their_network(): void
    {
        $allowedPermission = Permission::where('key', 'students.view')->firstOrFail();
        $blockedPermission = Permission::where('key', 'admin.access')->firstOrFail();

        $this->postJson(route('admin.roles.store'), [
            'display_name' => 'Perfil da Rede',
            'level' => 'unit',
            'description' => 'Perfil personalizado da rede',
            'permissions' => [$allowedPermission->id],
        ])->assertOk()->assertJsonPath('success', true);

        $role = Role::where('display_name', 'Perfil da Rede')->firstOrFail();

        $this->assertSame($this->network->id, $role->network_id);
        $this->assertTrue($role->permissions->contains('id', $allowedPermission->id));

        $this->postJson(route('admin.roles.store'), [
            'display_name' => 'Perfil Escalada',
            'level' => 'unit',
            'permissions' => [$blockedPermission->id],
        ])->assertStatus(422)->assertJsonValidationErrors('permissions');

        $otherNetwork = $this->createOtherNetwork();
        $otherRole = Role::create([
            'name' => 'perfil_externo',
            'display_name' => 'Perfil Externo',
            'level' => 'unit',
            'network_id' => $otherNetwork->id,
        ]);

        $this->get(route('admin.roles.index'))
            ->assertOk()
            ->assertDontSee('Perfil Externo');

        $this->putJson(route('admin.roles.update', $otherRole), [
            'display_name' => 'Perfil Externo Alterado',
            'level' => 'unit',
            'permissions' => [$allowedPermission->id],
        ])->assertForbidden();
    }

    /** @test */
    public function test_network_admin_cannot_delete_users_with_links_outside_their_network(): void
    {
        $otherNetwork = $this->createOtherNetwork();
        $role = Role::where('name', 'network_admin')->firstOrFail();

        $user = User::factory()->create(['email' => 'multirede@smartgym.com.br']);

        UserVinculo::create([
            'user_id' => $user->id,
            'network_id' => $this->network->id,
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        UserVinculo::create([
            'user_id' => $user->id,
            'network_id' => $otherNetwork->id,
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->deleteJson(route('admin.users.destroy', $user))
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function themePayload(): array
    {
        $dark = NetworkTheme::DEFAULTS['dark'];
        $light = NetworkTheme::DEFAULTS['light'];

        $dark['background_primary'] = '#101010';
        $light['background_primary'] = '#EFEFEF';

        return [
            'theme_mode' => 'dark',
            'theme' => [
                'dark' => $dark,
                'light' => $light,
            ],
        ];
    }

    private function createOtherNetwork(): Network
    {
        return Network::create([
            'name' => 'Rede Externa',
            'slug' => 'rede-externa',
            'status' => 'active',
        ]);
    }
}
