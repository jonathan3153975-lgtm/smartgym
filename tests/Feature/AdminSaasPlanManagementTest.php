<?php

namespace Tests\Feature;

use App\Models\Network;
use App\Models\Role;
use App\Models\SaasPlan;
use App\Models\User;
use App\Models\UserVinculo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSaasPlanManagementTest extends TestCase
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
    public function test_developer_can_access_plans_panel(): void
    {
        $response = $this->get(route('admin.plans.index'));

        $response->assertOk();
        $response->assertSee('Cadastro de planos');
        $response->assertSee('Enterprise');
    }

    /** @test */
    public function test_only_developer_profiles_can_access_plans_panel(): void
    {
        $support = User::factory()->create([
            'email' => 'suporte@smartgym.com.br',
        ]);

        $supportRole = Role::where('name', 'platform_support')->firstOrFail();

        UserVinculo::create([
            'user_id' => $support->id,
            'role_id' => $supportRole->id,
            'status' => 'active',
            'granted_by' => $this->platformAdmin->id,
        ]);

        $this->actingAs($support)
            ->get(route('admin.plans.index'))
            ->assertForbidden();
    }

    /** @test */
    public function test_plan_is_created_with_generated_slug(): void
    {
        $response = $this->postJson(route('admin.plans.store'), [
            'name' => 'Plano Premium',
            'description' => 'Plano com benefícios extras',
            'status' => 'active',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('saas_plans', [
            'name' => 'Plano Premium',
            'slug' => 'plano-premium',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function test_plan_can_be_updated(): void
    {
        $plan = SaasPlan::where('slug', 'enterprise')->firstOrFail();

        $response = $this->putJson(route('admin.plans.update', $plan), [
            'name' => 'Enterprise Plus',
            'description' => 'Nova descrição',
            'status' => 'inactive',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $plan->refresh();

        $this->assertSame('Enterprise Plus', $plan->name);
        $this->assertSame('inactive', $plan->status);
    }

    /** @test */
    public function test_plan_can_be_deleted(): void
    {
        $plan = SaasPlan::where('slug', 'starter')->firstOrFail();

        $this->deleteJson(route('admin.plans.destroy', $plan))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('saas_plans', ['id' => $plan->id]);
    }

    /** @test */
    public function test_parameter_is_created_with_formatted_money(): void
    {
        $plan = SaasPlan::where('slug', 'enterprise')->firstOrFail();

        $response = $this->postJson(route('admin.plans.parameters.store', $plan), [
            'description' => 'Taxa de adesão',
            'value' => '1.000,00',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseHas('saas_plan_parameters', [
            'saas_plan_id' => $plan->id,
            'description' => 'Taxa de adesão',
            'value' => 1000.00,
        ]);
    }

    /** @test */
    public function test_parameter_can_be_updated_and_deleted(): void
    {
        $plan = SaasPlan::where('slug', 'enterprise')->firstOrFail();
        $parameter = $plan->parameters()->create([
            'description' => 'Taxa mensal por unidade',
            'value' => 200,
        ]);

        $this->putJson(route('admin.plans.parameters.update', [$plan, $parameter]), [
            'description' => 'Taxa mensal por unidade',
            'value' => '250,50',
        ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('250.50', $parameter->fresh()->value);

        $this->deleteJson(route('admin.plans.parameters.destroy', [$plan, $parameter]))
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('saas_plan_parameters', ['id' => $parameter->id]);
    }

    /** @test */
    public function test_parameter_from_another_plan_cannot_be_changed(): void
    {
        $plan = SaasPlan::where('slug', 'enterprise')->firstOrFail();
        $otherPlan = SaasPlan::where('slug', 'starter')->firstOrFail();

        $parameter = $otherPlan->parameters()->create([
            'description' => 'Parâmetro de outro plano',
            'value' => 50,
        ]);

        $this->putJson(route('admin.plans.parameters.update', [$plan, $parameter]), [
            'description' => 'Alterado',
            'value' => '10,00',
        ])->assertNotFound();

        $this->deleteJson(route('admin.plans.parameters.destroy', [$plan, $parameter]))
            ->assertNotFound();
    }

    /** @test */
    public function test_plan_json_exposes_parameters_for_the_modal(): void
    {
        $plan = SaasPlan::where('slug', 'enterprise')->firstOrFail();
        $plan->parameters()->create([
            'description' => 'Taxa de adesão',
            'value' => 1000,
        ]);

        $response = $this->getJson(route('admin.plans.show', $plan));

        $response->assertOk();
        $response->assertJsonPath('name', $plan->name);
        $response->assertJsonPath('parameters.0.description', 'Taxa de adesão');
    }

    /** @test */
    public function test_network_form_lists_only_active_plans(): void
    {
        SaasPlan::create([
            'name' => 'Plano Visível Ativo',
            'slug' => 'plano-visivel-ativo',
            'status' => 'active',
        ]);

        SaasPlan::create([
            'name' => 'Plano Oculto Inativo',
            'slug' => 'plano-oculto-inativo',
            'status' => 'inactive',
        ]);

        $response = $this->get(route('admin.networks.index'));

        $response->assertOk();
        $response->assertSee('Plano Visível Ativo');
        $response->assertDontSee('Plano Oculto Inativo');
    }

    /** @test */
    public function test_network_edit_json_exposes_the_current_plan_name(): void
    {
        $plan = SaasPlan::where('slug', 'enterprise')->firstOrFail();
        $network = Network::where('slug', 'smartgym-demo')->firstOrFail();

        $network->update(['plan_id' => $plan->id]);

        $response = $this->getJson(route('admin.networks.show', $network));

        $response->assertOk();
        $response->assertJsonPath('plan_id', $plan->id);
        $response->assertJsonPath('plan_name', $plan->name);
    }
}
