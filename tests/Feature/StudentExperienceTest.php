<?php

namespace Tests\Feature;

use App\Models\ClassBooking;
use App\Models\Exercise;
use App\Models\MembershipPlan;
use App\Models\Network;
use App\Models\PaymentGatewaySetting;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentCheckin;
use App\Models\StudentSubscription;
use App\Models\StudentUnit;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkoutExercise;
use App\Models\WorkoutLog;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentExperienceTest extends TestCase
{
    use RefreshDatabase;

    private User $networkAdmin;

    private Network $network;

    private Unit $unit;

    private Student $student;

    private StudentAccount $account;

    private Exercise $exercise;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();
        $this->exercise = Exercise::where('network_id', $this->network->id)->firstOrFail();

        $this->student = Student::create([
            'network_id' => $this->network->id,
            'name' => 'Aluno Experiência',
            'email' => 'experiencia@exemplo.com',
            'status' => 'active',
            'source' => 'portal',
        ]);

        StudentUnit::create([
            'student_id' => $this->student->id,
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'status' => 'active',
            'is_primary' => true,
            'starts_at' => now()->toDateString(),
        ]);

        $this->account = StudentAccount::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'email' => 'experiencia@exemplo.com',
            'password' => 'SenhaExperiencia123',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function test_staff_login_uses_a_portuguese_error_message(): void
    {
        $this->post(route('login'), [
            'email' => 'admin@smartgym.com.br',
            'password' => 'senha-errada',
        ])->assertSessionHasErrors(['email' => 'E-mail ou senha inválidos.']);
    }

    /** @test */
    public function test_student_without_plan_can_use_the_trial_checkin_only_once(): void
    {
        $this->loginStudent();

        $this->post(route('student.portal.checkin.trial', $this->network))
            ->assertRedirect(route('student.portal.home', $this->network))
            ->assertSessionHas('success');

        $this->assertSame(1, StudentCheckin::where('student_id', $this->student->id)->count());

        $this->post(route('student.portal.checkin.trial', $this->network))
            ->assertRedirect(route('student.portal.home', $this->network))
            ->assertSessionHasErrors('trial');

        $this->assertSame(1, StudentCheckin::where('student_id', $this->student->id)->count());

        $this->get(route('student.portal.home', $this->network))
            ->assertOk()
            ->assertSee('Check-in experimental já utilizado');
    }

    /** @test */
    public function test_student_with_active_plan_cannot_use_the_trial_checkin(): void
    {
        $this->activateSubscription();
        $this->loginStudent();

        $this->post(route('student.portal.checkin.trial', $this->network))
            ->assertRedirect(route('student.portal.home', $this->network))
            ->assertSessionHasErrors('trial');

        $this->assertDatabaseCount('student_checkins', 0);
    }

    /** @test */
    public function test_admin_can_create_a_daily_plan_with_one_day_duration(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->postJson(route('admin.membership-plans.store'), [
            'name' => 'Diária avulsa',
            'price' => 39.90,
            'period' => 'daily',
            'weekly_accesses' => 1,
            'daily_checkins' => 1,
            'all_units' => true,
            'all_modalities' => true,
        ])->assertOk();

        $plan = MembershipPlan::where('name', 'Diária avulsa')->firstOrFail();

        $this->assertSame('daily', $plan->period);
        $this->assertSame(1, $plan->duration_days);
        $this->assertSame('Diária', $plan->period_label);
    }

    /** @test */
    public function test_student_can_see_todays_classes_and_the_unit(): void
    {
        $class = $this->createClass(['name' => 'Funcional da manhã', 'drop_in_price' => 50]);

        $this->loginStudent();

        $this->get(route('student.portal.classes.index', $this->network))
            ->assertOk()
            ->assertSee('Funcional da manhã')
            ->assertSee($this->unit->name)
            ->assertSee('50,00')
            ->assertSee('Pagar aula avulsa');
    }

    /** @test */
    public function test_student_can_pay_a_drop_in_class_and_webhook_confirms_it(): void
    {
        $class = $this->createClass(['name' => 'Aula avulsa de teste', 'drop_in_price' => 45]);
        $this->createGatewayCredentials();

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-class-1',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-class-1',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-class-1',
            ], 201),
        ]);

        $this->loginStudent();

        $this->post(route('student.portal.classes.checkout', [$this->network, $class]))
            ->assertRedirect('https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-class-1');

        $booking = ClassBooking::firstOrFail();

        $this->assertSame(ClassBooking::STATUS_PENDING, $booking->status);
        $this->assertEqualsWithDelta(45.0, (float) $booking->amount, 0.001);
        $this->assertSame('pref-class-1', $booking->preference_id);

        Http::fake([
            'api.mercadopago.com/v1/payments/999' => Http::response([
                'id' => 999,
                'status' => 'approved',
                'transaction_amount' => 45,
                'external_reference' => 'class-booking:'.$booking->id,
                'metadata' => ['class_booking_id' => $booking->id],
            ]),
        ]);

        $this->postJson(route('webhooks.mercado-pago', ['class_booking' => $booking->id]), [
            'type' => 'payment',
            'data' => ['id' => '999'],
        ])->assertOk()->assertJsonPath('status', ClassBooking::STATUS_CONFIRMED);

        $this->assertSame(ClassBooking::STATUS_CONFIRMED, $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->paid_at);
    }

    /** @test */
    public function test_student_can_create_copy_and_edit_a_workout_plan(): void
    {
        $this->activateSubscription();

        $publicPlan = $this->createPublicPlan();

        $this->loginStudent();

        $this->get(route('student.portal.workouts.index', $this->network))
            ->assertOk()
            ->assertSee('Treino Público')
            ->assertSee('Copiar treino');

        $this->post(route('student.portal.workouts.plans.copy', [$this->network, $publicPlan]))
            ->assertRedirect();

        $copied = WorkoutPlan::where('student_id', $this->student->id)->firstOrFail();

        $this->assertSame(WorkoutPlan::VISIBILITY_PRIVATE, $copied->visibility);
        $this->assertSame($publicPlan->id, $copied->source_plan_id);
        $this->assertSame(1, $copied->sessions()->count());
        $this->assertSame(1, $copied->sessions()->first()->exercises()->count());

        $this->post(route('student.portal.workouts.plans.store', $this->network), [
            'name' => 'Meu treino próprio',
            'objective' => 'Hipertrofia',
            'visibility' => 'private',
        ])->assertRedirect();

        $own = WorkoutPlan::where('name', 'Meu treino próprio')->firstOrFail();

        $this->assertSame($this->student->id, $own->student_id);
        $this->assertSame(WorkoutPlan::VISIBILITY_PRIVATE, $own->visibility);

        $this->patchJson(route('student.portal.workouts.plans.visibility', [$this->network, $own]))
            ->assertOk()
            ->assertJsonPath('visibility', WorkoutPlan::VISIBILITY_PUBLIC);

        $this->get(route('student.portal.workouts.plans.builder', [$this->network, $own]))
            ->assertOk()
            ->assertSee('Adicionar sessão');

        $this->post(route('student.portal.workouts.plans.sessions.store', [$this->network, $own]), [
            'name' => 'Treino de pernas',
            'notes' => 'Ênfase em glúteos.',
        ])->assertRedirect();

        $session = WorkoutSession::where('workout_plan_id', $own->id)->firstOrFail();

        $this->post(route('student.portal.workouts.plans.exercises.store', [$this->network, $session]), [
            'exercise_id' => $this->exercise->id,
            'sets' => 4,
            'reps' => 12,
            'rest_seconds' => 45,
            'suggested_load' => '20,000',
        ])->assertRedirect();

        $this->assertSame(4, WorkoutExercise::where('workout_session_id', $session->id)->firstOrFail()->sets);
    }

    /** @test */
    public function test_technical_sheet_shows_student_progress_and_applies_a_public_plan(): void
    {
        $this->activateSubscription();
        $publicPlan = $this->createPublicPlan();

        $personal = WorkoutPlan::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $this->student->id,
            'name' => 'Treino do aluno',
            'visibility' => WorkoutPlan::VISIBILITY_PRIVATE,
            'status' => WorkoutPlan::STATUS_ACTIVE,
        ]);

        $session = $personal->sessions()->create(['name' => 'Treino A', 'order' => 1]);
        $workoutExercise = $session->exercises()->create([
            'exercise_id' => $this->exercise->id,
            'exercise_name' => $this->exercise->name,
            'muscle_group' => $this->exercise->muscle_group,
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
            'rest_seconds' => 60,
            'suggested_load' => '20 kg',
        ]);

        $log = WorkoutLog::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'workout_plan_id' => $personal->id,
            'workout_session_id' => $session->id,
            'session_name' => $session->name,
            'status' => WorkoutLog::STATUS_COMPLETED,
            'started_at' => now(),
            'completed_at' => now(),
            'duration_seconds' => 3600,
        ]);

        $log->sets()->create([
            'workout_exercise_id' => $workoutExercise->id,
            'exercise_id' => $this->exercise->id,
            'exercise_name' => $this->exercise->name,
            'muscle_group' => $this->exercise->muscle_group,
            'set_number' => 1,
            'reps' => 10,
            'weight_kg' => 20,
        ]);

        $this->actingAs($this->networkAdmin)
            ->get(route('students.technical-sheet', $this->student))
            ->assertOk()
            ->assertSee('Ficha técnica')
            ->assertSee('Aluno Experiência')
            ->assertSee('Grupos musculares treinados')
            ->assertSee('Treino Público')
            ->assertSee('Aplicar treino existente');

        $this->post(route('students.technical-sheet.apply', $this->student), [
            'workout_plan_id' => $publicPlan->id,
        ])->assertRedirect();

        $this->assertTrue(
            WorkoutPlan::where('student_id', $this->student->id)
                ->where('source_plan_id', $publicPlan->id)
                ->exists()
        );

        $this->post(route('students.technical-sheet.plan', $this->student), [
            'name' => 'Treino personalizado novo',
            'objective' => 'Condicionamento',
        ])->assertRedirect();

        $this->assertDatabaseHas('workout_plans', [
            'student_id' => $this->student->id,
            'name' => 'Treino personalizado novo',
        ]);
    }

    /** @test */
    public function test_exercise_accepts_equipment_options_and_reference_image(): void
    {
        Storage::fake('public');

        $this->actingAs($this->networkAdmin);

        $this->post(route('admin.exercises.store'), [
            'name' => 'Exercício com imagem',
            'muscle_group' => 'chest',
            'equipment' => 'barbell',
            'instructions' => 'Execução de teste.',
            'image' => UploadedFile::fake()->image('exercise.png', 300, 300),
            'status' => 'active',
        ], [
            'Accept' => 'application/json',
        ])->assertOk()->assertJsonPath('success', true);

        $exercise = Exercise::where('name', 'Exercício com imagem')->firstOrFail();

        $this->assertSame('barbell', $exercise->equipment);
        $this->assertSame('Barra', $exercise->equipment_label);
        $this->assertNotNull($exercise->image);
        Storage::disk('public')->assertExists($exercise->image);

        $this->get(route('exercise.image', $exercise))->assertOk();

        $this->get(route('admin.exercises.index'))
            ->assertOk()
            ->assertSee('Barra')
            ->assertSee('Filtrar por equipamento');

        $this->post(route('admin.exercises.store'), [
            'name' => 'Exercício com imagem retangular',
            'muscle_group' => 'chest',
            'equipment' => 'dumbbell',
            'status' => 'active',
            'image' => UploadedFile::fake()->image('wide.png', 400, 300),
        ], [
            'Accept' => 'application/json',
        ])->assertStatus(422)->assertJsonValidationErrors('image');
    }

    private function loginStudent(): void
    {
        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'experiencia@exemplo.com',
            'password' => 'SenhaExperiencia123',
        ])->assertRedirect();
    }

    private function activateSubscription(): StudentSubscription
    {
        return StudentSubscription::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $this->student->id,
            'status' => StudentSubscription::STATUS_ACTIVE,
            'plan_name' => 'Plano Experiência',
            'price' => 129.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'paid_at' => now(),
        ]);
    }

    private function createClass(array $attributes = []): SchoolClass
    {
        return SchoolClass::create(array_merge([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'name' => 'Turma de teste',
            'weekday' => now()->dayOfWeek,
            'start_time' => '07:00',
            'end_time' => '08:00',
            'vacancies' => 10,
            'drop_in_price' => 50,
            'status' => 'active',
        ], $attributes));
    }

    private function createGatewayCredentials(): PaymentGatewaySetting
    {
        return PaymentGatewaySetting::create([
            'network_id' => $this->network->id,
            'unit_id' => null,
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADO_PAGO,
            'environment' => 'sandbox',
            'access_token' => 'network-token',
            'is_active' => true,
        ]);
    }

    private function createPublicPlan(): WorkoutPlan
    {
        $plan = WorkoutPlan::create([
            'network_id' => $this->network->id,
            'student_id' => null,
            'name' => 'Treino Público',
            'objective' => 'Treino genérico da equipe',
            'visibility' => WorkoutPlan::VISIBILITY_PUBLIC,
            'status' => WorkoutPlan::STATUS_ACTIVE,
        ]);

        $session = $plan->sessions()->create([
            'name' => 'Treino A',
            'notes' => 'Sessão pública.',
            'order' => 1,
        ]);

        $session->exercises()->create([
            'exercise_id' => $this->exercise->id,
            'exercise_name' => $this->exercise->name,
            'muscle_group' => $this->exercise->muscle_group,
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
            'rest_seconds' => 60,
            'suggested_load' => '20 kg',
        ]);

        return $plan;
    }
}
