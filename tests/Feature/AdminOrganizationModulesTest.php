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
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrganizationModulesTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
        $this->platformAdmin = User::where('email', 'jonathan3153975@gmail.com')->firstOrFail();
        $this->actingAs($this->platformAdmin);
    }

    /** @test */
    public function test_admin_dashboard_is_available(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Painel administrativo')
            ->assertSee('Redes')
            ->assertSee('Planos');
    }

    /** @test */
    public function test_cadastros_submenu_follows_the_required_order(): void
    {
        $html = $this->get(route('admin.networks.index'))->assertOk()->getContent();

        $expectedOrder = [
            '<span>Redes</span>',
            '<span>Regiões</span>',
            '<span>Unidades</span>',
            '<span>Pessoas</span>',
            '<span>Perfis</span>',
            '<span>Usuários</span>',
            '<span>Planos SaaS</span>',
        ];

        $previousPosition = -1;

        foreach ($expectedOrder as $item) {
            $position = mb_strpos($html, $item);

            $this->assertNotFalse($position, "Item {$item} não encontrado no menu.");
            $this->assertGreaterThan($previousPosition, $position, "Item {$item} está fora de ordem.");
            $previousPosition = $position;
        }
    }

    /** @test */
    public function test_network_cadastros_submenu_follows_the_required_order(): void
    {
        $networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->actingAs($networkAdmin);

        $html = $this->get(route('admin.dashboard'))->assertOk()->getContent();

        $expectedOrder = [
            '<span>Regiões</span>',
            '<span>Unidades</span>',
            '<span>Pessoas</span>',
            '<span>Perfis</span>',
            '<span>Usuários</span>',
            '<span>Modalidades</span>',
            '<span>Planos</span>',
            '<span>Turmas</span>',
            '<span>Alunos</span>',
        ];

        $previousPosition = -1;

        foreach ($expectedOrder as $item) {
            $position = mb_strpos($html, $item);

            $this->assertNotFalse($position, "Item {$item} não encontrado no menu.");
            $this->assertGreaterThan($previousPosition, $position, "Item {$item} está fora de ordem.");
            $previousPosition = $position;
        }

        $this->assertStringNotContainsString('<span>Redes</span>', $html);
        $this->assertStringNotContainsString('<span>Planos SaaS</span>', $html);
    }

    /** @test */
    public function test_region_module_supports_crud_and_status_toggle(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();

        $this->get(route('admin.regions.index'))
            ->assertOk()
            ->assertSee('Cadastro de regiões');

        $this->postJson(route('admin.regions.store'), [
            'network_id' => $network->id,
            'name' => 'Região Centro',
            'description' => 'Unidades centrais',
        ])->assertOk()->assertJsonPath('success', true);

        $region = Region::where('name', 'Região Centro')->firstOrFail();
        $this->assertSame('active', $region->status);

        $unit = Unit::create([
            'network_id' => $network->id,
            'region_id' => $region->id,
            'name' => 'Unidade da Região Centro',
            'identifier' => 'UNID-REGIAO-CENTRO',
            'status' => 'active',
        ]);

        $this->putJson(route('admin.regions.update', $region), [
            'name' => 'Região Centro-Oeste',
            'description' => 'Atualizada',
        ])->assertOk()->assertJsonPath('success', true);

        $this->patchJson(route('admin.regions.toggle-status', $region))
            ->assertOk()
            ->assertJsonPath('status', 'inactive');

        $this->deleteJson(route('admin.regions.destroy', $region))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('regions', ['id' => $region->id]);
        $this->assertNull($unit->fresh()->region_id);
    }

    /** @test */
    public function test_region_form_is_available_in_the_network_page(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();

        $this->get(route('admin.networks.show', $network))
            ->assertOk()
            ->assertSee('Nova região')
            ->assertSee('Nova unidade')
            ->assertSee('Visualização da estrutura');
    }

    /** @test */
    public function test_unit_module_supports_crud_and_status_toggle(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $region = Region::where('network_id', $network->id)->firstOrFail();

        $this->get(route('admin.units.index'))
            ->assertOk()
            ->assertSee('Cadastro de unidades');

        $this->postJson(route('admin.units.store'), [
            'network_id' => $network->id,
            'region_id' => $region->id,
            'name' => 'Unidade Centro',
            'city' => 'Curitiba',
            'state' => 'pr',
            'latitude' => -25.4284,
            'longitude' => -49.2733,
        ])->assertOk()->assertJsonPath('success', true);

        $unit = Unit::where('name', 'Unidade Centro')->firstOrFail();
        $this->assertMatchesRegularExpression('/^UNID\d{3,}$/', $unit->identifier);
        $this->assertSame('active', $unit->status);
        $this->assertSame('PR', $unit->state);
        $this->assertEqualsWithDelta(-25.4284, $unit->latitude, 0.0000001);
        $this->assertEqualsWithDelta(-49.2733, $unit->longitude, 0.0000001);

        $this->postJson(route('admin.units.store'), [
            'network_id' => $network->id,
            'name' => 'Unidade Coordenada Inválida',
            'latitude' => 100,
            'longitude' => 200,
        ])->assertStatus(422)->assertJsonValidationErrors(['latitude', 'longitude']);

        $this->putJson(route('admin.units.update', $unit), [
            'network_id' => $network->id,
            'region_id' => $region->id,
            'name' => 'Unidade Centro Novo',
            'status' => 'maintenance',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame('maintenance', $unit->fresh()->status);

        $this->patchJson(route('admin.units.toggle-status', $unit))
            ->assertOk()
            ->assertJsonPath('status', 'active');

        $this->deleteJson(route('admin.units.destroy', $unit))
            ->assertOk();

        $this->assertSoftDeleted('units', ['id' => $unit->id]);
    }

    /** @test */
    public function test_user_module_supports_crud_and_self_deletion_is_blocked(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $role = Role::where('name', 'network_admin')->where('level', 'network')->firstOrFail();
        $person = Person::factory()->staff()->create(['network_id' => $network->id]);

        $this->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Cadastro de usuários');

        $this->postJson(route('admin.users.store'), [
            'person_id' => $person->id,
            'name' => 'Usuário Teste',
            'email' => 'usuario.teste@smartgym.com.br',
            'password' => 'Senha12345',
            'password_confirmation' => 'Senha12345',
            'network_id' => $network->id,
            'role_id' => $role->id,
            'vinculo_status' => 'active',
        ])->assertOk()->assertJsonPath('success', true);

        $user = User::where('email', 'usuario.teste@smartgym.com.br')->firstOrFail();
        $this->assertSame($person->id, $user->person_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('user_vinculos', [
            'user_id' => $user->id,
            'network_id' => $network->id,
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        $this->putJson(route('admin.users.update', $user), [
            'person_id' => $person->id,
            'name' => 'Usuário Teste Editado',
            'email' => 'usuario.teste@smartgym.com.br',
            'network_id' => $network->id,
            'role_id' => $role->id,
            'vinculo_status' => 'active',
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame('Usuário Teste Editado', $user->fresh()->name);

        $this->patchJson(route('admin.users.toggle-status', $user))
            ->assertOk()
            ->assertJsonPath('status', 'inactive');

        $this->deleteJson(route('admin.users.destroy', $user))
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        $this->deleteJson(route('admin.users.destroy', $this->platformAdmin))
            ->assertStatus(422);
    }

    /** @test */
    public function test_user_scope_must_be_consistent(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();

        $otherNetwork = Network::create([
            'name' => 'Rede Isolada',
            'slug' => 'rede-isolada',
            'status' => 'active',
        ]);

        $otherRegion = Region::create([
            'network_id' => $otherNetwork->id,
            'name' => 'Região Isolada',
            'status' => 'active',
        ]);

        $role = Role::where('name', 'network_admin')->firstOrFail();
        $person = Person::factory()->staff()->create(['network_id' => $network->id]);

        $this->postJson(route('admin.users.store'), [
            'person_id' => $person->id,
            'name' => 'Usuário Inválido',
            'email' => 'invalido@smartgym.com.br',
            'password' => 'Senha12345',
            'password_confirmation' => 'Senha12345',
            'network_id' => $network->id,
            'region_id' => $otherRegion->id,
            'role_id' => $role->id,
            'vinculo_status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('region_id');
    }

    /** @test */
    public function test_role_module_supports_crud_and_protects_system_roles(): void
    {
        $this->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('Cadastro de perfis');

        $permissionIds = Permission::limit(2)->pluck('id')->all();

        $this->postJson(route('admin.roles.store'), [
            'display_name' => 'Perfil Personalizado',
            'level' => 'unit',
            'description' => 'Perfil de teste',
            'permissions' => $permissionIds,
        ])->assertOk()->assertJsonPath('success', true);

        $role = Role::where('display_name', 'Perfil Personalizado')->firstOrFail();
        $this->assertFalse($role->is_system);
        $this->assertCount(2, $role->permissions);

        $this->putJson(route('admin.roles.update', $role), [
            'display_name' => 'Perfil Personalizado Editado',
            'level' => 'unit',
            'permissions' => [$permissionIds[0]],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertCount(1, $role->fresh()->permissions);

        $systemRole = Role::where('name', 'platform_admin')->firstOrFail();

        $this->putJson(route('admin.roles.update', $systemRole), [
            'display_name' => 'Alterado',
            'level' => 'platform',
        ])->assertStatus(422);

        $this->deleteJson(route('admin.roles.destroy', $systemRole))
            ->assertStatus(422);

        $this->deleteJson(route('admin.roles.destroy', $role))
            ->assertOk();
    }

    /** @test */
    public function test_network_page_includes_org_chart_and_map_libraries(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();

        $response = $this->get(route('admin.networks.show', $network));

        $response->assertOk();
        $response->assertSee('vis-network@9.1.9', false);
        $response->assertSee('leaflet@1.9.4', false);
        $response->assertSee('orgChart', false);
        $response->assertSee('networkMap', false);
    }

    /** @test */
    public function test_support_profile_cannot_access_management_modules(): void
    {
        $support = User::factory()->create(['email' => 'suporte.modulos@smartgym.com.br']);
        $supportRole = Role::where('name', 'platform_support')->firstOrFail();

        UserVinculo::create([
            'user_id' => $support->id,
            'role_id' => $supportRole->id,
            'status' => 'active',
            'granted_by' => $this->platformAdmin->id,
        ]);

        $this->actingAs($support);

        $this->get(route('admin.dashboard'))->assertOk();

        foreach (['admin.regions.index', 'admin.units.index', 'admin.users.index', 'admin.roles.index', 'admin.plans.index'] as $routeName) {
            $this->get(route($routeName))->assertForbidden();
        }
    }
}
