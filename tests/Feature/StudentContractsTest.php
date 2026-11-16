<?php

namespace Tests\Feature;

use App\Mail\StudentChargeMail;
use App\Models\ClassBooking;
use App\Models\ClassSchedule;
use App\Models\MembershipPlan;
use App\Models\Network;
use App\Models\PaymentGatewaySetting;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentCharge;
use App\Models\StudentSubscription;
use App\Models\StudentUnit;
use App\Models\Unit;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StudentContractsTest extends TestCase
{
    use RefreshDatabase;

    private User $networkAdmin;

    private User $unitAdmin;

    private User $receptionist;

    private Network $network;

    private Unit $unit;

    private Unit $otherUnit;

    private Student $student;

    private MembershipPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->unitAdmin = User::where('email', 'gerente@smartgym.com.br')->firstOrFail();
        $this->receptionist = User::where('email', 'recepcao@smartgym.com.br')->firstOrFail();
        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();
        $this->otherUnit = Unit::where('identifier', 'UNID002')->firstOrFail();

        $this->student = $this->makeStudent($this->unit, [
            'name' => 'Aluno Contrato',
            'email' => 'contrato@exemplo.com',
            'phone' => '11999998888',
        ]);

        $this->plan = MembershipPlan::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Plano Presencial',
            'price' => 199.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'weekly_accesses' => 5,
            'daily_checkins' => 1,
            'all_units' => true,
            'all_modalities' => true,
        ]);
    }

    /** @test */
    public function test_global_search_returns_students_and_modules_for_the_logged_user(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->getJson(route('global.search', ['q' => 'Aluno Contrato']))
            ->assertOk()
            ->assertJsonFragment(['type' => 'student', 'label' => 'Aluno Contrato'])
            ->assertJsonFragment(['label' => 'Contratos', 'url' => route('students.contracts', $this->student)]);

        $this->getJson(route('global.search', ['q' => 'Alunos']))
            ->assertOk()
            ->assertJsonFragment(['type' => 'module', 'label' => 'Alunos', 'url' => route('students.index')]);

        $this->getJson(route('global.search', ['q' => 'a']))
            ->assertOk()
            ->assertJsonCount(0, 'results');
    }

    /** @test */
    public function test_global_search_respects_the_unit_scope(): void
    {
        $otherStudent = $this->makeStudent($this->otherUnit, ['name' => 'Aluno de Outra Unidade']);

        $this->actingAs($this->unitAdmin);

        $this->getJson(route('global.search', ['q' => 'Aluno Contrato']))
            ->assertOk()
            ->assertJsonFragment(['label' => 'Aluno Contrato']);

        $this->getJson(route('global.search', ['q' => 'Aluno de Outra']))
            ->assertOk()
            ->assertJsonMissing(['label' => $otherStudent->name]);
    }

    /** @test */
    public function test_contracts_page_shows_financial_summary_and_pending_charges(): void
    {
        $this->actingAs($this->networkAdmin);

        $charge = $this->sellPlan();

        $this->get(route('students.contracts', $this->student))
            ->assertOk()
            ->assertSee('Contratos e financeiro')
            ->assertSee('Aluno Contrato')
            ->assertSee('Plano Presencial')
            ->assertSee('Nova venda')
            ->assertSee('R$ 199,90')
            ->assertSee($charge->description);

        $this->assertSame(StudentCharge::STATUS_PENDING, $charge->fresh()->status);
        $this->assertSame(
            StudentSubscription::STATUS_PENDING,
            StudentSubscription::where('student_id', $this->student->id)->firstOrFail()->status
        );
    }

    /** @test */
    public function test_paying_a_plan_charge_activates_the_subscription(): void
    {
        $this->actingAs($this->networkAdmin);

        $charge = $this->sellPlan();

        $this->post(route('students.contracts.pay', [$this->student, $charge]))
            ->assertRedirect();

        $charge->refresh();
        $subscription = StudentSubscription::where('student_id', $this->student->id)->firstOrFail();

        $this->assertSame(StudentCharge::STATUS_PAID, $charge->status);
        $this->assertNotNull($charge->paid_at);
        $this->assertSame(StudentSubscription::STATUS_ACTIVE, $subscription->status);
        $this->assertNotNull($subscription->starts_at);
        $this->assertTrue($subscription->ends_at->greaterThan(now()->addDays(29)));

        $this->assertDatabaseHas('subscription_payments', [
            'student_subscription_id' => $subscription->id,
            'status' => 'approved',
            'provider' => 'manual',
            'external_reference' => 'charge:'.$charge->id,
        ]);
    }

    /** @test */
    public function test_day_pass_sale_keeps_validity_date_and_checkin_limit(): void
    {
        $this->actingAs($this->receptionist);

        $validDate = now()->addDay()->toDateString();

        $this->post(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'day_pass',
                'starts_at' => $validDate,
                'daily_checkins' => 2,
                'amount' => 45.50,
            ]],
        ])->assertRedirect();

        $charge = StudentCharge::where('student_id', $this->student->id)->latest('id')->firstOrFail();
        $subscription = StudentSubscription::where('student_id', $this->student->id)->firstOrFail();

        $this->assertSame(StudentCharge::TYPE_DAY_PASS, $charge->type);
        $this->assertSame($validDate, $charge->metadata['valid_date']);
        $this->assertSame(2, $charge->metadata['checkins']);
        $this->assertSame(2, $subscription->daily_checkins);

        $this->post(route('students.contracts.pay', [$this->student, $charge]))->assertRedirect();

        $subscription->refresh();

        $this->assertSame(StudentSubscription::STATUS_ACTIVE, $subscription->status);
        $this->assertTrue($subscription->starts_at->isSameDay(Carbon::parse($validDate)));
        $this->assertTrue($subscription->ends_at->isSameDay(Carbon::parse($validDate)));
    }

    /** @test */
    public function test_single_class_sale_creates_a_pending_booking_with_checkin_window(): void
    {
        $this->actingAs($this->unitAdmin);

        $class = $this->makeClass($this->unit, [
            'weekday' => now()->dayOfWeek,
            'start_time' => '18:00',
            'end_time' => '19:00',
            'drop_in_price' => 60,
        ]);

        $this->post(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'class',
                'class_schedule_id' => $class->schedules()->firstOrFail()->id,
                'checkin_advance_minutes' => 15,
                'amount' => 60,
            ]],
        ])->assertRedirect();

        $booking = ClassBooking::where('student_id', $this->student->id)->firstOrFail();
        $charge = StudentCharge::where('student_id', $this->student->id)->latest('id')->firstOrFail();

        $this->assertSame(ClassBooking::STATUS_PENDING, $booking->status);
        $this->assertSame(15, $booking->early_minutes);
        $this->assertSame('17:45', $booking->checkin_opens_at->format('H:i'));
        $this->assertSame('18:30', $booking->checkin_closes_at->format('H:i'));
        $this->assertSame(StudentCharge::TYPE_CLASS, $charge->type);
        $this->assertSame('class_booking', $charge->reference_type);

        $this->post(route('students.contracts.pay', [$this->student, $charge]))->assertRedirect();

        $this->assertSame(ClassBooking::STATUS_CONFIRMED, $booking->fresh()->status);
    }

    /** @test */
    public function test_pix_generation_uses_mercado_pago_and_stores_the_qr_code(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->makeSetting($this->unit, 'unit-token');
        $charge = $this->sellOther('Avaliação física', 120);

        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => 987654,
                'status' => 'pending',
                'point_of_interaction' => [
                    'transaction_data' => [
                        'qr_code' => '00020126580014BR.GOV.BCB.PIX...',
                        'qr_code_base64' => 'aW1hZ2VtLWRlLXBpeA==',
                    ],
                ],
            ], 201),
        ]);

        $this->post(route('students.contracts.pix', [$this->student, $charge]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $charge->refresh();

        $this->assertSame('00020126580014BR.GOV.BCB.PIX...', $charge->pix_qr_code);
        $this->assertSame('aW1hZ2VtLWRlLXBpeA==', $charge->pix_qr_code_base64);
        $this->assertNotNull($charge->pix_generated_at);

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://api.mercadopago.com/v1/payments'
                && $request->hasHeader('Authorization', 'Bearer unit-token')
                && ($data['payment_method_id'] ?? null) === 'pix'
                && ($data['external_reference'] ?? null) === 'charge:'.$this->chargeId()
                && str_contains((string) ($data['notification_url'] ?? ''), 'charge=');
        });
    }

    /** @test */
    public function test_charge_can_be_sent_to_the_student_by_email(): void
    {
        Mail::fake();

        $this->actingAs($this->receptionist);

        $charge = $this->sellOther('Camiseta oficial', 89.90);

        $this->post(route('students.contracts.email', [$this->student, $charge]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        Mail::assertSent(StudentChargeMail::class, function (StudentChargeMail $mail) {
            return $mail->hasTo('contrato@exemplo.com')
                && $mail->charge->is($this->charge())
                && str_contains($mail->portalUrl, route('student.portal.finance.index', $this->network));
        });
    }

    /** @test */
    public function test_pending_charge_appears_in_the_student_portal_with_a_notification(): void
    {
        $this->actingAs($this->receptionist);

        $charge = $this->sellOther('Diária promocional', 35);

        $this->makeAccount();
        $this->loginStudent();

        $this->get(route('student.portal.finance.index', $this->network))
            ->assertOk()
            ->assertSee('Meus contratos e pagamentos')
            ->assertSee($charge->description)
            ->assertSee('Pix')
            ->assertSee('Financeiro');

        $this->get(route('student.portal.home', $this->network))
            ->assertOk()
            ->assertSee('Você possui cobrança pendente')
            ->assertSee('Pagar agora');

        $this->get(route('student.portal.finance.index', $this->network))
            ->assertOk()
            ->assertSee('R$ 35,00');
    }

    /** @test */
    public function test_student_can_only_open_their_own_charges_in_the_portal(): void
    {
        $otherStudent = $this->makeStudent($this->unit, [
            'name' => 'Outro Aluno',
            'email' => 'outro@exemplo.com',
        ]);

        $otherCharge = StudentCharge::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $otherStudent->id,
            'type' => StudentCharge::TYPE_OTHER,
            'description' => 'Cobrança de outro aluno',
            'amount' => 50,
            'status' => StudentCharge::STATUS_PENDING,
        ]);

        $this->makeAccount();

        $this->loginStudent();

        $this->post(route('student.portal.finance.pix', [$this->network, $otherCharge]))
            ->assertNotFound();
    }

    /** @test */
    public function test_student_checkin_respects_the_booking_window(): void
    {
        $this->makeAccount();
        $class = $this->makeClass($this->unit);

        $openBooking = $this->makeBooking($class, [
            'checkin_opens_at' => now()->subMinutes(5),
            'checkin_closes_at' => now()->addMinutes(25),
        ]);

        $closedBooking = $this->makeBooking($class, [
            'booked_for' => now()->addDay()->toDateString(),
            'checkin_opens_at' => now()->addDay()->subMinutes(20),
            'checkin_closes_at' => now()->addDay()->addMinutes(30),
        ]);

        $this->loginStudent();

        $this->post(route('student.portal.classes.checkin', [$this->network, $openBooking]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertNotNull($openBooking->fresh()->checked_in_at);

        $this->post(route('student.portal.classes.checkin', [$this->network, $closedBooking]))
            ->assertRedirect()
            ->assertSessionHasErrors('checkin');

        $this->assertNull($closedBooking->fresh()->checked_in_at);
    }

    /** @test */
    public function test_platform_user_cannot_open_student_contracts(): void
    {
        $developer = User::where('email', 'jonathan3153975@gmail.com')->firstOrFail();

        $this->actingAs($developer)
            ->get(route('students.contracts', $this->student))
            ->assertForbidden();
    }

    private function sellPlan(): StudentCharge
    {
        $this->post(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'plan',
                'plan_id' => $this->plan->id,
                'starts_at' => now()->toDateString(),
                'ends_at' => now()->addDays(30)->toDateString(),
            ]],
        ])->assertRedirect();

        return StudentCharge::where('student_id', $this->student->id)->latest('id')->firstOrFail();
    }

    private function sellOther(string $description, float $amount): StudentCharge
    {
        $this->post(route('students.sales.store', $this->student), [
            'items' => [[
                'type' => 'other',
                'description' => $description,
                'amount' => $amount,
            ]],
        ])->assertRedirect();

        return StudentCharge::where('student_id', $this->student->id)->latest('id')->firstOrFail();
    }

    private function charge(): StudentCharge
    {
        return StudentCharge::where('student_id', $this->student->id)->latest('id')->firstOrFail();
    }

    private function chargeId(): int
    {
        return $this->charge()->id;
    }

    private function makeAccount(): StudentAccount
    {
        return StudentAccount::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'email' => 'contrato@exemplo.com',
            'password' => 'SenhaAluno123',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'status' => 'active',
        ]);
    }

    private function loginStudent(): void
    {
        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'contrato@exemplo.com',
            'password' => 'SenhaAluno123',
        ])->assertRedirect();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeStudent(Unit $unit, array $attributes = []): Student
    {
        $student = Student::create(array_merge([
            'network_id' => $this->network->id,
            'name' => 'Aluno Teste',
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeClass(Unit $unit, array $attributes = []): SchoolClass
    {
        $class = SchoolClass::create([
            'network_id' => $this->network->id,
            'unit_id' => $unit->id,
            'name' => $attributes['name'] ?? 'Aula Teste',
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
    private function makeBooking(SchoolClass $class, array $attributes = []): ClassBooking
    {
        return ClassBooking::create(array_merge([
            'network_id' => $this->network->id,
            'unit_id' => $class->unit_id,
            'student_id' => $this->student->id,
            'class_id' => $class->id,
            'class_schedule_id' => $class->schedules()->first()?->id,
            'status' => ClassBooking::STATUS_CONFIRMED,
            'type' => ClassBooking::TYPE_SINGLE,
            'booked_for' => now()->toDateString(),
            'early_minutes' => 20,
            'amount' => 50,
            'paid_at' => now(),
        ], $attributes));
    }

    private function makeSetting(?Unit $unit, string $token): PaymentGatewaySetting
    {
        return PaymentGatewaySetting::create([
            'network_id' => $this->network->id,
            'unit_id' => $unit?->id,
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADO_PAGO,
            'environment' => 'sandbox',
            'access_token' => $token,
            'is_active' => true,
        ]);
    }
}
