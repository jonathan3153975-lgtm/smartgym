<?php

namespace Tests\Feature;

use App\Models\ClassBooking;
use App\Models\ClassSchedule;
use App\Models\Network;
use App\Models\Region;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCharge;
use App\Models\StudentCheckin;
use App\Models\StudentSale;
use App\Models\StudentSaleItem;
use App\Models\StudentSubscription;
use App\Models\Unit;
use App\Models\User;
use App\Support\NetworkPolicy;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentModuleTest extends TestCase
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
    public function test_student_screen_summarizes_active_plans_and_same_day_accesses(): void
    {
        $this->actingAs($this->networkAdmin);

        $student = Student::create([
            'network_id' => $this->network->id,
            'name' => 'Aluno Acesso',
            'email' => 'acesso@exemplo.com',
            'status' => 'active',
        ]);

        $student->units()->create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'status' => 'active',
            'is_primary' => true,
            'starts_at' => now()->toDateString(),
        ]);

        $sale = StudentSale::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $student->id,
            'status' => StudentSale::STATUS_PAID,
            'launched_at' => now()->toDateString(),
            'total' => 45,
        ]);

        StudentSaleItem::create([
            'student_sale_id' => $sale->id,
            'type' => StudentSaleItem::TYPE_DAY_PASS,
            'unit_id' => $this->unit->id,
            'description' => 'Diária de hoje',
            'amount' => 45,
            'launched_at' => now()->toDateString(),
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->toDateString(),
            'status' => StudentCharge::STATUS_PAID,
        ]);

        StudentCheckin::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $student->id,
            'type' => StudentCheckin::TYPE_TRIAL,
            'checked_in_at' => now(),
        ]);

        $class = SchoolClass::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'name' => 'Funcional Teste',
            'status' => 'active',
        ]);

        $schedule = ClassSchedule::create([
            'class_id' => $class->id,
            'weekday' => now()->dayOfWeek,
            'start_time' => '18:00',
            'end_time' => '19:00',
            'vacancies' => 10,
        ]);

        ClassBooking::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $student->id,
            'class_id' => $class->id,
            'class_schedule_id' => $schedule->id,
            'status' => ClassBooking::STATUS_CONFIRMED,
            'type' => ClassBooking::TYPE_SINGLE,
            'booked_for' => now()->toDateString(),
            'amount' => 30,
        ]);

        $this->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Sem plano ativo no momento.')
            ->assertSee('Diária adquirida hoje')
            ->assertSee('Aula agendada hoje: Funcional Teste 18:00')
            ->assertSee('Check-in experimental hoje');

        $this->get(route('students.index'))
            ->assertOk()
            ->assertSee('Diária hoje')
            ->assertSee('Aula hoje: Funcional Teste 18:00')
            ->assertSee('Experimental hoje');

        StudentSubscription::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $student->id,
            'status' => StudentSubscription::STATUS_ACTIVE,
            'plan_name' => 'Plano Premium',
            'price' => 199.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'paid_at' => now(),
        ]);

        $this->get(route('students.show', $student))
            ->assertOk()
            ->assertSee('Plano ativo')
            ->assertSee('Plano Premium');
    }

    /** @test */
    public function test_network_admin_can_create_a_student_with_primary_and_additional_units(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->postJson(route('students.store'), [
            'name' => 'Maria Aluna',
            'social_name' => 'Maria',
            'cpf' => '10000137073',
            'birth_date' => '1995-04-10',
            'gender' => 'female',
            'email' => 'maria.aluna@exemplo.com',
            'phone' => '(41) 99999-1234',
            'primary_unit_id' => $this->unit->id,
            'additional_unit_ids' => [$this->otherUnit->id],
            'source' => 'walk_in',
            'status' => 'active',
        ])->assertOk()->assertJsonPath('success', true);

        $student = Student::where('cpf_raw', '10000137073')->firstOrFail();

        $this->assertSame($this->network->id, $student->network_id);
        $this->assertSame('100.001.370-73', $student->cpf);
        $this->assertSame('Maria Aluna', $student->name);
        $this->assertSame(2, $student->units()->count());
        $this->assertDatabaseHas('student_units', [
            'student_id' => $student->id,
            'unit_id' => $this->unit->id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('student_units', [
            'student_id' => $student->id,
            'unit_id' => $this->otherUnit->id,
            'is_primary' => false,
        ]);

        $this->get(route('students.index'))
            ->assertOk()
            ->assertSee('Maria Aluna');

        $this->get(route('students.show', ['student' => $student, 'tab' => 'cadastro']))
            ->assertOk()
            ->assertSee('Unidades vinculadas')
            ->assertSee('Principal');
    }

    /** @test */
    public function test_invalid_and_duplicated_cpf_are_rejected(): void
    {
        $this->actingAs($this->networkAdmin);

        $payload = [
            'name' => 'Aluno CPF',
            'cpf' => '11111111111',
            'primary_unit_id' => $this->unit->id,
            'status' => 'active',
        ];

        $this->postJson(route('students.store'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf');

        $payload['cpf'] = '10000137073';

        $this->postJson(route('students.store'), $payload)
            ->assertOk();

        $this->postJson(route('students.store'), array_merge($payload, ['name' => 'Outro Aluno']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf');
    }

    /** @test */
    public function test_unit_admin_can_only_manage_students_from_their_unit(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->postJson(route('students.store'), [
            'name' => 'Aluno da Outra Unidade',
            'cpf' => '10000109614',
            'primary_unit_id' => $this->otherUnit->id,
            'status' => 'active',
        ])->assertOk();

        $otherStudent = Student::where('name', 'Aluno da Outra Unidade')->firstOrFail();

        $this->actingAs($this->unitAdmin);

        $this->get(route('students.index'))
            ->assertOk()
            ->assertDontSee('Aluno da Outra Unidade');

        $this->get(route('students.show', $otherStudent))->assertNotFound();

        $this->postJson(route('students.store'), [
            'name' => 'Aluno Unidade Inválida',
            'primary_unit_id' => $this->otherUnit->id,
            'status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('primary_unit_id');

        $this->postJson(route('students.store'), [
            'name' => 'Aluno da Minha Unidade',
            'primary_unit_id' => $this->unit->id,
            'status' => 'active',
        ])->assertOk()->assertJsonPath('success', true);

        $this->get(route('students.index'))
            ->assertOk()
            ->assertSee('Aluno da Minha Unidade');
    }

    /** @test */
    public function test_student_can_be_updated_toggled_and_removed(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->postJson(route('students.store'), [
            'name' => 'Aluno Teste',
            'cpf' => '10000095990',
            'primary_unit_id' => $this->unit->id,
            'status' => 'active',
        ])->assertOk();

        $student = Student::where('name', 'Aluno Teste')->firstOrFail();

        $this->putJson(route('students.update', $student), [
            'name' => 'Aluno Teste Editado',
            'cpf' => '10000095990',
            'primary_unit_id' => $this->unit->id,
            'status' => 'pending',
        ])->assertOk();

        $this->assertSame('Aluno Teste Editado', $student->fresh()->name);
        $this->assertSame('pending', $student->fresh()->status);

        $this->patchJson(route('students.toggle-status', $student))
            ->assertOk()
            ->assertJsonPath('status', 'active');

        $this->deleteJson(route('students.destroy', $student))
            ->assertOk();

        $this->assertSoftDeleted('students', ['id' => $student->id]);
    }

    /** @test */
    public function test_network_policy_can_block_additional_units(): void
    {
        $policies = NetworkPolicy::forNetwork($this->network);
        $policies['pessoas']['share_across_units'] = false;

        $settings = $this->network->settings;
        $settings['policies'] = $policies;
        $this->network->update(['settings' => $settings]);

        $this->actingAs($this->networkAdmin);

        $this->postJson(route('students.store'), [
            'name' => 'Aluno sem compartilhamento',
            'primary_unit_id' => $this->unit->id,
            'additional_unit_ids' => [$this->otherUnit->id],
            'status' => 'active',
        ])->assertStatus(422)->assertJsonValidationErrors('additional_unit_ids');
    }

    /** @test */
    public function test_student_avatar_is_stored_and_served(): void
    {
        Storage::fake('public');

        $this->actingAs($this->networkAdmin);

        $this->post(route('students.store'), [
            'name' => 'Aluno com Foto',
            'primary_unit_id' => $this->unit->id,
            'status' => 'active',
            'avatar' => UploadedFile::fake()->image('aluno.png', 256, 256),
        ], ['Accept' => 'application/json'])->assertOk();

        $student = Student::where('name', 'Aluno com Foto')->firstOrFail();

        $this->assertNotNull($student->avatar);
        Storage::disk('public')->assertExists($student->avatar);
        $this->get(route('student.avatar', $student))->assertOk();
    }

    /** @test */
    public function test_student_form_uses_the_shared_phone_mask(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->get(route('students.index'))
            ->assertOk()
            ->assertSee('data-phone', false);
    }
}
