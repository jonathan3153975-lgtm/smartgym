<?php

namespace Tests\Feature;

use App\Models\Network;
use App\Models\Role;
use App\Models\SaasPlan;
use App\Models\Student;
use App\Models\User;
use App\Models\UserVinculo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @test */
    public function test_login_page_is_accessible()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertSee('SmartGym');
    }

    /** @test */
    public function test_developer_can_login()
    {
        $response = $this->post(route('login'), [
            'email' => 'jonathan3153975@gmail.com',
            'password' => 'Jonathan315',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'jonathan3153975@gmail.com')->first());
    }

    /** @test */
    public function test_network_admin_can_login()
    {
        $response = $this->post(route('login'), [
            'email' => 'admin@smartgym.com.br',
            'password' => 'Admin123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'admin@smartgym.com.br')->first());
    }

    /** @test */
    public function test_unit_admin_can_login()
    {
        $response = $this->post(route('login'), [
            'email' => 'gerente@smartgym.com.br',
            'password' => 'Gerente123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'gerente@smartgym.com.br')->first());
    }

    /** @test */
    public function test_receptionist_can_login()
    {
        $response = $this->post(route('login'), [
            'email' => 'recepcao@smartgym.com.br',
            'password' => 'Recepcao123!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'recepcao@smartgym.com.br')->first());
    }

    /** @test */
    public function test_invalid_credentials_cannot_login()
    {
        $response = $this->post(route('login'), [
            'email' => 'jonathan3153975@gmail.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function test_authenticated_user_can_access_dashboard()
    {
        $user = User::where('email', 'jonathan3153975@gmail.com')->first();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    }

    /** @test */
    public function test_guest_cannot_access_dashboard()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function test_logout_works()
    {
        $user = User::where('email', 'jonathan3153975@gmail.com')->first();
        $this->actingAs($user);

        $response = $this->post(route('logout'));
        $response->assertRedirect('/');
        $this->assertGuest();
    }

    /** @test */
    public function test_register_page_is_accessible()
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);
        $response->assertSee('Criar conta');
    }

    /** @test */
    public function test_authenticated_user_can_access_profile()
    {
        $user = User::where('email', 'jonathan3153975@gmail.com')->first();
        $this->actingAs($user);

        $response = $this->get(route('profile.edit'));
        $response->assertStatus(200);
        $response->assertSee('Meu Perfil');
        $response->assertSee('Alterar Senha');
        $response->assertSee('Área de Perigo');
    }

    /** @test */
    public function test_guest_cannot_access_profile()
    {
        $response = $this->get(route('profile.edit'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function test_user_can_update_profile()
    {
        $user = User::where('email', 'jonathan3153975@gmail.com')->first();
        $this->actingAs($user);

        $response = $this->patch(route('profile.update'), [
            'name' => 'Novo Nome',
            'email' => 'novo@email.com',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success', 'Perfil atualizado com sucesso.');
        $this->assertEquals('Novo Nome', $user->fresh()->name);
        $this->assertEquals('novo@email.com', $user->fresh()->email);
    }

    /** @test */
    public function test_user_can_update_password()
    {
        $user = User::where('email', 'jonathan3153975@gmail.com')->first();
        $this->actingAs($user);

        $response = $this->put(route('profile.password'), [
            'current_password' => 'Jonathan315',
            'password' => 'NovaSenha123!',
            'password_confirmation' => 'NovaSenha123!',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success', 'Senha alterada com sucesso.');
        $this->assertTrue(Hash::check('NovaSenha123!', $user->fresh()->password));
    }

    /** @test */
    public function test_user_cannot_update_password_with_wrong_current()
    {
        $user = User::where('email', 'jonathan3153975@gmail.com')->first();
        $this->actingAs($user);

        $response = $this->put(route('profile.password'), [
            'current_password' => 'SenhaErrada',
            'password' => 'NovaSenha123!',
            'password_confirmation' => 'NovaSenha123!',
        ]);

        $response->assertSessionHasErrors('current_password');
    }
}

class TenantMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** @test */
    public function test_developer_has_platform_admin_role()
    {
        $developer = User::where('email', 'jonathan3153975@gmail.com')->first();
        $this->assertNotNull($developer);

        $vinculo = $developer->getActiveVinculo();
        $this->assertNotNull($vinculo);
        $this->assertEquals('platform_admin', $vinculo->role->name);
        $this->assertEquals('platform', $vinculo->role->level);
        $this->assertNull($vinculo->network_id);
    }

    /** @test */
    public function test_network_admin_has_network_role()
    {
        $networkAdmin = User::where('email', 'admin@smartgym.com.br')->first();
        $this->assertNotNull($networkAdmin);

        $vinculo = $networkAdmin->getActiveVinculo();
        $this->assertNotNull($vinculo);
        $this->assertEquals('network_admin', $vinculo->role->name);
        $this->assertEquals('network', $vinculo->role->level);
        $this->assertNotNull($vinculo->network_id);
    }

    /** @test */
    public function test_unit_admin_has_unit_role()
    {
        $unitAdmin = User::where('email', 'gerente@smartgym.com.br')->first();
        $this->assertNotNull($unitAdmin);

        $vinculo = $unitAdmin->getActiveVinculo();
        $this->assertNotNull($vinculo);
        $this->assertEquals('unit_admin', $vinculo->role->name);
        $this->assertEquals('unit', $vinculo->role->level);
        $this->assertNotNull($vinculo->network_id);
        $this->assertNotNull($vinculo->unit_id);
    }

    /** @test */
    public function test_permissions_are_assigned_to_roles()
    {
        $platformAdminRole = Role::where('name', 'platform_admin')->first();
        $this->assertTrue($platformAdminRole->hasPermission('admin.access'));
        $this->assertTrue($platformAdminRole->hasPermission('networks.manage'));
        $this->assertTrue($platformAdminRole->hasPermission('students.view'));

        $networkAdminRole = Role::where('name', 'network_admin')->first();
        $this->assertTrue($networkAdminRole->hasPermission('students.create'));
        $this->assertTrue($networkAdminRole->hasPermission('students.edit'));
        $this->assertFalse($networkAdminRole->hasPermission('networks.manage'));

        $unitAdminRole = Role::where('name', 'unit_admin')->first();
        $this->assertTrue($unitAdminRole->hasPermission('students.create'));
        $this->assertTrue($unitAdminRole->hasPermission('students.edit'));
        $this->assertFalse($unitAdminRole->hasPermission('users.manage'));
    }
}

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_networks_are_isolated()
    {
        // Create two networks
        $plan = SaasPlan::factory()->create(['slug' => 'test-plan']);

        $network1 = Network::factory()->create(['slug' => 'network-1', 'plan_id' => $plan->id]);
        $network2 = Network::factory()->create(['slug' => 'network-2', 'plan_id' => $plan->id]);

        // Create users for each network
        $user1 = User::factory()->create(['email' => 'user1@test.com']);
        $user2 = User::factory()->create(['email' => 'user2@test.com']);

        $role = Role::where('name', 'network_admin')->first();

        UserVinculo::create([
            'user_id' => $user1->id,
            'network_id' => $network1->id,
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        UserVinculo::create([
            'user_id' => $user2->id,
            'network_id' => $network2->id,
            'role_id' => $role->id,
            'status' => 'active',
        ]);

        // Create students for each network
        $student1 = Student::factory()->create([
            'network_id' => $network1->id,
            'name' => 'Student Network 1',
        ]);

        $student2 = Student::factory()->create([
            'network_id' => $network2->id,
            'name' => 'Student Network 2',
        ]);

        // User 1 should only see their network's students
        $this->actingAs($user1);
        $students = Student::all();
        $this->assertCount(1, $students);
        $this->assertEquals('Student Network 1', $students->first()->name);

        // User 2 should only see their network's students
        $this->actingAs($user2);
        $students = Student::all();
        $this->assertCount(1, $students);
        $this->assertEquals('Student Network 2', $students->first()->name);
    }
}
