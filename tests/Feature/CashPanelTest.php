<?php

namespace Tests\Feature;

use App\Models\Network;
use App\Models\Region;
use App\Models\SaasPlan;
use App\Models\Student;
use App\Models\StudentCharge;
use App\Models\StudentNotification;
use App\Models\StudentSale;
use App\Models\StudentSalePayment;
use App\Models\StudentUnit;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $platformAdmin;

    private User $networkAdmin;

    private User $unitAdmin;

    private Network $network;

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
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();
        $this->otherUnit = Unit::where('identifier', 'UNID002')->firstOrFail();
    }

    /** @test */
    public function test_network_admin_can_open_the_cash_panel(): void
    {
        $student = $this->makeStudent($this->unit, ['name' => 'Aluno do Caixa']);
        $this->makeCharge($this->unit, $student, ['amount' => 149.90]);

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.index'))
            ->assertOk()
            ->assertSee('Caixa')
            ->assertSee('Movimentação de vendas')
            ->assertSee('Registrar nova venda')
            ->assertSee('Aluno do Caixa')
            ->assertSee('Recebido')
            ->assertSee('Pendente')
            ->assertSee('Atrasado')
            ->assertSee('R$ 149,90');
    }

    /** @test */
    public function test_management_menu_is_available_on_the_sidebar(): void
    {
        $this->actingAs($this->networkAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Gerenciamento')
            ->assertSee('Caixa')
            ->assertSee(route('management.cash.index'), false);
    }

    /** @test */
    public function test_unit_admin_only_sees_charges_from_their_unit(): void
    {
        $ownStudent = $this->makeStudent($this->unit, ['name' => 'Aluno da Unidade']);
        $otherStudent = $this->makeStudent($this->otherUnit, ['name' => 'Aluno de Outra Unidade']);

        $this->makeCharge($this->unit, $ownStudent, ['amount' => 100]);
        $this->makeCharge($this->otherUnit, $otherStudent, ['amount' => 900]);

        $this->actingAs($this->unitAdmin)
            ->get(route('management.cash.index'))
            ->assertOk()
            ->assertSee('Aluno da Unidade')
            ->assertDontSee('Aluno de Outra Unidade')
            ->assertViewHas('scope', fn (array $scope) => $scope['unit_id'] === $this->unit->id);
    }

    /** @test */
    public function test_date_shortcuts_filter_the_movements(): void
    {
        $student = $this->makeStudent($this->unit);

        $todayCharge = $this->makeCharge($this->unit, $student, ['amount' => 111, 'paid_at' => now()]);
        $yesterdayCharge = $this->makeCharge($this->unit, $student, ['amount' => 222, 'paid_at' => now()->subDay()]);
        $tomorrowCharge = $this->makeCharge($this->unit, $student, [
            'amount' => 333,
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.index'))
            ->assertOk()
            ->assertViewHas('charges', fn ($charges) => $charges->pluck('id')->contains($todayCharge->id))
            ->assertViewHas('charges', fn ($charges) => ! $charges->pluck('id')->contains($yesterdayCharge->id));

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.index', ['period' => 'yesterday']))
            ->assertOk()
            ->assertViewHas('charges', fn ($charges) => $charges->pluck('id')->contains($yesterdayCharge->id))
            ->assertViewHas('charges', fn ($charges) => ! $charges->pluck('id')->contains($todayCharge->id));

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.index', ['period' => 'tomorrow']))
            ->assertOk()
            ->assertViewHas('charges', fn ($charges) => $charges->pluck('id')->contains($tomorrowCharge->id))
            ->assertViewHas('charges', fn ($charges) => ! $charges->pluck('id')->contains($todayCharge->id));
    }

    /** @test */
    public function test_pending_status_is_limited_to_charges_due_today(): void
    {
        $student = $this->makeStudent($this->unit);

        $dueToday = $this->makeCharge($this->unit, $student, [
            'amount' => 70,
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->toDateString(),
        ]);

        $dueTomorrow = $this->makeCharge($this->unit, $student, [
            'amount' => 90,
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.index', ['status' => 'pending']))
            ->assertOk()
            ->assertViewHas('charges', fn ($charges) => $charges->pluck('id')->all() === [$dueToday->id])
            ->assertViewHas('totals', fn (array $totals) => $totals['pending'] === 70.0);

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.index', ['period' => 'tomorrow']))
            ->assertOk()
            ->assertViewHas('charges', fn ($charges) => $charges->pluck('id')->contains($dueTomorrow->id))
            ->assertViewHas('charges', fn ($charges) => $charges->pluck('id')->contains($dueToday->id));
    }

    /** @test */
    public function test_confirm_payment_applies_late_interest(): void
    {
        $student = $this->makeStudent($this->unit);

        $charge = $this->makeCharge($this->unit, $student, [
            'amount' => 100,
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->actingAs($this->networkAdmin)
            ->post(route('management.cash.confirm', $charge), [
                'method' => 'pix',
                'interest_percent' => 10,
            ])
            ->assertRedirect();

        $charge->refresh();

        $this->assertSame(StudentCharge::STATUS_PAID, $charge->status);
        $this->assertEquals(110.0, (float) $charge->amount);
        $this->assertEquals(100.0, (float) $charge->metadata['original_amount']);
        $this->assertEquals(10.0, (float) $charge->metadata['interest_amount']);
        $this->assertEquals(10.0, (float) $charge->metadata['interest_percent']);
    }

    /** @test */
    public function test_overdue_interest_can_be_informed_as_a_fixed_amount(): void
    {
        $student = $this->makeStudent($this->unit);

        $charge = $this->makeCharge($this->unit, $student, [
            'amount' => 80,
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        $this->actingAs($this->networkAdmin)
            ->post(route('management.cash.confirm', $charge), [
                'method' => 'cash',
                'interest_amount' => 15.50,
            ])
            ->assertRedirect();

        $charge->refresh();

        $this->assertEquals(95.5, (float) $charge->amount);
        $this->assertEquals(15.5, (float) $charge->metadata['interest_amount']);
    }

    /** @test */
    public function test_status_filter_separates_paid_pending_and_overdue(): void
    {
        $student = $this->makeStudent($this->unit);

        $paid = $this->makeCharge($this->unit, $student, ['amount' => 100]);
        $pending = $this->makeCharge($this->unit, $student, [
            'amount' => 50,
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->toDateString(),
        ]);
        $overdue = $this->makeCharge($this->unit, $student, [
            'amount' => 30,
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->subDays(3)->toDateString(),
        ]);

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.index', ['status' => 'paid']))
            ->assertOk()
            ->assertViewHas('charges', fn ($charges) => $charges->pluck('id')->all() === [$paid->id]);

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.index', ['status' => 'pending']))
            ->assertOk()
            ->assertViewHas('charges', fn ($charges) => $charges->pluck('id')->all() === [$pending->id]);

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.index', ['status' => 'overdue']))
            ->assertOk()
            ->assertViewHas('charges', fn ($charges) => $charges->pluck('id')->all() === [$overdue->id]);
    }

    /** @test */
    public function test_overdue_reference_is_capped_at_today_for_future_dates(): void
    {
        $student = $this->makeStudent($this->unit);

        $overdue = $this->makeCharge($this->unit, $student, [
            'amount' => 40,
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->subDays(2)->toDateString(),
        ]);

        $future = $this->makeCharge($this->unit, $student, [
            'amount' => 60,
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.index', ['period' => 'tomorrow', 'status' => 'overdue']))
            ->assertOk()
            ->assertViewHas('charges', fn ($charges) => $charges->pluck('id')->contains($overdue->id))
            ->assertViewHas('charges', fn ($charges) => ! $charges->pluck('id')->contains($future->id));

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.index', ['period' => 'tomorrow']))
            ->assertOk()
            ->assertViewHas('charges', fn ($charges) => $charges->pluck('id')->contains($overdue->id))
            ->assertViewHas('charges', fn ($charges) => $charges->pluck('id')->contains($future->id));
    }

    /** @test */
    public function test_platform_admin_can_filter_by_network(): void
    {
        $enterprise = SaasPlan::where('slug', 'enterprise')->firstOrFail();

        $otherNetwork = Network::create([
            'name' => 'Rede do Caixa',
            'slug' => 'rede-do-caixa',
            'document' => '22.333.444/0001-55',
            'status' => 'active',
            'plan_id' => $enterprise->id,
        ]);

        $otherRegion = Region::create([
            'network_id' => $otherNetwork->id,
            'name' => 'Região do Caixa',
            'status' => 'active',
        ]);

        $otherUnit = Unit::create([
            'network_id' => $otherNetwork->id,
            'region_id' => $otherRegion->id,
            'name' => 'Unidade do Caixa',
            'identifier' => 'CAI001',
            'status' => 'active',
        ]);

        $ownStudent = $this->makeStudent($this->unit, ['name' => 'Aluno Rede Principal']);
        $otherStudent = $this->makeStudent($otherUnit, ['name' => 'Aluno Rede Secundária']);

        $this->makeCharge($this->unit, $ownStudent, ['amount' => 100]);
        $this->makeCharge($otherUnit, $otherStudent, ['amount' => 700]);

        $this->actingAs($this->platformAdmin)
            ->get(route('management.cash.index'))
            ->assertOk()
            ->assertViewHas('scope', fn (array $scope) => $scope['network_filter'] === true)
            ->assertSee('Aluno Rede Principal')
            ->assertSee('Aluno Rede Secundária');

        $this->actingAs($this->platformAdmin)
            ->get(route('management.cash.index', ['network_id' => $otherNetwork->id]))
            ->assertOk()
            ->assertViewHas('scope', fn (array $scope) => $scope['network_id'] === $otherNetwork->id)
            ->assertSee('Aluno Rede Secundária')
            ->assertDontSee('Aluno Rede Principal');
    }

    /** @test */
    public function test_confirm_payment_marks_the_charge_as_paid(): void
    {
        $student = $this->makeStudent($this->unit);
        $charge = $this->makeCharge($this->unit, $student, [
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->networkAdmin)
            ->post(route('management.cash.confirm', $charge), ['method' => 'pix'])
            ->assertRedirect();

        $charge->refresh();

        $this->assertSame(StudentCharge::STATUS_PAID, $charge->status);
        $this->assertSame('pix', $charge->payment_method);
        $this->assertNotNull($charge->paid_at);
    }

    /** @test */
    public function test_notify_creates_a_student_notification(): void
    {
        $student = $this->makeStudent($this->unit);
        $charge = $this->makeCharge($this->unit, $student, [
            'amount' => 75,
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->subDays(4)->toDateString(),
        ]);

        $this->actingAs($this->networkAdmin)
            ->post(route('management.cash.notify', $charge))
            ->assertRedirect();

        $this->assertDatabaseHas('student_notifications', [
            'student_id' => $student->id,
            'type' => 'payment_reminder',
        ]);
        $this->assertSame(1, StudentNotification::where('student_id', $student->id)->count());
    }

    /** @test */
    public function test_receipt_is_available_for_the_charge_unit_and_blocked_for_others(): void
    {
        $ownStudent = $this->makeStudent($this->unit, ['name' => 'Aluno Recibo']);
        $ownCharge = $this->makeCharge($this->unit, $ownStudent, ['amount' => 123.45]);

        $otherStudent = $this->makeStudent($this->otherUnit, ['name' => 'Aluno de Outra Unidade']);
        $otherCharge = $this->makeCharge($this->otherUnit, $otherStudent);

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.receipt', $ownCharge))
            ->assertOk()
            ->assertSee('Recibo de pagamento')
            ->assertSee('Aluno Recibo')
            ->assertSee('R$ 123,45', false);

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.receipt', ['charge' => $ownCharge, 'doc' => 'comprovante']))
            ->assertOk()
            ->assertSee('Comprovante de pagamento');

        $this->actingAs($this->unitAdmin)
            ->get(route('management.cash.receipt', $otherCharge))
            ->assertNotFound();
    }

    /** @test */
    public function test_installment_plan_is_available_for_paid_sales(): void
    {
        $student = $this->makeStudent($this->unit);

        $sale = StudentSale::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $student->id,
            'code' => 'VND-000999',
            'status' => StudentSale::STATUS_PAID,
            'launched_at' => now()->toDateString(),
            'total' => 300,
        ]);

        $sale->payments()->create([
            'method' => StudentSalePayment::METHOD_CREDIT,
            'amount' => 300,
            'installments' => 3,
            'status' => 'confirmed',
        ]);

        $charge = $this->makeCharge($this->unit, $student, [
            'amount' => 300,
            'student_sale_id' => $sale->id,
        ]);

        $this->actingAs($this->networkAdmin)
            ->get(route('management.cash.index'))
            ->assertOk()
            ->assertViewHas('installments', function (array $installments) use ($charge) {
                return isset($installments[$charge->id])
                    && $installments[$charge->id]['count'] === 3
                    && round($installments[$charge->id]['total'], 2) === 300.0;
            });
    }

    /** @test */
    public function test_payment_link_returns_a_clear_error_when_gateway_is_not_configured(): void
    {
        $student = $this->makeStudent($this->unit);
        $charge = $this->makeCharge($this->unit, $student, [
            'status' => StudentCharge::STATUS_PENDING,
            'paid_at' => null,
            'due_date' => now()->toDateString(),
            'description' => 'Plano Essencial',
        ]);

        $this->actingAs($this->networkAdmin)
            ->postJson(route('management.cash.link', $charge))
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    private function makeStudent(Unit $unit, array $attributes = []): Student
    {
        $this->sequence++;

        $student = Student::create(array_merge([
            'network_id' => $unit->network_id,
            'name' => 'Aluno Caixa '.$this->sequence,
            'email' => 'caixa'.$this->sequence.'@exemplo.com',
            'cpf' => str_pad((string) (70000000000 + $this->sequence), 11, '0', STR_PAD_LEFT),
            'cpf_raw' => str_pad((string) (70000000000 + $this->sequence), 11, '0', STR_PAD_LEFT),
            'status' => 'active',
        ], $attributes));

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
