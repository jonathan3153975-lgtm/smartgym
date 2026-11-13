<?php

namespace Tests\Feature;

use App\Models\ClassBooking;
use App\Models\Network;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentAssessment;
use App\Models\StudentCheckin;
use App\Models\StudentSubscription;
use App\Models\StudentUnit;
use App\Models\SubscriptionPayment;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnitPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $networkAdmin;

    private User $unitAdmin;

    private Network $network;

    private Unit $unit;

    private Unit $otherUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->unitAdmin = User::where('email', 'gerente@smartgym.com.br')->firstOrFail();
        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();
        $this->otherUnit = Unit::where('identifier', 'UNID002')->firstOrFail();
    }

    /** @test */
    public function test_unit_admin_opens_the_panel_and_only_sees_their_unit_students(): void
    {
        $mine = $this->makeStudent($this->unit, ['name' => 'Aluno da Unidade']);
        $other = $this->makeStudent($this->otherUnit, ['name' => 'Aluno de Outra Unidade']);

        $this->actingAs($this->unitAdmin);

        $this->get(route('unit.dashboard'))
            ->assertOk()
            ->assertSee($this->unit->name)
            ->assertSee('Visão geral')
            ->assertSee('Alunos ativos');

        $this->get(route('unit.students.index'))
            ->assertOk()
            ->assertSee($mine->name)
            ->assertDontSee($other->name);
    }

    /** @test */
    public function test_network_admin_can_select_a_unit_in_the_panel(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->get(route('unit.dashboard', ['unit_id' => $this->otherUnit->id]))
            ->assertOk()
            ->assertSee($this->otherUnit->name);

        $this->get(route('unit.dashboard'))
            ->assertOk()
            ->assertSee($this->otherUnit->name);
    }

    /** @test */
    public function test_platform_user_cannot_access_the_unit_panel(): void
    {
        $developer = User::where('email', 'jonathan3153975@gmail.com')->firstOrFail();

        $this->actingAs($developer)
            ->get(route('unit.dashboard'))
            ->assertForbidden();
    }

    /** @test */
    public function test_unit_admin_can_review_an_assessment_from_their_unit(): void
    {
        $mine = $this->makeStudent($this->unit);
        $other = $this->makeStudent($this->otherUnit);

        $assessment = $this->makeAssessment($mine);
        $otherAssessment = $this->makeAssessment($other);

        $this->actingAs($this->unitAdmin);

        $this->get(route('unit.assessments.index'))
            ->assertOk()
            ->assertSee($mine->name)
            ->assertSee('A verificar');

        $this->post(route('unit.assessments.review', $assessment))
            ->assertRedirect();

        $assessment->refresh();

        $this->assertNotNull($assessment->reviewed_at);
        $this->assertSame($this->unitAdmin->id, $assessment->reviewed_by);

        $this->get(route('unit.assessments.index'))
            ->assertOk()
            ->assertSee('Verificada');

        $this->post(route('unit.assessments.review', $otherAssessment))
            ->assertNotFound();
    }

    /** @test */
    public function test_unit_admin_can_confirm_a_pending_subscription_manually(): void
    {
        $student = $this->makeStudent($this->unit);
        $subscription = $this->makeSubscription($student, StudentSubscription::STATUS_PENDING);

        $other = $this->makeStudent($this->otherUnit);
        $otherSubscription = $this->makeSubscription($other, StudentSubscription::STATUS_PENDING);

        $this->actingAs($this->unitAdmin);

        $this->get(route('unit.payments.index'))
            ->assertOk()
            ->assertSee($student->name)
            ->assertSee('Aguardando pagamento');

        $this->post(route('unit.subscriptions.confirm', $subscription))
            ->assertRedirect();

        $subscription->refresh();

        $this->assertSame(StudentSubscription::STATUS_ACTIVE, $subscription->status);
        $this->assertNotNull($subscription->starts_at);
        $this->assertNotNull($subscription->ends_at);

        $this->assertDatabaseHas('subscription_payments', [
            'student_subscription_id' => $subscription->id,
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'status' => SubscriptionPayment::STATUS_APPROVED,
        ]);

        $this->post(route('unit.subscriptions.confirm', $otherSubscription))
            ->assertNotFound();
    }

    /** @test */
    public function test_access_page_lists_accounts_and_trial_checkins(): void
    {
        $withoutAccess = $this->makeStudent($this->unit, ['name' => 'Aluno Sem Acesso']);
        $withAccess = $this->makeStudent($this->unit, ['name' => 'Aluno Com Acesso']);

        StudentAccount::create([
            'network_id' => $this->network->id,
            'student_id' => $withAccess->id,
            'email' => $withAccess->email,
            'password' => 'SenhaAcesso123',
            'must_change_password' => true,
            'status' => 'active',
        ]);

        StudentCheckin::create([
            'network_id' => $this->network->id,
            'student_id' => $withoutAccess->id,
            'unit_id' => $this->unit->id,
            'type' => StudentCheckin::TYPE_TRIAL,
            'checked_in_at' => now(),
        ]);

        $this->actingAs($this->unitAdmin)
            ->get(route('unit.access.index'))
            ->assertOk()
            ->assertSee('Aluno Sem Acesso')
            ->assertSee('Aluno Com Acesso')
            ->assertSee('Sem acesso')
            ->assertSee('Temporária')
            ->assertSee('Experimental');

        $this->get(route('unit.access.index', ['access' => 'without']))
            ->assertOk()
            ->assertSee('Aluno Sem Acesso')
            ->assertDontSee('Aluno Com Acesso');
    }

    /** @test */
    public function test_unit_panel_manages_class_reservations(): void
    {
        $class = $this->makeClass($this->unit, ['name' => 'Aula do painel', 'vacancies' => 1]);
        $student = $this->makeStudent($this->unit, ['name' => 'Aluno Reserva']);
        $this->makeSubscription($student, StudentSubscription::STATUS_ACTIVE);

        $this->actingAs($this->unitAdmin);

        $this->post(route('unit.class-bookings.store'), [
            'class_id' => $class->id,
            'student_id' => $student->id,
            'type' => 'plan',
        ])->assertRedirect();

        $booking = ClassBooking::where('student_id', $student->id)->firstOrFail();

        $this->assertSame(ClassBooking::STATUS_CONFIRMED, $booking->status);
        $this->assertSame(ClassBooking::TYPE_PLAN, $booking->type);

        $this->get(route('unit.classes.index'))
            ->assertOk()
            ->assertSee('Aula do painel')
            ->assertSee('1/1');

        $this->post(route('unit.class-bookings.store'), [
            'class_id' => $class->id,
            'student_id' => $student->id,
            'type' => 'plan',
        ])->assertRedirect()->assertSessionHasErrors('booking');

        $this->post(route('unit.class-bookings.cancel', $booking))
            ->assertRedirect();

        $this->assertSame(ClassBooking::STATUS_CANCELED, $booking->fresh()->status);

        $otherClass = $this->makeClass($this->otherUnit);
        $otherStudent = $this->makeStudent($this->otherUnit);
        $otherBooking = ClassBooking::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->otherUnit->id,
            'student_id' => $otherStudent->id,
            'class_id' => $otherClass->id,
            'status' => ClassBooking::STATUS_PENDING,
            'type' => ClassBooking::TYPE_SINGLE,
            'booked_for' => now()->toDateString(),
            'amount' => 50,
        ]);

        $this->post(route('unit.class-bookings.cancel', $otherBooking))->assertNotFound();
    }

    /** @test */
    public function test_student_with_plan_can_reserve_a_class(): void
    {
        $student = $this->makeStudent($this->unit);
        $this->makeSubscription($student, StudentSubscription::STATUS_ACTIVE);

        StudentAccount::create([
            'network_id' => $this->network->id,
            'student_id' => $student->id,
            'email' => $student->email,
            'password' => 'SenhaAluno123',
            'must_change_password' => false,
            'status' => 'active',
        ]);

        $class = $this->makeClass($this->unit, ['name' => 'Aula inclusa', 'vacancies' => 2]);

        $this->post(route('student.portal.login.store', $this->network), [
            'email' => $student->email,
            'password' => 'SenhaAluno123',
        ])->assertRedirect();

        $this->post(route('student.portal.classes.reserve', [$this->network, $class]))
            ->assertRedirect(route('student.portal.classes.index', $this->network));

        $booking = ClassBooking::where('student_id', $student->id)->firstOrFail();

        $this->assertSame(ClassBooking::STATUS_CONFIRMED, $booking->status);
        $this->assertSame(ClassBooking::TYPE_PLAN, $booking->type);

        $this->get(route('student.portal.classes.index', $this->network))
            ->assertOk()
            ->assertSee('Aula inclusa')
            ->assertSee('Aula reservada');
    }

    private function makeStudent(Unit $unit, array $attributes = []): Student
    {
        $student = Student::create(array_merge([
            'network_id' => $this->network->id,
            'name' => 'Aluno '.Str::random(4),
            'email' => 'aluno.'.Str::lower(Str::random(8)).'@exemplo.com',
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

    private function makeAssessment(Student $student): StudentAssessment
    {
        return StudentAssessment::create([
            'network_id' => $this->network->id,
            'student_id' => $student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'goal' => 'weight_loss',
            'experience_level' => 'beginner',
            'training_frequency' => 3,
            'weight_kg' => 80,
            'height_cm' => 180,
            'parq_answers' => [],
            'consent' => true,
            'submitted_at' => now(),
        ]);
    }

    private function makeSubscription(Student $student, string $status): StudentSubscription
    {
        return StudentSubscription::create([
            'network_id' => $this->network->id,
            'unit_id' => $student->units()->first()->unit_id,
            'student_id' => $student->id,
            'status' => $status,
            'plan_name' => 'Plano Teste',
            'price' => 129.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'starts_at' => $status === StudentSubscription::STATUS_ACTIVE ? now() : null,
            'ends_at' => $status === StudentSubscription::STATUS_ACTIVE ? now()->addDays(30) : null,
            'paid_at' => $status === StudentSubscription::STATUS_ACTIVE ? now() : null,
        ]);
    }

    private function makeClass(Unit $unit, array $attributes = []): SchoolClass
    {
        return SchoolClass::create(array_merge([
            'network_id' => $this->network->id,
            'unit_id' => $unit->id,
            'name' => 'Aula '.Str::random(5),
            'weekday' => now()->dayOfWeek,
            'start_time' => '07:00',
            'end_time' => '08:00',
            'vacancies' => 10,
            'drop_in_price' => 50,
            'status' => 'active',
        ], $attributes));
    }
}
