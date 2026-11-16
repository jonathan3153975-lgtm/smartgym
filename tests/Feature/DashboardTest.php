<?php

namespace Tests\Feature;

use App\Models\Network;
use App\Models\Region;
use App\Models\Role;
use App\Models\SaasPlan;
use App\Models\Student;
use App\Models\StudentCharge;
use App\Models\StudentCheckin;
use App\Models\StudentSubscription;
use App\Models\StudentUnit;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserVinculo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;

    private User $networkAdmin;

    private User $unitAdmin;

    private User $regionAdmin;

    private Network $network;

    private Region $region;

    private Unit $unit;

    private Unit $otherUnit;

    private int $sequence = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->platformAdmin = User::where('email', 'jonathan3153975@gmail.com')->firstOrFail();
        $this->networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->unitAdmin = User::where('email', 'gerente@smartgym.com.br')->firstOrFail();

        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $this->region = Region::where('network_id', $this->network->id)->firstOrFail();
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();
        $this->otherUnit = Unit::where('identifier', 'UNID002')->firstOrFail();

        $regionRole = Role::where('name', 'region_admin')->where('level', 'region')->firstOrFail();

        $this->regionAdmin = User::create([
            'name' => 'Admin da Região',
            'email' => 'regiao@smartgym.com.br',
            'password' => Hash::make('Regiao123!'),
        ]);

        $this->regionAdmin->forceFill(['email_verified_at' => now()])->save();

        UserVinculo::create([
            'user_id' => $this->regionAdmin->id,
            'network_id' => $this->network->id,
            'region_id' => $this->region->id,
            'role_id' => $regionRole->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function test_dashboard_renders_the_new_financial_and_student_panels(): void
    {
        $student = $this->makeStudent($this->unit);
        $this->makeCharge($this->unit, $student, ['amount' => 120]);

        $this->actingAs($this->platformAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Visão geral da operação')
            ->assertSee('Total esperado')
            ->assertSee('Recebido por produto')
            ->assertSee('A receber por produto')
            ->assertSee('Formas de pagamento')
            ->assertSee('Composição dos alunos')
            ->assertSee('Painel de pendências')
            ->assertSee('Alunos sem consumo')
            ->assertSee('Lançamentos');
    }

    /** @test */
    public function test_default_period_is_the_last_thirty_days(): void
    {
        $this->actingAs($this->networkAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('period.days', 30)
            ->assertViewHas('filters.from', now()->subDays(29)->toDateString())
            ->assertViewHas('filters.to', now()->toDateString());
    }

    /** @test */
    public function test_unit_admin_only_sees_their_own_unit(): void
    {
        $ownStudent = $this->makeStudent($this->unit);
        $otherStudent = $this->makeStudent($this->otherUnit);

        $this->makeCharge($this->unit, $ownStudent, ['amount' => 100]);
        $this->makeCharge($this->otherUnit, $otherStudent, ['amount' => 500]);

        $this->actingAs($this->unitAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('options.unit_filter', false)
            ->assertViewHas('options.region_filter', false)
            ->assertViewHas('filters.unit_id', $this->unit->id)
            ->assertViewHas('revenue.received', 100.0);

        $this->actingAs($this->unitAdmin)
            ->get(route('dashboard', ['unit_id' => $this->otherUnit->id]))
            ->assertOk()
            ->assertViewHas('filters.unit_id', $this->unit->id)
            ->assertViewHas('revenue.received', 100.0);
    }

    /** @test */
    public function test_network_admin_can_filter_by_unit(): void
    {
        $ownStudent = $this->makeStudent($this->unit);
        $otherStudent = $this->makeStudent($this->otherUnit);

        $this->makeCharge($this->unit, $ownStudent, ['amount' => 100]);
        $this->makeCharge($this->otherUnit, $otherStudent, ['amount' => 500]);

        $this->actingAs($this->networkAdmin)
            ->get(route('dashboard', ['unit_id' => $this->otherUnit->id]))
            ->assertOk()
            ->assertViewHas('options.unit_filter', true)
            ->assertViewHas('filters.unit_id', $this->otherUnit->id)
            ->assertViewHas('revenue.received', 500.0);
    }

    /** @test */
    public function test_region_admin_can_filter_between_units_of_the_region(): void
    {
        $otherRegion = Region::create([
            'network_id' => $this->network->id,
            'name' => 'Região Norte',
            'status' => 'active',
        ]);

        $outsideUnit = Unit::create([
            'network_id' => $this->network->id,
            'region_id' => $otherRegion->id,
            'name' => 'Unidade Fora da Região',
            'identifier' => 'UNID003',
            'status' => 'active',
        ]);

        $insideStudent = $this->makeStudent($this->unit);
        $outsideStudent = $this->makeStudent($outsideUnit);

        $this->makeCharge($this->unit, $insideStudent, ['amount' => 80]);
        $this->makeCharge($outsideUnit, $outsideStudent, ['amount' => 999]);

        $this->actingAs($this->regionAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('options.region_filter', false)
            ->assertViewHas('options.unit_filter', true)
            ->assertViewHas('filters.region_id', $this->region->id)
            ->assertViewHas('revenue.received', 80.0);

        $this->actingAs($this->regionAdmin)
            ->get(route('dashboard', ['unit_id' => $outsideUnit->id]))
            ->assertOk()
            ->assertViewHas('filters.unit_id', null)
            ->assertViewHas('revenue.received', 80.0);
    }

    /** @test */
    public function test_platform_admin_can_filter_by_network(): void
    {
        $enterprise = SaasPlan::where('slug', 'enterprise')->firstOrFail();

        $otherNetwork = Network::create([
            'name' => 'Rede Secundária',
            'slug' => 'rede-secundaria',
            'document' => '11.222.333/0001-44',
            'status' => 'active',
            'plan_id' => $enterprise->id,
        ]);

        $otherRegion = Region::create([
            'network_id' => $otherNetwork->id,
            'name' => 'Região Secundária',
            'status' => 'active',
        ]);

        $otherNetworkUnit = Unit::create([
            'network_id' => $otherNetwork->id,
            'region_id' => $otherRegion->id,
            'name' => 'Unidade Secundária',
            'identifier' => 'SEC001',
            'status' => 'active',
        ]);

        $ownStudent = $this->makeStudent($this->unit);
        $otherStudent = $this->makeStudent($otherNetworkUnit);

        $this->makeCharge($this->unit, $ownStudent, ['amount' => 100]);
        $this->makeCharge($otherNetworkUnit, $otherStudent, ['amount' => 700]);

        $this->actingAs($this->platformAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('options.network_filter', true)
            ->assertViewHas('revenue.received', 800.0);

        $this->actingAs($this->platformAdmin)
            ->get(route('dashboard', ['network_id' => $otherNetwork->id]))
            ->assertOk()
            ->assertViewHas('filters.network_id', $otherNetwork->id)
            ->assertViewHas('revenue.received', 700.0);
    }

    /** @test */
    public function test_revenue_is_split_between_received_future_and_overdue(): void
    {
        $student = $this->makeStudent($this->unit);

        $this->makeCharge($this->unit, $student, [
            'type' => StudentCharge::TYPE_PLAN,
            'amount' => 100,
            'status' => StudentCharge::STATUS_PAID,
            'paid_at' => now(),
            'metadata' => ['plan_type' => 'basic'],
        ]);

        $this->makeCharge($this->unit, $student, [
            'type' => StudentCharge::TYPE_PLAN,
            'amount' => 50,
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->addDays(5)->toDateString(),
            'metadata' => ['plan_type' => 'basic'],
        ]);

        $this->makeCharge($this->unit, $student, [
            'type' => StudentCharge::TYPE_PLAN,
            'amount' => 30,
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->subDays(3)->toDateString(),
            'metadata' => ['plan_type' => 'basic'],
        ]);

        // Outside the period and must not be counted.
        $this->makeCharge($this->unit, $student, [
            'amount' => 999,
            'status' => StudentCharge::STATUS_PAID,
            'paid_at' => now()->subDays(45),
        ]);

        $this->actingAs($this->networkAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('revenue.received', 100.0)
            ->assertViewHas('revenue.pending_future', 50.0)
            ->assertViewHas('revenue.overdue', 30.0)
            ->assertViewHas('revenue.total_expected', 180.0);
    }

    /** @test */
    public function test_product_and_payment_method_filters_are_applied(): void
    {
        $student = $this->makeStudent($this->unit);

        $this->makeCharge($this->unit, $student, [
            'type' => StudentCharge::TYPE_PLAN,
            'amount' => 100,
            'payment_method' => 'pix',
            'metadata' => ['plan_type' => 'basic'],
        ]);

        $this->makeCharge($this->unit, $student, [
            'type' => StudentCharge::TYPE_PLAN,
            'amount' => 200,
            'payment_method' => 'cash',
            'metadata' => ['plan_type' => 'classes'],
        ]);

        $this->makeCharge($this->unit, $student, [
            'type' => StudentCharge::TYPE_DAY_PASS,
            'amount' => 50,
            'payment_method' => 'pix',
        ]);

        $this->actingAs($this->networkAdmin)
            ->get(route('dashboard', ['product' => 'plan_basic']))
            ->assertOk()
            ->assertViewHas('filters.product', 'plan_basic')
            ->assertViewHas('revenue.received', 100.0);

        $this->actingAs($this->networkAdmin)
            ->get(route('dashboard', ['method' => 'pix']))
            ->assertOk()
            ->assertViewHas('filters.method', 'pix')
            ->assertViewHas('revenue.received', 150.0);

        $this->actingAs($this->networkAdmin)
            ->get(route('dashboard', ['product' => 'day_pass', 'method' => 'cash']))
            ->assertOk()
            ->assertViewHas('revenue.received', 0.0);
    }

    /** @test */
    public function test_student_metrics_cover_recurring_new_trial_conversion_and_without_plan(): void
    {
        $recurring = $this->makeStudent($this->unit, ['created_at' => now()->subDays(90)]);
        $newcomer = $this->makeStudent($this->unit, ['created_at' => now()->subDays(5)]);
        $trial = $this->makeStudent($this->unit, ['created_at' => now()->subDays(60)]);
        $withoutPlan = $this->makeStudent($this->unit, ['created_at' => now()->subDays(120)]);

        StudentSubscription::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $recurring->id,
            'status' => StudentSubscription::STATUS_ACTIVE,
            'plan_name' => 'Essencial',
            'price' => 100,
            'period' => 'monthly',
            'duration_days' => 30,
            'plan_type' => 'basic',
            'starts_at' => now()->subDays(20),
            'ends_at' => now()->addDays(10),
            'paid_at' => now()->subDays(20),
        ]);

        StudentCheckin::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $trial->id,
            'type' => StudentCheckin::TYPE_TRIAL,
            'checked_in_at' => now()->subDays(10),
        ]);

        StudentSubscription::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $trial->id,
            'status' => StudentSubscription::STATUS_ACTIVE,
            'plan_name' => 'Essencial',
            'price' => 100,
            'period' => 'monthly',
            'duration_days' => 30,
            'plan_type' => 'basic',
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(28),
            'paid_at' => now()->subDays(2),
        ]);

        $this->actingAs($this->networkAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertViewHas('students.total_active', 4)
            ->assertViewHas('students.recurring', 2)
            ->assertViewHas('students.new', 1)
            ->assertViewHas('students.trial', 1)
            ->assertViewHas('students.trial_converted', 1)
            ->assertViewHas('students.without_plan', fn ($value) => $value >= 1)
            ->assertViewHas('students.composition', function (array $composition) {
                $total = array_sum(array_column($composition, 'value'));

                return $total === 4;
            });
    }

    /** @test */
    public function test_custom_date_range_filters_the_revenue(): void
    {
        $student = $this->makeStudent($this->unit);

        $this->makeCharge($this->unit, $student, [
            'amount' => 300,
            'paid_at' => now()->subMonths(3),
        ]);

        $this->makeCharge($this->unit, $student, [
            'amount' => 120,
            'paid_at' => now(),
        ]);

        $from = now()->subMonths(3)->startOfDay()->toDateString();
        $to = now()->subMonths(3)->endOfDay()->toDateString();

        $this->actingAs($this->networkAdmin)
            ->get(route('dashboard', ['from' => $from, 'to' => $to]))
            ->assertOk()
            ->assertViewHas('revenue.received', 300.0)
            ->assertViewHas('filters.preset', 'custom');
    }

    private function makeStudent(Unit $unit, array $attributes = []): Student
    {
        $this->sequence++;

        $createdAt = $attributes['created_at'] ?? null;
        unset($attributes['created_at']);

        $student = Student::create(array_merge([
            'network_id' => $unit->network_id,
            'name' => 'Aluno Dashboard '.$this->sequence,
            'email' => 'dashboard'.$this->sequence.'@exemplo.com',
            'cpf' => str_pad((string) (80000000000 + $this->sequence), 11, '0', STR_PAD_LEFT),
            'cpf_raw' => str_pad((string) (80000000000 + $this->sequence), 11, '0', STR_PAD_LEFT),
            'status' => 'active',
        ], $attributes));

        if ($createdAt) {
            $student->forceFill(['created_at' => $createdAt])->saveQuietly();
        }

        StudentUnit::create([
            'student_id' => $student->id,
            'network_id' => $unit->network_id,
            'unit_id' => $unit->id,
            'status' => 'active',
            'is_primary' => true,
            'starts_at' => now()->subDays(30)->toDateString(),
        ]);

        return $student;
    }

    private function makeCharge(Unit $unit, Student $student, array $attributes = []): StudentCharge
    {
        return StudentCharge::create(array_merge([
            'network_id' => $unit->network_id,
            'unit_id' => $unit->id,
            'student_id' => $student->id,
            'type' => StudentCharge::TYPE_PLAN,
            'description' => 'Plano de teste',
            'amount' => 100,
            'status' => StudentCharge::STATUS_PAID,
            'payment_method' => 'pix',
            'due_date' => now()->toDateString(),
            'paid_at' => now(),
            'metadata' => ['plan_type' => 'basic'],
        ], $attributes));
    }
}
