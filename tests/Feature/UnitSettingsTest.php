<?php

namespace Tests\Feature;

use App\Models\DayPassPriceHistory;
use App\Models\MembershipPlan;
use App\Models\Modality;
use App\Models\Network;
use App\Models\Region;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentSaleItem;
use App\Models\StudentUnit;
use App\Models\Unit;
use App\Models\User;
use App\Support\Settings\DayPassPrice;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $networkAdmin;

    private User $unitAdmin;

    private Network $network;

    private Unit $unit;

    private Region $region;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->unitAdmin = User::where('email', 'gerente@smartgym.com.br')->firstOrFail();
        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();
        $this->region = Region::where('network_id', $this->network->id)->firstOrFail();
    }

    /** @test */
    public function test_unit_admin_can_set_the_day_pass_price_and_history_is_recorded(): void
    {
        $this->actingAs($this->unitAdmin);

        $this->get(route('unit.settings.edit'))
            ->assertOk()
            ->assertSee('Valor da diária')
            ->assertSee('Credenciais Mercado Pago');

        $this->put(route('unit.settings.update'), [
            'day_pass_price' => 39.90,
        ])->assertRedirect();

        $this->unit->refresh();

        $this->assertEquals(39.90, (float) $this->unit->settings[DayPassPrice::KEY]);
        $this->assertDatabaseHas('day_pass_price_histories', [
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'price' => 39.90,
        ]);

        $this->put(route('unit.settings.update'), [
            'inherit_day_pass_price' => 1,
        ])->assertRedirect();

        $this->unit->refresh();

        $this->assertArrayNotHasKey(DayPassPrice::KEY, $this->unit->settings);
        $this->assertSame(2, DayPassPriceHistory::where('unit_id', $this->unit->id)->count());
    }

    /** @test */
    public function test_day_pass_price_falls_back_from_unit_to_region_to_network(): void
    {
        $service = app(DayPassPrice::class);

        $this->network->update(['settings' => array_merge($this->network->settings, [
            DayPassPrice::KEY => 20,
        ])]);
        $this->unit->unsetRelation('network');

        $resolved = $service->resolve($this->unit->fresh());

        $this->assertEquals(20, $resolved['price']);
        $this->assertSame('network', $resolved['source']);

        $this->region->update(['settings' => [DayPassPrice::KEY => 30]]);
        $this->unit->unsetRelation('network');

        $resolved = $service->resolve($this->unit->fresh());

        $this->assertEquals(30, $resolved['price']);
        $this->assertSame('region', $resolved['source']);

        $this->unit->update(['settings' => [DayPassPrice::KEY => 40]]);

        $resolved = $service->resolve($this->unit->fresh());

        $this->assertEquals(40, $resolved['price']);
        $this->assertSame('unit', $resolved['source']);
    }

    /** @test */
    public function test_network_admin_can_set_the_default_day_pass_price_and_daily_plan_uses_it(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->network->update(['settings' => array_merge($this->network->settings, [
            DayPassPrice::KEY => 25,
        ])]);

        $this->postJson(route('admin.membership-plans.store'), [
            'name' => 'Diária da Rede',
            'price' => 999,
            'period' => 'daily',
            'type' => MembershipPlan::TYPE_BASIC,
            'weekly_accesses' => 1,
            'daily_checkins' => 1,
            'all_units' => '1',
        ])->assertOk();

        $plan = MembershipPlan::where('name', 'Diária da Rede')->firstOrFail();

        $this->assertEquals(25, (float) $plan->price);
    }

    /** @test */
    public function test_sale_with_daily_plan_only_accepts_products_or_services_as_extra_items(): void
    {
        $this->actingAs($this->networkAdmin);

        $daily = MembershipPlan::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Diária',
            'price' => 30,
            'period' => 'daily',
            'duration_days' => 1,
            'type' => MembershipPlan::TYPE_BASIC,
            'weekly_accesses' => 1,
            'daily_checkins' => 1,
            'all_units' => true,
        ]);

        $basic = MembershipPlan::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Mensal',
            'price' => 100,
            'period' => 'monthly',
            'duration_days' => 30,
            'type' => MembershipPlan::TYPE_BASIC,
            'weekly_accesses' => 5,
            'daily_checkins' => 1,
            'all_units' => true,
        ]);

        $student = $this->makeStudent($this->unit);

        $this->postJson(route('students.sales.store', $student), [
            'items' => [
                ['type' => 'plan', 'plan_id' => $daily->id, 'starts_at' => now()->toDateString()],
                ['type' => 'plan', 'plan_id' => $basic->id, 'starts_at' => now()->toDateString(), 'ends_at' => now()->addDays(30)->toDateString()],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('items');

        $this->postJson(route('students.sales.store', $student), [
            'items' => [
                ['type' => 'plan', 'plan_id' => $daily->id, 'starts_at' => now()->toDateString()],
                ['type' => 'other', 'description' => 'Camiseta', 'amount' => 50],
            ],
        ])->assertOk();

        $this->assertSame(2, StudentSaleItem::count());
    }

    /** @test */
    public function test_class_grade_requires_a_class_schedule(): void
    {
        $this->actingAs($this->networkAdmin);

        $modality = Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Yoga',
        ]);

        $class = SchoolClass::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'modality_id' => $modality->id,
            'name' => 'Yoga',
            'drop_in_price' => 40,
            'status' => 'active',
        ]);

        $student = $this->makeStudent($this->unit);

        $this->postJson(route('students.sales.store', $student), [
            'items' => [[
                'type' => 'class',
                'class_id' => $class->id,
                'amount' => 40,
            ]],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.class_schedule_id');
    }

    /** @test */
    public function test_network_admin_can_open_and_update_region_settings(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->get(route('admin.regions.settings', $this->region))
            ->assertOk()
            ->assertSee('Configurações da região')
            ->assertSee('Valor padrão da diária');

        $this->put(route('admin.regions.settings.update', $this->region), [
            'day_pass_price' => 32.50,
        ])->assertRedirect();

        $this->region->refresh();

        $this->assertEquals(32.50, (float) $this->region->settings[DayPassPrice::KEY]);
    }

    private function makeStudent(Unit $unit): Student
    {
        $student = Student::create([
            'network_id' => $this->network->id,
            'name' => 'Aluno Config',
            'email' => 'config@exemplo.com',
            'status' => 'active',
            'source' => 'panel',
        ]);

        StudentUnit::create([
            'student_id' => $student->id,
            'network_id' => $this->network->id,
            'unit_id' => $unit->id,
            'status' => 'active',
            'is_primary' => true,
            'starts_at' => now()->toDateString(),
        ]);

        return $student;
    }
}
