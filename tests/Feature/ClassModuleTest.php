<?php

namespace Tests\Feature;

use App\Models\ClassBooking;
use App\Models\ClassSchedule;
use App\Models\Modality;
use App\Models\Network;
use App\Models\Person;
use App\Models\Region;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentNotification;
use App\Models\StudentUnit;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $networkAdmin;

    private User $unitAdmin;

    private Network $network;

    private Region $region;

    private Unit $unit;

    private Unit $otherUnit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->networkAdmin = User::where('email', 'admin@smartgym.com.br')->firstOrFail();
        $this->unitAdmin = User::where('email', 'gerente@smartgym.com.br')->firstOrFail();
        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $this->region = Region::where('network_id', $this->network->id)->firstOrFail();
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();
        $this->otherUnit = Unit::where('identifier', 'UNID002')->firstOrFail();
    }

    /** @test */
    public function test_network_admin_can_create_a_class_and_define_its_schedule(): void
    {
        $this->actingAs($this->networkAdmin);

        $teacher = Person::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'person_type' => 'individual',
            'category' => 'staff',
            'job_function' => Person::FUNCTION_INSTRUCTOR,
            'name' => 'Professor da Unidade',
            'status' => 'active',
        ]);

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->unit->id,
            'name' => 'Musculação Manhã',
            'status' => 'active',
            'drop_in_price' => 50,
        ])->assertOk()->assertJsonPath('success', true);

        $class = SchoolClass::where('name', 'Musculação Manhã')->firstOrFail();

        $this->assertSame($this->network->id, $class->network_id);
        $this->assertSame($this->unit->id, $class->unit_id);

        $this->putJson(route('admin.classes.schedules.sync', $class), [
            'schedules' => [[
                'weekday' => 1,
                'start_time' => '07:00',
                'end_time' => '08:00',
                'person_id' => $teacher->id,
                'vacancies' => 10,
            ]],
        ])->assertOk()->assertJsonPath('success', true);

        $schedule = $class->schedules()->firstOrFail();

        $this->assertSame($teacher->id, $schedule->person_id);
        $this->assertSame('Segunda-feira', $schedule->weekday_label);
        $this->assertSame('07:00 - 08:00', $schedule->time_label);
        $this->assertSame(10, $schedule->vacancies);

        $this->get(route('admin.classes.index'))
            ->assertOk()
            ->assertSee('Musculação Manhã')
            ->assertSee('1 horário(s)');
    }

    /** @test */
    public function test_teacher_must_be_a_staff_person_with_a_teaching_function(): void
    {
        $instructor = Person::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->otherUnit->id,
            'person_type' => 'individual',
            'category' => 'staff',
            'job_function' => Person::FUNCTION_INSTRUCTOR,
            'name' => 'Professor da Outra Unidade',
            'status' => 'active',
        ]);

        $this->actingAs($this->networkAdmin);

        $class = SchoolClass::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'name' => 'Turma Inválida',
            'status' => 'active',
        ]);

        $this->putJson(route('admin.classes.schedules.sync', $class), [
            'schedules' => [[
                'weekday' => 2,
                'start_time' => '09:00',
                'person_id' => $instructor->id,
                'vacancies' => 10,
            ]],
        ])->assertStatus(422)->assertJsonValidationErrors('schedules.0.person_id');
    }

    /** @test */
    public function test_unit_is_required_and_unit_admin_is_limited_to_their_unit(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->postJson(route('admin.classes.store'), [
            'name' => 'Sem unidade',
            'status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('unit_id');

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->otherUnit->id,
            'name' => 'Turma da Outra Unidade',
            'status' => 'active',
        ])->assertOk();

        $this->actingAs($this->unitAdmin);

        $this->get(route('admin.classes.index'))
            ->assertOk()
            ->assertDontSee('Turma da Outra Unidade');

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->otherUnit->id,
            'name' => 'Turma Bloqueada',
            'status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('unit_id');

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->unit->id,
            'name' => 'Turma da Minha Unidade',
            'status' => 'active',
        ])->assertOk();
    }

    /** @test */
    public function test_class_can_be_updated_and_removed_and_schedules_follow(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->unit->id,
            'name' => 'Turma Editável',
            'status' => 'active',
        ])->assertOk();

        $class = SchoolClass::where('name', 'Turma Editável')->firstOrFail();

        $this->putJson(route('admin.classes.schedules.sync', $class), [
            'schedules' => [[
                'weekday' => 6,
                'start_time' => '08:00',
                'end_time' => '09:30',
                'vacancies' => 10,
            ]],
        ])->assertOk();

        $schedule = $class->schedules()->firstOrFail();

        $this->putJson(route('admin.classes.update', $class), [
            'unit_id' => $this->unit->id,
            'name' => 'Turma Atualizada',
            'status' => 'inactive',
        ])->assertOk();

        $class = $class->fresh();

        $this->assertSame('Turma Atualizada', $class->name);
        $this->assertSame('inactive', $class->status);
        $this->assertSame('Sábado', $schedule->fresh()->weekday_label);

        $this->deleteJson(route('admin.classes.destroy', $class))
            ->assertOk();

        $this->assertSoftDeleted('classes', ['id' => $class->id]);
    }

    /** @test */
    public function test_class_can_be_linked_to_a_modality_available_in_the_unit(): void
    {
        $this->actingAs($this->networkAdmin);

        $modality = Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Funcional',
        ]);
        $modality->units()->attach($this->unit->id);

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->unit->id,
            'modality_id' => $modality->id,
            'name' => 'Funcional Manhã',
            'status' => 'active',
        ])->assertOk();

        $class = SchoolClass::where('name', 'Funcional Manhã')->firstOrFail();

        $this->assertSame($modality->id, $class->modality_id);

        $this->get(route('admin.classes.index'))
            ->assertOk()
            ->assertSee('Funcional Manhã')
            ->assertSee('Funcional');
    }

    /** @test */
    public function test_modality_must_be_available_in_the_class_unit(): void
    {
        $this->actingAs($this->networkAdmin);

        $modality = Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Spinning',
        ]);
        $modality->units()->attach($this->otherUnit->id);

        $this->postJson(route('admin.classes.store'), [
            'unit_id' => $this->unit->id,
            'modality_id' => $modality->id,
            'name' => 'Spinning Inválido',
            'status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('modality_id');
    }

    /** @test */
    public function test_removing_a_schedule_cancels_future_bookings_and_grants_class_credit(): void
    {
        $this->actingAs($this->networkAdmin);

        $class = SchoolClass::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'name' => 'Boxe',
            'drop_in_price' => 50,
            'status' => 'active',
        ]);

        $schedule = ClassSchedule::create([
            'class_id' => $class->id,
            'weekday' => now()->dayOfWeek,
            'start_time' => '18:00',
            'end_time' => '19:00',
            'vacancies' => 10,
        ]);

        $student = Student::create([
            'network_id' => $this->network->id,
            'name' => 'Aluno Reserva',
            'email' => 'reserva@exemplo.com',
            'status' => 'active',
        ]);

        StudentUnit::create([
            'student_id' => $student->id,
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'status' => 'active',
            'is_primary' => true,
            'starts_at' => now()->toDateString(),
        ]);

        $booking = ClassBooking::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'class_schedule_id' => $schedule->id,
            'status' => ClassBooking::STATUS_CONFIRMED,
            'type' => ClassBooking::TYPE_SINGLE,
            'booked_for' => now()->addDay()->toDateString(),
            'amount' => 50,
            'paid_at' => now(),
        ]);

        $this->putJson(route('admin.classes.schedules.sync', $class), [
            'schedules' => [],
        ])->assertOk();

        $booking->refresh();

        $this->assertSame(ClassBooking::STATUS_CANCELED, $booking->status);
        $this->assertTrue($booking->credit_available);
        $this->assertDatabaseHas('student_notifications', [
            'student_id' => $student->id,
            'type' => 'class_credit',
        ]);
        $this->assertSame(1, StudentNotification::where('student_id', $student->id)->count());
    }
}
