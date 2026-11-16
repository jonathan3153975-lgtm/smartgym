<?php

namespace Tests\Feature;

use App\Models\ClassBooking;
use App\Models\ClassSchedule;
use App\Models\MembershipPlan;
use App\Models\Modality;
use App\Models\Network;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentCharge;
use App\Models\StudentSale;
use App\Models\StudentSaleItem;
use App\Models\StudentSalePayment;
use App\Models\StudentSubscription;
use App\Models\StudentUnit;
use App\Models\Unit;
use App\Models\User;
use App\Support\Payments\StudentChargeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class StudentSaleTest extends TestCase
{
    use RefreshDatabase;

    private User $networkAdmin;

    private User $unitAdmin;

    private Network $network;

    private Unit $unit;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->unitAdmin = User::where('email', 'gerente@smartgym.com.br')->firstOrFail();
        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();

        $this->student = $this->makeStudent($this->unit, [
            'name' => 'Aluno Venda',
            'email' => 'venda@exemplo.com',
        ]);
    }

    /** @test */
    public function test_staff_can_open_the_sale_builder_page(): void
    {
        $this->actingAs($this->networkAdmin);

        $plan = $this->makeBasicPlan();
        $modality = $this->makeModality('Yoga');
        $classPlan = $this->makeClassesPlan($modality, 3);
        $class = $this->makeClass($this->unit, ['name' => 'Yoga Manhã', 'modality_id' => $modality->id]);

        $this->get(route('students.sales.create', $this->student))
            ->assertOk()
            ->assertSee('Nova venda presencial')
            ->assertSee('Tipo de venda')
            ->assertSee($plan->name)
            ->assertSee($classPlan->name)
            ->assertSee($class->name);
    }

    /** @test */
    public function test_sale_builder_rejects_a_student_from_another_unit(): void
    {
        $otherUnit = Unit::where('identifier', 'UNID002')->firstOrFail();
        $otherStudent = $this->makeStudent($otherUnit, ['name' => 'Outro Aluno']);

        $this->actingAs($this->unitAdmin)
            ->get(route('students.sales.create', $otherStudent))
            ->assertNotFound();
    }

    /** @test */
    public function test_multi_plan_sale_creates_items_with_individual_dates_limits_and_slots(): void
    {
        $this->actingAs($this->networkAdmin);

        $basicPlan = $this->makeBasicPlan();
        $modality = $this->makeModality('Yoga');
        $classesPlan = $this->makeClassesPlan($modality, 4);

        $firstClass = $this->makeClass($this->unit, [
            'name' => 'Yoga Segunda',
            'modality_id' => $modality->id,
            'weekday' => 1,
            'start_time' => '07:00',
            'end_time' => '08:00',
        ]);

        $secondClass = $this->makeClass($this->unit, [
            'name' => 'Yoga Quarta',
            'modality_id' => $modality->id,
            'weekday' => 3,
            'start_time' => '19:00',
            'end_time' => '20:00',
        ]);

        $start = now()->toDateString();
        $end = now()->addDays(30)->toDateString();
        $classesStart = now()->addDays(5)->toDateString();
        $classesEnd = now()->addDays(35)->toDateString();

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [
                [
                    'type' => 'plan',
                    'plan_id' => $basicPlan->id,
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'weekly_accesses' => 6,
                    'daily_checkins' => 2,
                ],
                [
                    'type' => 'plan',
                    'plan_id' => $classesPlan->id,
                    'starts_at' => $classesStart,
                    'ends_at' => $classesEnd,
                    'weekly_accesses' => 3,
                    'daily_checkins' => 2,
                    'checkin_advance_minutes' => 30,
                    'slot_ids' => [$firstClass->schedules()->firstOrFail()->id, $secondClass->schedules()->firstOrFail()->id],
                ],
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $sale = StudentSale::where('student_id', $this->student->id)->firstOrFail();

        $this->assertSame(2, $sale->items()->count());
        $this->assertSame(2, $sale->charges()->count());
        $this->assertSame(2, StudentSubscription::where('student_id', $this->student->id)->count());
        $this->assertNotNull($sale->code);

        $basicItem = $sale->items()->where('membership_plan_id', $basicPlan->id)->firstOrFail();

        $this->assertSame($start, $basicItem->starts_at->toDateString());
        $this->assertSame($end, $basicItem->ends_at->toDateString());
        $this->assertSame(6, $basicItem->weekly_accesses);
        $this->assertSame(2, $basicItem->daily_checkins);

        $classesItem = $sale->items()->where('membership_plan_id', $classesPlan->id)->firstOrFail();

        $this->assertSame($classesStart, $classesItem->starts_at->toDateString());
        $this->assertSame($classesEnd, $classesItem->ends_at->toDateString());
        $this->assertSame(3, $classesItem->weekly_accesses);
        $this->assertSame(30, $classesItem->checkin_advance_minutes);
        $this->assertSame(2, $classesItem->slots()->count());

        $classesSubscription = StudentSubscription::where('student_sale_item_id', $classesItem->id)->firstOrFail();

        $this->assertSame($classesStart, $classesSubscription->starts_at->toDateString());
        $this->assertSame($classesEnd, $classesSubscription->ends_at->toDateString());
        $this->assertSame(3, $classesSubscription->weekly_accesses);
        $this->assertSame(2, $classesSubscription->daily_checkins);
        $this->assertSame(30, $classesSubscription->checkin_advance_minutes);

        $this->assertDatabaseHas('student_charges', [
            'student_id' => $this->student->id,
            'student_sale_id' => $sale->id,
            'student_sale_item_id' => $classesItem->id,
            'type' => StudentCharge::TYPE_PLAN,
        ]);
    }

    /** @test */
    public function test_sale_rejects_more_slots_than_the_plan_weekly_limit(): void
    {
        $this->actingAs($this->networkAdmin);

        $modality = $this->makeModality('Pilates');
        $plan = $this->makeClassesPlan($modality, 1);

        $first = $this->makeClass($this->unit, ['name' => 'Pilates A', 'modality_id' => $modality->id, 'weekday' => 1]);
        $second = $this->makeClass($this->unit, ['name' => 'Pilates B', 'modality_id' => $modality->id, 'weekday' => 3]);

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'plan',
                'plan_id' => $plan->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(30)->toDateString(),
                'weekly_accesses' => 1,
                'daily_checkins' => 1,
                'slot_ids' => [$first->schedules()->firstOrFail()->id, $second->schedules()->firstOrFail()->id],
            ]],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.slot_ids');

        $this->assertSame(0, StudentSale::count());
    }

    /** @test */
    public function test_sale_rejects_a_slot_from_another_modality(): void
    {
        $this->actingAs($this->networkAdmin);

        $yoga = $this->makeModality('Yoga');
        $boxe = $this->makeModality('Boxe');
        $plan = $this->makeClassesPlan($yoga, 2);

        $outside = $this->makeClass($this->unit, [
            'name' => 'Boxe',
            'modality_id' => $boxe->id,
            'weekday' => 2,
        ]);

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'plan',
                'plan_id' => $plan->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(30)->toDateString(),
                'weekly_accesses' => 2,
                'daily_checkins' => 1,
                'slot_ids' => [$outside->schedules()->firstOrFail()->id],
            ]],
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.slot_ids');
    }

    /** @test */
    public function test_day_pass_sale_creates_a_subscription_for_the_chosen_date(): void
    {
        $this->actingAs($this->networkAdmin);

        $validDate = now()->addDay()->toDateString();

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'day_pass',
                'starts_at' => $validDate,
                'daily_checkins' => 2,
                'amount' => 40,
            ]],
        ])->assertOk()->assertJsonPath('success', true);

        $item = StudentSaleItem::firstOrFail();
        $subscription = StudentSubscription::where('student_sale_item_id', $item->id)->firstOrFail();

        $this->assertSame($validDate, $item->starts_at->toDateString());
        $this->assertSame($validDate, $item->ends_at->toDateString());
        $this->assertSame($validDate, $subscription->starts_at->toDateString());
        $this->assertSame(2, $subscription->daily_checkins);
    }

    /** @test */
    public function test_single_class_sale_creates_a_booking_with_the_configured_window(): void
    {
        $this->actingAs($this->networkAdmin);

        $class = $this->makeClass($this->unit, [
            'name' => 'Funcional',
            'weekday' => now()->dayOfWeek,
            'start_time' => '18:00',
            'end_time' => '19:00',
            'drop_in_price' => 60,
        ]);

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'class',
                'class_schedule_id' => $class->schedules()->firstOrFail()->id,
                'booked_for' => now()->toDateString(),
                'checkin_advance_minutes' => 15,
                'amount' => 60,
            ]],
        ])->assertOk()->assertJsonPath('success', true);

        $item = StudentSaleItem::firstOrFail();
        $booking = ClassBooking::where('student_id', $this->student->id)->firstOrFail();

        $this->assertSame($class->id, $booking->class_id);
        $this->assertSame(15, $booking->early_minutes);
        $this->assertSame('17:45', $booking->checkin_opens_at->format('H:i'));
        $this->assertSame('18:30', $booking->checkin_closes_at->format('H:i'));
        $this->assertDatabaseHas('student_charges', [
            'student_sale_item_id' => $item->id,
            'reference_type' => 'class_booking',
            'reference_id' => $booking->id,
        ]);
    }

    /** @test */
    public function test_paying_a_sale_charge_preserves_the_contract_dates(): void
    {
        $this->actingAs($this->networkAdmin);

        $plan = $this->makeBasicPlan();
        $start = now()->addDays(3)->toDateString();
        $end = now()->addDays(33)->toDateString();

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'plan',
                'plan_id' => $plan->id,
                'starts_at' => $start,
                'ends_at' => $end,
                'weekly_accesses' => 5,
                'daily_checkins' => 1,
            ]],
        ])->assertOk();

        $charge = StudentCharge::where('student_id', $this->student->id)->firstOrFail();
        $subscription = StudentSubscription::where('student_id', $this->student->id)->firstOrFail();

        $this->post(route('students.contracts.pay', [$this->student, $charge]))->assertRedirect();

        $subscription->refresh();
        $sale = StudentSale::where('student_id', $this->student->id)->firstOrFail();

        $this->assertSame(StudentSubscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame($start, $subscription->starts_at->toDateString());
        $this->assertSame($end, $subscription->ends_at->toDateString());
        $this->assertSame(StudentSale::STATUS_PAID, $sale->fresh()->status);
    }

    /** @test */
    public function test_contracts_page_lists_sale_dates_and_preferred_slots(): void
    {
        $this->actingAs($this->networkAdmin);

        $modality = $this->makeModality('Yoga');
        $plan = $this->makeClassesPlan($modality, 2);
        $class = $this->makeClass($this->unit, [
            'name' => 'Yoga noite',
            'modality_id' => $modality->id,
            'weekday' => 2,
            'start_time' => '20:00',
        ]);

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'plan',
                'plan_id' => $plan->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(30)->toDateString(),
                'weekly_accesses' => 2,
                'daily_checkins' => 1,
                'slot_ids' => [$class->schedules()->firstOrFail()->id],
            ]],
        ])->assertOk();

        $sale = StudentSale::where('student_id', $this->student->id)->firstOrFail();

        $this->get(route('students.contracts', $this->student))
            ->assertOk()
            ->assertSee('Vendas e contratos')
            ->assertSee($sale->code)
            ->assertSee('Lançamento')
            ->assertSee('Início')
            ->assertSee('Fim')
            ->assertSee('Yoga noite')
            ->assertSee('Horários preferenciais');
    }

    /** @test */
    public function test_sale_requires_at_least_one_item(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [],
        ])->assertStatus(422)->assertJsonValidationErrors('items');
    }

    /** @test */
    public function test_multiple_active_plans_allow_classes_from_the_matching_plan(): void
    {
        $this->actingAs($this->networkAdmin);

        $basic = $this->makeBasicPlan();
        $yoga = $this->makeModality('Yoga');
        $classesPlan = $this->makeClassesPlan($yoga, 2);

        $includedClass = $this->makeClass($this->unit, [
            'name' => 'Yoga incluída',
            'modality_id' => $yoga->id,
            'weekday' => now()->dayOfWeek,
        ]);

        $otherModality = $this->makeModality('Boxe');
        $otherClass = $this->makeClass($this->unit, [
            'name' => 'Boxe não incluído',
            'modality_id' => $otherModality->id,
            'weekday' => now()->dayOfWeek,
        ]);

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [
                [
                    'type' => 'plan',
                    'plan_id' => $basic->id,
                    'starts_at' => now()->toDateString(),
                    'ends_at' => now()->addDays(30)->toDateString(),
                    'weekly_accesses' => 5,
                    'daily_checkins' => 1,
                ],
                [
                    'type' => 'plan',
                    'plan_id' => $classesPlan->id,
                    'starts_at' => now()->toDateString(),
                    'ends_at' => now()->addDays(30)->toDateString(),
                    'weekly_accesses' => 2,
                    'daily_checkins' => 1,
                ],
            ],
        ])->assertOk();

        foreach (StudentCharge::where('student_id', $this->student->id)->get() as $charge) {
            $this->post(route('students.contracts.pay', [$this->student, $charge]))->assertRedirect();
        }

        StudentAccount::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'email' => $this->student->email,
            'password' => 'SenhaAluno123',
            'must_change_password' => false,
            'status' => 'active',
        ]);

        $this->post(route('student.portal.login.store', $this->network), [
            'email' => $this->student->email,
            'password' => 'SenhaAluno123',
        ])->assertRedirect();

        $this->post(route('student.portal.classes.reserve', [$this->network, $includedClass->schedules()->firstOrFail()]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('class_bookings', [
            'student_id' => $this->student->id,
            'class_id' => $includedClass->id,
        ]);

        $this->post(route('student.portal.classes.reserve', [$this->network, $otherClass->schedules()->firstOrFail()]))
            ->assertSessionHasErrors('class');

        $this->assertDatabaseMissing('class_bookings', [
            'student_id' => $this->student->id,
            'class_id' => $otherClass->id,
        ]);
    }

    /** @test */
    public function test_sale_is_finalized_with_split_payment_methods(): void
    {
        $this->actingAs($this->networkAdmin);

        $plan = $this->makeBasicPlan();

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'plan',
                'plan_id' => $plan->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(30)->toDateString(),
                'weekly_accesses' => 5,
                'daily_checkins' => 1,
            ]],
        ])->assertOk();

        $sale = StudentSale::where('student_id', $this->student->id)->firstOrFail();

        $this->get(route('students.sales.checkout', [$this->student, $sale]))
            ->assertOk()
            ->assertSee('Finalizar venda')
            ->assertSee($sale->code)
            ->assertSee('Formas de pagamento');

        $this->postJson(route('students.sales.finalize', [$this->student, $sale]), [
            'payments' => [
                ['method' => StudentSalePayment::METHOD_CASH, 'amount' => 50, 'installments' => 1],
                ['method' => StudentSalePayment::METHOD_PIX, 'amount' => 49.90, 'installments' => 1],
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $sale->refresh();

        $this->assertSame(StudentSale::STATUS_PAID, $sale->status);
        $this->assertSame(2, $sale->payments()->count());
        $this->assertSame(0, $sale->charges()->where('status', StudentCharge::STATUS_PENDING)->count());
        $this->assertSame(StudentSubscription::STATUS_ACTIVE, StudentSubscription::firstOrFail()->status);
    }

    /** @test */
    public function test_sale_finalization_rejects_payment_total_mismatch(): void
    {
        $this->actingAs($this->networkAdmin);

        $plan = $this->makeBasicPlan();

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'plan',
                'plan_id' => $plan->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(30)->toDateString(),
                'weekly_accesses' => 5,
                'daily_checkins' => 1,
            ]],
        ])->assertOk();

        $sale = StudentSale::where('student_id', $this->student->id)->firstOrFail();

        $this->postJson(route('students.sales.finalize', [$this->student, $sale]), [
            'payments' => [
                ['method' => StudentSalePayment::METHOD_CASH, 'amount' => 10, 'installments' => 1],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('payments');

        $this->assertSame(StudentSale::STATUS_PENDING, $sale->fresh()->status);
    }

    /** @test */
    public function test_plan_item_stores_the_automatic_renewal_preference(): void
    {
        $this->actingAs($this->networkAdmin);

        $plan = $this->makeBasicPlan();

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'plan',
                'plan_id' => $plan->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(30)->toDateString(),
                'weekly_accesses' => 5,
                'daily_checkins' => 1,
                'auto_renew' => 1,
            ]],
        ])->assertOk();

        $item = StudentSaleItem::latest('id')->firstOrFail();
        $subscription = StudentSubscription::latest('id')->firstOrFail();

        $this->assertTrue($item->auto_renew);
        $this->assertTrue($subscription->auto_renew);
    }

    /** @test */
    public function test_renewal_command_creates_a_charge_and_payment_extends_the_subscription(): void
    {
        $plan = $this->makeBasicPlan();
        $endsAt = now()->addDay();

        $subscription = StudentSubscription::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $this->student->id,
            'membership_plan_id' => $plan->id,
            'status' => StudentSubscription::STATUS_ACTIVE,
            'plan_name' => $plan->name,
            'price' => $plan->price,
            'period' => 'monthly',
            'duration_days' => 30,
            'plan_type' => MembershipPlan::TYPE_BASIC,
            'weekly_accesses' => 5,
            'daily_checkins' => 1,
            'auto_renew' => true,
            'starts_at' => now()->subDays(29),
            'ends_at' => $endsAt,
            'paid_at' => now()->subDays(29),
        ]);

        Artisan::call('subscriptions:renew');

        $charge = StudentCharge::query()
            ->where('reference_type', 'subscription')
            ->where('reference_id', $subscription->id)
            ->where('description', 'like', 'Renovação%')
            ->firstOrFail();

        $this->assertSame(StudentCharge::STATUS_PENDING, $charge->status);
        $this->assertTrue((bool) $charge->metadata['renewal']);
        $this->assertSame($endsAt->toDateString(), $charge->due_date->toDateString());

        app(StudentChargeService::class)->markPaid($charge, 'manual', $this->networkAdmin->id);

        $subscription->refresh();

        $this->assertSame(StudentSubscription::STATUS_ACTIVE, $subscription->status);
        $this->assertTrue($subscription->ends_at->greaterThan($endsAt));
        $this->assertTrue($subscription->ends_at->greaterThan($endsAt->copy()->addDays(29)));
    }

    /** @test */
    public function test_checkout_allows_deleting_a_pending_item_and_recalculates_the_total(): void
    {
        $this->actingAs($this->networkAdmin);

        $plan = $this->makeBasicPlan();

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [
                [
                    'type' => 'plan',
                    'plan_id' => $plan->id,
                    'starts_at' => now()->toDateString(),
                    'ends_at' => now()->addDays(30)->toDateString(),
                    'weekly_accesses' => 5,
                    'daily_checkins' => 1,
                    'auto_renew' => 1,
                ],
                [
                    'type' => 'day_pass',
                    'starts_at' => now()->toDateString(),
                    'daily_checkins' => 1,
                    'amount' => 45,
                ],
            ],
        ])->assertOk();

        $sale = StudentSale::latest('id')->firstOrFail();
        $item = $sale->items()->where('type', StudentSaleItem::TYPE_DAY_PASS)->firstOrFail();

        $this->delete(route('students.sales.items.destroy', [$this->student, $sale, $item]))
            ->assertRedirect(route('students.sales.checkout', [$this->student, $sale]));

        $this->assertDatabaseMissing('student_sale_items', ['id' => $item->id]);
        $this->assertSame(1, $sale->items()->count());
        $this->assertEquals((float) $plan->price, (float) $sale->fresh()->total);
        $this->assertSame(1, StudentCharge::where('student_sale_id', $sale->id)->count());
    }

    /** @test */
    public function test_paid_sale_items_cannot_be_deleted(): void
    {
        $this->actingAs($this->networkAdmin);

        $plan = $this->makeBasicPlan();

        $this->postJson(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'plan',
                'plan_id' => $plan->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(30)->toDateString(),
                'weekly_accesses' => 5,
                'daily_checkins' => 1,
            ]],
        ])->assertOk();

        $sale = StudentSale::latest('id')->firstOrFail();
        $item = $sale->items()->firstOrFail();

        $this->postJson(route('students.sales.finalize', [$this->student, $sale]), [
            'payments' => [
                ['method' => StudentSalePayment::METHOD_CASH, 'amount' => (float) $sale->total, 'installments' => 1],
            ],
        ])->assertOk();

        $this->from(route('students.sales.checkout', [$this->student, $sale]))
            ->delete(route('students.sales.items.destroy', [$this->student, $sale, $item]))
            ->assertRedirect(route('students.sales.checkout', [$this->student, $sale]))
            ->assertSessionHasErrors('sale');

        $this->assertDatabaseHas('student_sale_items', ['id' => $item->id]);
    }

    private function makeBasicPlan(): MembershipPlan
    {
        return MembershipPlan::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Básico Academia',
            'price' => 99.90,
            'period' => 'monthly',
            'type' => MembershipPlan::TYPE_BASIC,
            'duration_days' => 30,
            'weekly_accesses' => 5,
            'daily_checkins' => 1,
            'all_units' => true,
            'all_modalities' => false,
        ]);
    }

    private function makeClassesPlan(Modality $modality, int $weeklyAccesses): MembershipPlan
    {
        $plan = MembershipPlan::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Aulas '.$modality->name,
            'price' => 149.90,
            'period' => 'monthly',
            'type' => MembershipPlan::TYPE_CLASSES,
            'duration_days' => 30,
            'weekly_accesses' => $weeklyAccesses,
            'daily_checkins' => 1,
            'checkin_advance_minutes' => 20,
            'all_units' => false,
            'all_modalities' => false,
        ]);

        $plan->units()->sync([$this->unit->id]);
        $plan->modalities()->sync([$modality->id]);

        return $plan;
    }

    private function makeModality(string $name): Modality
    {
        return Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => $name,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeClass(Unit $unit, array $attributes = []): SchoolClass
    {
        $class = SchoolClass::create([
            'network_id' => $this->network->id,
            'unit_id' => $unit->id,
            'name' => $attributes['name'] ?? 'Aula '.uniqid(),
            'modality_id' => $attributes['modality_id'] ?? null,
            'drop_in_price' => $attributes['drop_in_price'] ?? 50,
            'status' => $attributes['status'] ?? 'active',
        ]);

        ClassSchedule::create([
            'class_id' => $class->id,
            'weekday' => $attributes['weekday'] ?? now()->dayOfWeek,
            'start_time' => $attributes['start_time'] ?? '07:00',
            'end_time' => $attributes['end_time'] ?? '08:00',
            'vacancies' => $attributes['vacancies'] ?? 10,
            'teacher_id' => $attributes['teacher_id'] ?? null,
        ]);

        return $class;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeStudent(Unit $unit, array $attributes = []): Student
    {
        $student = Student::create(array_merge([
            'network_id' => $this->network->id,
            'name' => 'Aluno '.uniqid(),
            'email' => 'aluno.'.uniqid().'@exemplo.com',
            'status' => 'active',
            'source' => 'panel',
        ], $attributes));

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
