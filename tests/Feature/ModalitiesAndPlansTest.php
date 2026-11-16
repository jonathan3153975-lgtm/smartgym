<?php

namespace Tests\Feature;

use App\Models\MembershipPlan;
use App\Models\Modality;
use App\Models\ModalityCategory;
use App\Models\Network;
use App\Models\Region;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModalitiesAndPlansTest extends TestCase
{
    use RefreshDatabase;

    private User $networkAdmin;

    private User $unitAdmin;

    private Network $network;

    private Region $region;

    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->unitAdmin = User::where('email', 'gerente@smartgym.com.br')->firstOrFail();
        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $this->region = Region::where('network_id', $this->network->id)->firstOrFail();
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();
    }

    /** @test */
    public function test_network_admin_can_create_a_modality_and_link_units(): void
    {
        $this->actingAs($this->networkAdmin);

        $category = ModalityCategory::create([
            'network_id' => $this->network->id,
            'name' => 'Coletivas',
        ]);

        $this->postJson(route('admin.modalities.store'), [
            'name' => 'Crossfit',
            'modality_category_id' => $category->id,
        ])->assertOk()->assertJsonPath('success', true);

        $modality = Modality::where('name', 'Crossfit')->firstOrFail();

        $this->assertSame('network', $modality->created_level);
        $this->assertSame($this->network->id, $modality->network_id);
        $this->assertSame($this->networkAdmin->id, $modality->created_by);

        $this->get(route('admin.modalities.index'))
            ->assertOk()
            ->assertSee('Crossfit')
            ->assertSee('Coletivas');

        $units = Unit::where('network_id', $this->network->id)->pluck('id')->all();

        $this->putJson(route('admin.modalities.units.sync', $modality), [
            'unit_ids' => $units,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertEqualsCanonicalizing($units, $modality->units()->pluck('units.id')->all());
    }

    /** @test */
    public function test_unit_admin_sees_network_modalities_but_cannot_edit_them(): void
    {
        $modality = Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Musculação',
            'created_by' => $this->networkAdmin->id,
        ]);

        $this->actingAs($this->unitAdmin);

        $this->get(route('admin.modalities.index'))
            ->assertOk()
            ->assertSee('Musculação');

        $this->putJson(route('admin.modalities.update', $modality), [
            'name' => 'Alterada',
        ])->assertForbidden();

        $this->deleteJson(route('admin.modalities.destroy', $modality))
            ->assertForbidden();
    }

    /** @test */
    public function test_unit_admin_creates_a_unit_modality_and_network_admin_cannot_edit_it(): void
    {
        $this->actingAs($this->unitAdmin);

        $this->postJson(route('admin.modalities.store'), [
            'name' => 'Funcional',
        ])->assertOk()->assertJsonPath('success', true);

        $modality = Modality::where('name', 'Funcional')->firstOrFail();

        $this->assertSame('unit', $modality->created_level);
        $this->assertSame($this->unit->id, $modality->unit_id);
        $this->assertSame($this->region->id, $modality->region_id);
        $this->assertDatabaseHas('modality_units', [
            'modality_id' => $modality->id,
            'unit_id' => $this->unit->id,
        ]);

        $this->putJson(route('admin.modalities.update', $modality), [
            'name' => 'Funcional Atualizado',
        ])->assertOk();

        $this->actingAs($this->networkAdmin);

        $this->get(route('admin.modalities.index'))
            ->assertOk()
            ->assertSee('Funcional Atualizado');

        $this->putJson(route('admin.modalities.update', $modality), [
            'name' => 'Tentativa da rede',
        ])->assertForbidden();

        $this->deleteJson(route('admin.modalities.destroy', $modality))
            ->assertForbidden();
    }

    /** @test */
    public function test_modality_categories_support_crud_and_protect_linked_categories(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->postJson(route('admin.modality-categories.store'), [
            'name' => 'Lutas',
        ])->assertOk()->assertJsonPath('success', true);

        $category = ModalityCategory::where('name', 'Lutas')->firstOrFail();

        $this->postJson(route('admin.modality-categories.store'), [
            'name' => 'Lutas',
        ])->assertStatus(422)->assertJsonValidationErrors('name');

        $this->putJson(route('admin.modality-categories.update', $category), [
            'name' => 'Artes Marciais',
        ])->assertOk();

        $modality = Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'modality_category_id' => $category->id,
            'name' => 'Jiu-Jitsu',
        ]);

        $this->deleteJson(route('admin.modality-categories.destroy', $category))
            ->assertStatus(422);

        $modality->delete();

        $this->deleteJson(route('admin.modality-categories.destroy', $category))
            ->assertOk();
    }

    /** @test */
    public function test_network_admin_can_create_and_update_membership_plans(): void
    {
        $this->actingAs($this->networkAdmin);

        $modality = Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Musculação',
        ]);

        $units = Unit::where('network_id', $this->network->id)->pluck('id')->all();

        $this->postJson(route('admin.membership-plans.store'), [
            'name' => 'Mensal Premium',
            'price' => '199.90',
            'period' => 'monthly',
            'type' => MembershipPlan::TYPE_MIXED,
            'weekly_accesses' => 5,
            'daily_checkins' => 2,
            'checkin_advance_minutes' => 30,
            'all_units' => '0',
            'unit_ids' => $units,
            'all_modalities' => '0',
            'modality_ids' => [$modality->id],
        ])->assertOk()->assertJsonPath('success', true);

        $plan = MembershipPlan::where('name', 'Mensal Premium')->firstOrFail();

        $this->assertSame('network', $plan->created_level);
        $this->assertSame(MembershipPlan::TYPE_MIXED, $plan->type);
        $this->assertSame('Misto', $plan->type_label);
        $this->assertSame(30, $plan->duration_days);
        $this->assertSame(5, $plan->weekly_accesses);
        $this->assertSame(2, $plan->daily_checkins);
        $this->assertSame(30, $plan->checkin_advance_minutes);
        $this->assertFalse($plan->all_units);
        $this->assertFalse($plan->all_modalities);
        $this->assertEqualsCanonicalizing($units, $plan->units()->pluck('units.id')->all());
        $this->assertTrue($plan->modalities->contains('id', $modality->id));

        $this->get(route('admin.membership-plans.index'))
            ->assertOk()
            ->assertSee('Mensal Premium')
            ->assertSee('30 dias de vigência');

        $this->getJson(route('admin.membership-plans.edit-data', $plan))
            ->assertOk()
            ->assertJsonPath('period', 'monthly')
            ->assertJsonPath('type', MembershipPlan::TYPE_MIXED)
            ->assertJsonPath('checkin_advance_minutes', 30);

        $this->putJson(route('admin.membership-plans.update', $plan), [
            'name' => 'Anual Premium',
            'price' => '1999.00',
            'period' => 'annual',
            'type' => MembershipPlan::TYPE_BASIC,
            'weekly_accesses' => 7,
            'daily_checkins' => 1,
            'all_units' => '1',
        ])->assertOk();

        $plan = $plan->fresh();

        $this->assertSame(MembershipPlan::TYPE_BASIC, $plan->type);
        $this->assertSame(365, $plan->duration_days);
        $this->assertTrue($plan->all_units);
        $this->assertFalse($plan->all_modalities);
        $this->assertSame(0, $plan->units()->count());
        $this->assertSame(0, $plan->modalities()->count());
    }

    /** @test */
    public function test_basic_plan_ignores_the_selected_modalities(): void
    {
        $this->actingAs($this->networkAdmin);

        $modality = Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Spinning',
        ]);

        $this->postJson(route('admin.membership-plans.store'), [
            'name' => 'Básico Livre',
            'price' => '99.90',
            'period' => 'monthly',
            'type' => MembershipPlan::TYPE_BASIC,
            'weekly_accesses' => 5,
            'daily_checkins' => 1,
            'all_units' => '1',
            'modality_ids' => [$modality->id],
        ])->assertOk()->assertJsonPath('success', true);

        $plan = MembershipPlan::where('name', 'Básico Livre')->firstOrFail();

        $this->assertTrue($plan->isBasic());
        $this->assertFalse($plan->includesClasses());
        $this->assertFalse($plan->all_modalities);
        $this->assertSame(0, $plan->modalities()->count());
        $this->assertSame('Acesso à academia', $plan->modality_access_label);
    }

    /** @test */
    public function test_classes_plan_requires_modalities_and_derives_daily_checkins(): void
    {
        $this->actingAs($this->networkAdmin);

        $units = Unit::where('network_id', $this->network->id)->pluck('id')->all();

        $first = Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Pilates',
        ]);

        $second = Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Funcional',
        ]);

        $this->postJson(route('admin.membership-plans.store'), [
            'name' => 'Somente Aulas',
            'price' => '249.90',
            'period' => 'monthly',
            'type' => MembershipPlan::TYPE_CLASSES,
            'weekly_accesses' => 4,
            'daily_checkins' => 1,
            'checkin_advance_minutes' => 25,
            'all_units' => '0',
            'unit_ids' => $units,
            'modality_ids' => [$first->id, $second->id],
        ])->assertOk()->assertJsonPath('success', true);

        $plan = MembershipPlan::where('name', 'Somente Aulas')->firstOrFail();

        $this->assertSame(MembershipPlan::TYPE_CLASSES, $plan->type);
        $this->assertSame(2, $plan->daily_checkins);
        $this->assertSame(25, $plan->checkin_advance_minutes);
        $this->assertFalse($plan->all_units);
        $this->assertCount(2, $plan->modalities);

        $this->postJson(route('admin.membership-plans.store'), [
            'name' => 'Aulas sem modalidade',
            'period' => 'monthly',
            'type' => MembershipPlan::TYPE_CLASSES,
            'weekly_accesses' => 4,
            'daily_checkins' => 1,
            'all_units' => '1',
        ])->assertStatus(422)->assertJsonValidationErrors('modality_ids');
    }

    /** @test */
    public function test_classes_plan_only_accepts_units_that_offer_the_selected_modalities(): void
    {
        $this->actingAs($this->networkAdmin);

        $otherUnit = Unit::where('network_id', $this->network->id)
            ->where('id', '!=', $this->unit->id)
            ->firstOrFail();

        $modality = Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Yoga',
        ]);
        $modality->units()->sync([$this->unit->id]);

        $payload = [
            'name' => 'Yoga na Unidade',
            'period' => 'monthly',
            'type' => MembershipPlan::TYPE_CLASSES,
            'weekly_accesses' => 3,
            'daily_checkins' => 1,
            'all_units' => '0',
            'modality_ids' => [$modality->id],
        ];

        $this->postJson(route('admin.membership-plans.store'), array_merge($payload, [
            'unit_ids' => [$otherUnit->id],
        ]))->assertStatus(422)->assertJsonValidationErrors('unit_ids');

        $this->postJson(route('admin.membership-plans.store'), array_merge($payload, [
            'unit_ids' => [$this->unit->id],
        ]))->assertOk()->assertJsonPath('success', true);
    }

    /** @test */
    public function test_unit_admin_plans_are_limited_to_their_scope_and_creation_level(): void
    {
        $networkPlan = MembershipPlan::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Plano da Rede',
            'period' => 'monthly',
            'duration_days' => 30,
            'all_units' => true,
            'all_modalities' => true,
        ]);

        $this->actingAs($this->unitAdmin);

        $this->get(route('admin.membership-plans.index'))
            ->assertOk()
            ->assertSee('Plano da Rede');

        $this->putJson(route('admin.membership-plans.update', $networkPlan), [
            'name' => 'Tentativa',
            'period' => 'monthly',
            'type' => MembershipPlan::TYPE_BASIC,
            'weekly_accesses' => 7,
            'daily_checkins' => 1,
        ])->assertForbidden();

        $otherUnit = Unit::where('network_id', $this->network->id)
            ->where('id', '!=', $this->unit->id)
            ->firstOrFail();

        $this->postJson(route('admin.membership-plans.store'), [
            'name' => 'Plano Unidade Inválido',
            'period' => 'monthly',
            'type' => MembershipPlan::TYPE_BASIC,
            'weekly_accesses' => 7,
            'daily_checkins' => 1,
            'all_units' => '0',
            'unit_ids' => [$otherUnit->id],
        ])->assertStatus(422)->assertJsonValidationErrors('unit_ids');

        $this->postJson(route('admin.membership-plans.store'), [
            'name' => 'Plano da Unidade',
            'period' => 'quarterly',
            'type' => MembershipPlan::TYPE_BASIC,
            'weekly_accesses' => 6,
            'daily_checkins' => 1,
            'all_units' => '1',
        ])->assertOk()->assertJsonPath('success', true);

        $plan = MembershipPlan::where('name', 'Plano da Unidade')->firstOrFail();

        $this->assertSame('unit', $plan->created_level);
        $this->assertSame($this->unit->id, $plan->unit_id);
        $this->assertSame(90, $plan->duration_days);
    }

    /** @test */
    public function test_user_detail_shows_the_permissions_panel(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->get(route('admin.users.show', $this->networkAdmin))
            ->assertOk()
            ->assertSee('Permissões de acesso')
            ->assertSee('Gerenciar usuários')
            ->assertSee('Criar modalidades');
    }

    /** @test */
    public function test_forms_use_the_shared_phone_mask(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->get(route('admin.people.index'))
            ->assertOk()
            ->assertSee('data-phone', false);

        $this->get(route('admin.units.index'))
            ->assertOk()
            ->assertSee('data-phone', false);
    }
}
