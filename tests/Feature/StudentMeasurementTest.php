<?php

namespace Tests\Feature;

use App\Models\BodyMeasurement;
use App\Models\Network;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentAssessment;
use App\Models\StudentSubscription;
use App\Models\StudentUnit;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentMeasurementTest extends TestCase
{
    use RefreshDatabase;

    private User $networkAdmin;

    private Network $network;

    private Unit $unit;

    private Student $student;

    private StudentAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();

        $this->student = Student::create([
            'network_id' => $this->network->id,
            'name' => 'Aluno Medidas',
            'email' => 'medidas@exemplo.com',
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
            'email' => 'medidas@exemplo.com',
            'password' => 'SenhaMedidas123',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'status' => 'active',
        ]);

        StudentSubscription::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $this->student->id,
            'status' => StudentSubscription::STATUS_ACTIVE,
            'plan_name' => 'Plano Medidas',
            'price' => 129.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'paid_at' => now(),
        ]);

        StudentAssessment::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'height_cm' => 180,
            'consent' => true,
            'parq_answers' => [],
            'submitted_at' => now(),
        ]);
    }

    /** @test */
    public function test_student_with_an_active_plan_can_open_the_measurements_page(): void
    {
        $this->login();

        $this->get(route('student.portal.measurements.index', $this->network))
            ->assertOk()
            ->assertSee('Minhas medidas')
            ->assertSee('Registrar medidas')
            ->assertSee('Histórico de medidas');
    }

    /** @test */
    public function test_student_without_an_active_plan_is_redirected_to_plans(): void
    {
        StudentSubscription::query()->update(['status' => StudentSubscription::STATUS_CANCELED]);

        $this->login();

        $this->get(route('student.portal.measurements.index', $this->network))
            ->assertRedirect(route('student.portal.plans', $this->network))
            ->assertSessionHasErrors('plan');
    }

    /** @test */
    public function test_student_can_register_weekly_measurements(): void
    {
        $this->login();

        $this->post(route('student.portal.measurements.store', $this->network), [
            'measured_at' => now()->toDateString(),
            'weight_kg' => 80,
            'body_fat_percent' => 18.5,
            'waist_cm' => 90,
            'hip_cm' => 100,
            'chest_cm' => 105,
            'arm_cm' => 36,
            'thigh_cm' => 60,
            'calf_cm' => 38,
            'notes' => 'Medida em jejum.',
        ])->assertRedirect(route('student.portal.measurements.index', $this->network));

        $measurement = BodyMeasurement::firstOrFail();

        $this->assertSame($this->student->id, $measurement->student_id);
        $this->assertSame($this->network->id, $measurement->network_id);
        $this->assertEqualsWithDelta(80.0, (float) $measurement->weight_kg, 0.001);
        $this->assertEqualsWithDelta(24.7, $measurement->bmiForHeight(180), 0.01);

        $this->get(route('student.portal.measurements.index', $this->network))
            ->assertOk()
            ->assertSee('80,00 kg')
            ->assertSee('24,7')
            ->assertSee('Histórico de medidas');
    }

    /** @test */
    public function test_registering_the_same_date_updates_the_measurement(): void
    {
        $this->login();

        $date = now()->toDateString();

        $this->post(route('student.portal.measurements.store', $this->network), [
            'measured_at' => $date,
            'weight_kg' => 80,
        ])->assertRedirect();

        $this->post(route('student.portal.measurements.store', $this->network), [
            'measured_at' => $date,
            'weight_kg' => 79.2,
        ])->assertRedirect();

        $this->assertSame(1, BodyMeasurement::count());
        $this->assertEqualsWithDelta(79.2, (float) BodyMeasurement::firstOrFail()->weight_kg, 0.001);
    }

    /** @test */
    public function test_measurement_requires_a_valid_date_and_weight(): void
    {
        $this->login();

        $this->post(route('student.portal.measurements.store', $this->network), [
            'measured_at' => now()->addDay()->toDateString(),
            'weight_kg' => '',
        ])->assertSessionHasErrors(['measured_at', 'weight_kg']);

        $this->assertDatabaseCount('body_measurements', 0);
    }

    /** @test */
    public function test_student_can_remove_a_measurement(): void
    {
        $this->login();

        $measurement = BodyMeasurement::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'measured_at' => now()->toDateString(),
            'weight_kg' => 80,
        ]);

        $this->delete(route('student.portal.measurements.destroy', [$this->network, $measurement]))
            ->assertRedirect(route('student.portal.measurements.index', $this->network));

        $this->assertDatabaseCount('body_measurements', 0);
    }

    /** @test */
    public function test_history_shows_weight_variation_between_records(): void
    {
        $this->login();

        BodyMeasurement::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'measured_at' => now()->subWeek()->toDateString(),
            'weight_kg' => 82,
        ]);

        BodyMeasurement::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'measured_at' => now()->toDateString(),
            'weight_kg' => 80,
        ]);

        $this->get(route('student.portal.measurements.index', $this->network))
            ->assertOk()
            ->assertSee('-2,00 kg')
            ->assertSee('Variação total');
    }

    /** @test */
    public function test_admin_can_view_the_student_measurements(): void
    {
        BodyMeasurement::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'measured_at' => now()->toDateString(),
            'weight_kg' => 80,
            'body_fat_percent' => 18.5,
            'waist_cm' => 90,
        ]);

        $this->actingAs($this->networkAdmin)
            ->get(route('students.measurements', $this->student))
            ->assertOk()
            ->assertSee('Medidas corporais')
            ->assertSee('Aluno Medidas')
            ->assertSee('80,00 kg')
            ->assertSee('24,7');

        $this->get(route('students.show', $this->student))
            ->assertOk()
            ->assertSee('Medidas corporais')
            ->assertSee('80,00 kg');
    }

    /** @test */
    public function test_admin_cannot_view_measurements_from_another_network(): void
    {
        $otherNetwork = Network::create([
            'name' => 'Outra Rede Medidas',
            'slug' => 'outra-rede-medidas',
            'status' => 'active',
        ]);

        $otherStudent = Student::create([
            'network_id' => $otherNetwork->id,
            'name' => 'Aluno Externo Medidas',
            'email' => 'externo.medidas@exemplo.com',
            'status' => 'active',
        ]);

        $this->actingAs($this->networkAdmin)
            ->get(route('students.measurements', $otherStudent))
            ->assertNotFound();
    }

    private function login(): void
    {
        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'medidas@exemplo.com',
            'password' => 'SenhaMedidas123',
        ])->assertRedirect();
    }
}
