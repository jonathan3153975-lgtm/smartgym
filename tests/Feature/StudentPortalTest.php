<?php

namespace Tests\Feature;

use App\Models\MembershipPlan;
use App\Models\Modality;
use App\Models\Network;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalTest extends TestCase
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

        $this->actingAs($this->networkAdmin);

        $this->postJson(route('students.store'), [
            'name' => 'Aluno do Portal',
            'cpf' => '10000082236',
            'email' => 'aluno.portal@exemplo.com',
            'primary_unit_id' => $this->unit->id,
            'status' => 'active',
        ])->assertOk();

        $this->student = Student::where('email', 'aluno.portal@exemplo.com')->firstOrFail();

        $this->put(route('students.access.update', $this->student), [
            'email' => 'aluno.portal@exemplo.com',
            'password' => 'SenhaAluno123',
            'password_confirmation' => 'SenhaAluno123',
            'status' => 'active',
        ])->assertRedirect();

        $this->account = $this->student->fresh()->account;
        $this->account->update(['must_change_password' => false]);
    }

    /** @test */
    public function test_student_can_open_the_login_page_and_log_in(): void
    {
        $this->get(route('student.portal.login', $this->network))
            ->assertOk()
            ->assertSee('Bem-vindo de volta')
            ->assertSee('Acesso do aluno');

        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'aluno.portal@exemplo.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'aluno.portal@exemplo.com',
            'password' => 'SenhaAluno123',
        ])->assertRedirect(route('student.portal.plans', $this->network));

        $this->assertAuthenticatedAs($this->account, 'student');

        $this->get(route('student.portal.home', $this->network))
            ->assertOk()
            ->assertSee('Aluno do Portal')
            ->assertSee('Home')
            ->assertSee('Dashboard');

        $this->get(route('student.portal.dashboard', $this->network))
            ->assertOk()
            ->assertSee('Dashboard em desenvolvimento');

        $this->post(route('student.portal.logout', $this->network))
            ->assertRedirect(route('student.portal.login', $this->network));

        $this->assertGuest('student');
    }

    /** @test */
    public function test_guest_is_redirected_from_home_and_root(): void
    {
        $this->get(route('student.portal.home', $this->network))
            ->assertRedirect(route('student.portal.login', $this->network));

        $this->get(route('student.portal.index', $this->network))
            ->assertRedirect(route('student.portal.login', $this->network));
    }

    /** @test */
    public function test_inactive_account_cannot_log_in(): void
    {
        $this->account->update(['status' => 'inactive']);

        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'aluno.portal@exemplo.com',
            'password' => 'SenhaAluno123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('student');
    }

    /** @test */
    public function test_account_from_another_network_cannot_log_in_on_this_portal(): void
    {
        $otherNetwork = Network::create([
            'name' => 'Outra Rede Portal',
            'slug' => 'outra-rede-portal',
            'status' => 'active',
        ]);

        $this->post(route('student.portal.login.store', $otherNetwork), [
            'email' => 'aluno.portal@exemplo.com',
            'password' => 'SenhaAluno123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('student');
    }

    /** @test */
    public function test_temporary_password_forces_a_password_change_on_first_access(): void
    {
        $this->postJson(route('students.store'), [
            'name' => 'Aluno Senha Temporária',
            'email' => 'temp.student@exemplo.com',
            'primary_unit_id' => $this->unit->id,
            'status' => 'active',
            'access_password' => 'TempPassword123',
            'access_password_confirmation' => 'TempPassword123',
        ])->assertOk();

        $student = Student::where('email', 'temp.student@exemplo.com')->firstOrFail();
        $account = $student->account;

        $this->assertNotNull($account);
        $this->assertTrue($account->must_change_password);

        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'temp.student@exemplo.com',
            'password' => 'TempPassword123',
        ])->assertRedirect(route('student.portal.password.edit', $this->network));

        $this->get(route('student.portal.home', $this->network))
            ->assertRedirect(route('student.portal.password.edit', $this->network));

        $this->post(route('student.portal.password.update', $this->network), [
            'current_password' => 'TempPassword123',
            'password' => 'NewPassword123',
            'password_confirmation' => 'NewPassword123',
        ])->assertRedirect(route('student.portal.plans', $this->network));

        $this->assertFalse($account->fresh()->must_change_password);

        $this->get(route('student.portal.home', $this->network))
            ->assertOk()
            ->assertSee('Aluno Senha Temporária');
    }

    /** @test */
    public function test_access_password_requires_an_email_on_the_student(): void
    {
        $this->postJson(route('students.store'), [
            'name' => 'Aluno Sem E-mail',
            'primary_unit_id' => $this->unit->id,
            'status' => 'active',
            'access_password' => 'TempPassword123',
            'access_password_confirmation' => 'TempPassword123',
        ])->assertStatus(422)->assertJsonValidationErrors('access_password');
    }

    /** @test */
    public function test_student_show_page_exposes_the_access_provisioning(): void
    {
        $this->get(route('students.show', $this->student))
            ->assertOk()
            ->assertSee('Acesso do aluno')
            ->assertSee('aluno.portal@exemplo.com')
            ->assertSee('Último acesso');
    }

    /** @test */
    public function test_guest_can_register_and_lands_on_the_plans_page(): void
    {
        $this->get(route('student.portal.register', $this->network))
            ->assertOk()
            ->assertSee('Crie sua conta');

        $this->post(route('student.portal.register.store', $this->network), [
            'name' => 'Novo Aluno Portal',
            'email' => 'novo.aluno@exemplo.com',
            'phone' => '(41) 99999-0000',
            'unit_id' => $this->unit->id,
            'password' => 'SenhaNova123',
            'password_confirmation' => 'SenhaNova123',
        ])->assertRedirect(route('student.portal.plans', $this->network));

        $student = Student::where('email', 'novo.aluno@exemplo.com')->firstOrFail();

        $this->assertSame($this->network->id, $student->network_id);
        $this->assertSame('portal', $student->source);
        $this->assertSame('active', $student->status);

        $this->assertDatabaseHas('student_units', [
            'student_id' => $student->id,
            'unit_id' => $this->unit->id,
            'is_primary' => true,
        ]);

        $account = $student->account;

        $this->assertNotNull($account);
        $this->assertFalse($account->must_change_password);
        $this->assertAuthenticatedAs($account, 'student');

        $this->get(route('student.portal.plans', $this->network))
            ->assertOk()
            ->assertSee('Escolha o plano ideal para você')
            ->assertSee($this->unit->name);
    }

    /** @test */
    public function test_registration_rejects_an_email_already_used_by_a_student_account(): void
    {
        $this->post(route('student.portal.register.store', $this->network), [
            'name' => 'Aluno Duplicado',
            'email' => 'aluno.portal@exemplo.com',
            'unit_id' => $this->unit->id,
            'password' => 'SenhaNova123',
            'password_confirmation' => 'SenhaNova123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('student');
    }

    /** @test */
    public function test_plans_page_lists_eligible_plans_units_and_registered_classes(): void
    {
        $otherUnit = Unit::where('identifier', 'UNID002')->firstOrFail();

        $modality = Modality::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Cross Training',
        ]);
        $modality->units()->attach($this->unit->id);

        SchoolClass::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'modality_id' => $modality->id,
            'name' => 'Cross Iniciante',
            'weekday' => 1,
            'start_time' => '07:00',
            'end_time' => '08:00',
            'vacancies' => 12,
            'status' => 'active',
        ]);

        $eligiblePlan = MembershipPlan::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Plano Livre',
            'price' => 129.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'weekly_accesses' => 5,
            'daily_checkins' => 1,
            'all_units' => true,
            'all_modalities' => true,
        ]);

        $unitPlan = MembershipPlan::create([
            'network_id' => $this->network->id,
            'created_level' => 'unit',
            'unit_id' => $otherUnit->id,
            'name' => 'Plano Unidade Distante',
            'price' => 89.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'weekly_accesses' => 3,
            'daily_checkins' => 1,
            'all_units' => true,
            'all_modalities' => true,
        ]);

        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'aluno.portal@exemplo.com',
            'password' => 'SenhaAluno123',
        ])->assertRedirect(route('student.portal.plans', $this->network));

        $this->get(route('student.portal.plans', $this->network))
            ->assertOk()
            ->assertSee($eligiblePlan->name)
            ->assertSee('R$ 129,90')
            ->assertSee($this->unit->name)
            ->assertSee('Cross Training')
            ->assertSee('Cross Iniciante')
            ->assertSee('12 vagas')
            ->assertDontSee($unitPlan->name);
    }
}
