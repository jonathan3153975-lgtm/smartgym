<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\MembershipPlan;
use App\Models\Network;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentAssessment;
use App\Models\StudentSubscription;
use App\Models\StudentUnit;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkoutLog;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentAssessmentTest extends TestCase
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
            'name' => 'Aluno Avaliação',
            'email' => 'avaliacao@exemplo.com',
            'emergency_contact_name' => 'Contato do Cadastro',
            'emergency_contact_phone' => '(41) 98888-0000',
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
            'email' => 'avaliacao@exemplo.com',
            'password' => 'SenhaAvaliacao123',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function test_student_without_an_active_plan_is_redirected_to_the_plan_catalog(): void
    {
        $this->login();

        $this->get(route('student.portal.assessment.show', $this->network))
            ->assertRedirect(route('student.portal.plans', $this->network))
            ->assertSessionHasErrors('plan');
    }

    /** @test */
    public function test_student_with_an_active_plan_can_open_the_assessment_form(): void
    {
        $this->activateSubscription();
        $this->login();

        $this->get(route('student.portal.assessment.show', $this->network))
            ->assertOk()
            ->assertSee('Ficha de avaliação')
            ->assertSee('Objetivo principal')
            ->assertSee('Questionário de prontidão')
            ->assertSee('Contato do Cadastro')
            ->assertSee('(41) 98888-0000');
    }

    /** @test */
    public function test_student_can_submit_the_assessment(): void
    {
        $this->activateSubscription();
        $this->login();

        $payload = $this->payload([
            'goal' => 'muscle_gain',
            'parq_answers' => array_merge(
                array_fill_keys(array_keys(StudentAssessment::PARQ_QUESTIONS), '0'),
                ['chest_pain' => '1']
            ),
        ]);

        $this->post(route('student.portal.assessment.store', $this->network), $payload)
            ->assertRedirect(route('student.portal.assessment.show', $this->network));

        $assessment = StudentAssessment::firstOrFail();

        $this->assertSame($this->student->id, $assessment->student_id);
        $this->assertSame($this->network->id, $assessment->network_id);
        $this->assertSame(StudentAssessment::STATUS_COMPLETED, $assessment->status);
        $this->assertSame('muscle_gain', $assessment->goal);
        $this->assertTrue($assessment->parq_positive);
        $this->assertTrue($assessment->parq_answers['chest_pain']);
        $this->assertFalse($assessment->parq_answers['heart_condition']);
        $this->assertNotNull($assessment->submitted_at);
        $this->assertTrue($assessment->consent);
        $this->assertEqualsWithDelta(80.0, (float) $assessment->weight_kg, 0.001);

        $this->get(route('student.portal.assessment.show', $this->network))
            ->assertOk()
            ->assertSee('Ficha de avaliação')
            ->assertSee('Ganho de massa muscular')
            ->assertSee('Atenção');
    }

    /** @test */
    public function test_assessment_accepts_comma_separated_measurements(): void
    {
        $this->activateSubscription();
        $this->login();

        $this->post(route('student.portal.assessment.store', $this->network), $this->payload([
            'weight_kg' => '80,500',
            'height_cm' => '180,00',
            'waist_cm' => '90,5',
        ]))->assertRedirect(route('student.portal.assessment.show', $this->network));

        $assessment = StudentAssessment::firstOrFail();

        $this->assertEqualsWithDelta(80.5, (float) $assessment->weight_kg, 0.001);
        $this->assertEqualsWithDelta(180.0, (float) $assessment->height_cm, 0.001);
        $this->assertEqualsWithDelta(90.5, (float) $assessment->waist_cm, 0.001);
    }

    /** @test */
    public function test_staff_can_create_an_assessment_for_a_student(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->get(route('students.assessments.create', $this->student))
            ->assertOk()
            ->assertSee('Nova avaliação')
            ->assertSee('Salvar avaliação');

        $this->post(route('students.assessments.store', $this->student), $this->payload([
            'weight_kg' => '82,500',
            'height_cm' => '181,00',
        ]))->assertRedirect(route('students.assessment', $this->student));

        $assessment = $this->student->assessment;

        $this->assertNotNull($assessment);
        $this->assertSame(StudentAssessment::STATUS_COMPLETED, $assessment->status);
        $this->assertTrue($assessment->consent);
        $this->assertEqualsWithDelta(82.5, (float) $assessment->weight_kg, 0.001);
        $this->assertEqualsWithDelta(181.0, (float) $assessment->height_cm, 0.001);
    }

    /** @test */
    public function test_assessment_without_parq_alerts_is_flagged_as_safe(): void
    {
        $this->activateSubscription();
        $this->login();

        $this->post(route('student.portal.assessment.store', $this->network), $this->payload())
            ->assertRedirect(route('student.portal.assessment.show', $this->network));

        $assessment = StudentAssessment::firstOrFail();

        $this->assertFalse($assessment->parq_positive);

        $this->get(route('student.portal.assessment.show', $this->network))
            ->assertOk()
            ->assertSee('Ficha de avaliação')
            ->assertDontSee('Atenção');
    }

    /** @test */
    public function test_all_parq_questions_are_required(): void
    {
        $this->activateSubscription();
        $this->login();

        $answers = array_fill_keys(array_keys(StudentAssessment::PARQ_QUESTIONS), '0');
        unset($answers['heart_condition']);

        $this->post(route('student.portal.assessment.store', $this->network), $this->payload([
            'parq_answers' => $answers,
        ]))->assertSessionHasErrors('parq_answers');

        $this->assertDatabaseCount('student_assessments', 0);
    }

    /** @test */
    public function test_new_submission_creates_a_reassessment_and_preserves_history(): void
    {
        $this->activateSubscription();
        $this->login();

        $this->post(route('student.portal.assessment.store', $this->network), $this->payload([
            'goal' => 'weight_loss',
            'weight_kg' => 80,
        ]))->assertRedirect(route('student.portal.assessment.show', $this->network));

        $this->post(route('student.portal.assessment.store', $this->network), $this->payload([
            'goal' => 'conditioning',
            'weight_kg' => 78,
        ]))->assertRedirect(route('student.portal.assessment.show', $this->network));

        $this->assertSame(2, StudentAssessment::count());

        $assessments = StudentAssessment::orderByDesc('id')->get();

        $this->assertSame('conditioning', $assessments->first()->goal);
        $this->assertEqualsWithDelta(78.0, (float) $assessments->first()->weight_kg, 0.001);
        $this->assertSame('weight_loss', $assessments->last()->goal);
        $this->assertEqualsWithDelta(80.0, (float) $assessments->last()->weight_kg, 0.001);
    }

    /** @test */
    public function test_reassessment_form_is_prefilled_and_history_compares_assessments(): void
    {
        $this->activateSubscription();
        $this->login();

        $this->post(route('student.portal.assessment.store', $this->network), $this->payload([
            'goal' => 'weight_loss',
            'weight_kg' => 80,
            'body_fat_percent' => 20,
            'waist_cm' => 90,
        ]))->assertRedirect();

        $first = StudentAssessment::firstOrFail();

        $this->get(route('student.portal.assessment.create', $this->network))
            ->assertOk()
            ->assertSee('Nova reavaliação')
            ->assertSee('value="80.00"', false);

        $this->post(route('student.portal.assessment.store', $this->network), $this->payload([
            'goal' => 'conditioning',
            'weight_kg' => 78,
            'body_fat_percent' => 18,
            'waist_cm' => 88,
        ]))->assertRedirect(route('student.portal.assessment.show', $this->network));

        $this->get(route('student.portal.assessment.show', $this->network))
            ->assertOk()
            ->assertSee('Última avaliação')
            ->assertSee('-2,5%');

        $this->get(route('student.portal.assessment.history', $this->network))
            ->assertOk()
            ->assertSee('Histórico de avaliações')
            ->assertSee('Perda de peso')
            ->assertSee('Condicionamento físico');

        $this->get(route('student.portal.assessment.show-one', [$this->network, $first]))
            ->assertOk()
            ->assertSee('Ficha de avaliação')
            ->assertSee('Perda de peso');
    }

    /** @test */
    public function test_student_cannot_view_another_students_assessment(): void
    {
        $this->activateSubscription();
        $this->login();

        $this->post(route('student.portal.assessment.store', $this->network), $this->payload([
            'goal' => 'weight_loss',
        ]))->assertRedirect();

        $first = StudentAssessment::firstOrFail();

        $otherStudent = Student::create([
            'network_id' => $this->network->id,
            'name' => 'Outro Aluno Avaliação',
            'email' => 'outro.avaliacao@exemplo.com',
            'status' => 'active',
        ]);

        StudentAccount::create([
            'network_id' => $this->network->id,
            'student_id' => $otherStudent->id,
            'email' => 'outro.avaliacao@exemplo.com',
            'password' => 'SenhaOutro123',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'status' => 'active',
        ]);

        StudentSubscription::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $otherStudent->id,
            'status' => StudentSubscription::STATUS_ACTIVE,
            'plan_name' => 'Plano Outro',
            'price' => 99.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'paid_at' => now(),
        ]);

        $this->post(route('student.portal.logout', $this->network));
        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'outro.avaliacao@exemplo.com',
            'password' => 'SenhaOutro123',
        ])->assertRedirect();

        $this->get(route('student.portal.assessment.show-one', [$this->network, $first]))
            ->assertNotFound();
    }

    /** @test */
    public function test_home_offers_the_assessment_after_the_subscription(): void
    {
        $this->activateSubscription();
        $this->login();

        $this->get(route('student.portal.home', $this->network))
            ->assertOk()
            ->assertSee('Ficha de avaliação')
            ->assertSee('Preencher ficha');

        $this->post(route('student.portal.assessment.store', $this->network), $this->payload())
            ->assertRedirect(route('student.portal.assessment.show', $this->network));

        $this->get(route('student.portal.home', $this->network))
            ->assertOk()
            ->assertSee('Ver ficha');
    }

    /** @test */
    public function test_checkout_success_page_offers_the_assessment(): void
    {
        $subscription = $this->activateSubscription();
        $this->login();

        $this->get(route('student.portal.checkout.status', [
            'network' => $this->network,
            'studentSubscription' => $subscription,
        ]))
            ->assertOk()
            ->assertSee('Pagamento aprovado')
            ->assertSee('Preencher ficha de avaliação');
    }

    /** @test */
    public function test_admin_can_view_the_student_assessment(): void
    {
        $this->activateSubscription();
        $this->login();

        $this->post(route('student.portal.assessment.store', $this->network), $this->payload([
            'goal' => 'performance',
        ]))->assertRedirect(route('student.portal.assessment.show', $this->network));

        $this->actingAs($this->networkAdmin)
            ->get(route('students.assessment', $this->student))
            ->assertOk()
            ->assertSee('Ficha de avaliação')
            ->assertSee('Desempenho esportivo')
            ->assertSee('Aluno Avaliação')
            ->assertSee('Questionário de prontidão');
    }

    /** @test */
    public function test_admin_cannot_view_an_assessment_from_another_network(): void
    {
        $otherNetwork = Network::create([
            'name' => 'Outra Rede Avaliação',
            'slug' => 'outra-rede-avaliacao',
            'status' => 'active',
        ]);

        $otherStudent = Student::create([
            'network_id' => $otherNetwork->id,
            'name' => 'Aluno Externo',
            'email' => 'externo.avaliacao@exemplo.com',
            'status' => 'active',
        ]);

        $this->actingAs($this->networkAdmin)
            ->get(route('students.assessment', $otherStudent))
            ->assertNotFound();
    }

    /** @test */
    public function test_student_overview_has_tabs_plan_details_and_no_new_assessment_button(): void
    {
        $this->activateSubscription();

        StudentAssessment::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'goal' => 'weight_loss',
            'experience_level' => 'beginner',
            'training_frequency' => 3,
            'parq_answers' => array_fill_keys(array_keys(StudentAssessment::PARQ_QUESTIONS), false),
            'weight_kg' => 80,
            'height_cm' => 180,
            'submitted_at' => now()->subDays(40),
        ]);

        StudentAssessment::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'goal' => 'conditioning',
            'experience_level' => 'intermediate',
            'training_frequency' => 4,
            'parq_answers' => array_fill_keys(array_keys(StudentAssessment::PARQ_QUESTIONS), false),
            'weight_kg' => 78,
            'height_cm' => 180,
            'submitted_at' => now()->subDays(2),
        ]);

        $this->actingAs($this->networkAdmin);

        $this->get(route('students.show', $this->student))
            ->assertOk()
            ->assertSee('Visão geral')
            ->assertSee('Cadastro')
            ->assertSee('Ficha técnica')
            ->assertSee('Treinos')
            ->assertSee('Financeiro')
            ->assertSee('Detalhes do plano')
            ->assertSee('Plano Avaliação')
            ->assertSee('Unidade de referência')
            ->assertSee('Medidas corporais')
            ->assertSee('Verificar avaliação')
            ->assertSee('-2,5%')
            ->assertDontSee('Histórico')
            ->assertDontSee('Nova avaliação');
    }

    /** @test */
    public function test_assessment_screen_shows_summary_cards_with_previous_comparison(): void
    {
        $this->activateSubscription();

        StudentAssessment::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'goal' => 'weight_loss',
            'experience_level' => 'beginner',
            'training_frequency' => 3,
            'parq_answers' => array_fill_keys(array_keys(StudentAssessment::PARQ_QUESTIONS), false),
            'weight_kg' => 80,
            'height_cm' => 180,
            'body_fat_percent' => 20,
            'waist_cm' => 90,
            'submitted_at' => now()->subDays(40),
        ]);

        StudentAssessment::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'goal' => 'conditioning',
            'experience_level' => 'intermediate',
            'training_frequency' => 4,
            'parq_answers' => array_fill_keys(array_keys(StudentAssessment::PARQ_QUESTIONS), false),
            'weight_kg' => 78,
            'height_cm' => 180,
            'body_fat_percent' => 18,
            'waist_cm' => 88,
            'submitted_at' => now()->subDays(2),
        ]);

        $this->actingAs($this->networkAdmin);

        $this->get(route('students.assessment', $this->student))
            ->assertOk()
            ->assertSee('Peso atual')
            ->assertSee('78,00 kg')
            ->assertSee('-2,00 kg (-2,5%)')
            ->assertSee('Variação total')
            ->assertSee('IMC')
            ->assertSee('24,1')
            ->assertSee('Gordura / cintura')
            ->assertSee('18,0%')
            ->assertSee('cintura 88,00 cm');
    }

    /** @test */
    public function test_technical_sheet_shows_assessment_measures_and_clickable_muscle_groups(): void
    {
        $this->activateSubscription();

        StudentAssessment::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'goal' => 'weight_loss',
            'experience_level' => 'beginner',
            'training_frequency' => 3,
            'parq_answers' => array_fill_keys(array_keys(StudentAssessment::PARQ_QUESTIONS), false),
            'weight_kg' => 80,
            'height_cm' => 180,
            'submitted_at' => now()->subDays(40),
        ]);

        StudentAssessment::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'goal' => 'conditioning',
            'experience_level' => 'intermediate',
            'training_frequency' => 4,
            'parq_answers' => array_fill_keys(array_keys(StudentAssessment::PARQ_QUESTIONS), false),
            'weight_kg' => 78,
            'height_cm' => 180,
            'submitted_at' => now()->subDays(2),
        ]);

        $log = WorkoutLog::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'session_name' => 'Treino de força',
            'status' => WorkoutLog::STATUS_COMPLETED,
            'started_at' => now()->subDays(5),
            'completed_at' => now()->subDays(5)->addHour(),
            'duration_seconds' => 3600,
        ]);

        $log->sets()->create([
            'exercise_name' => 'Supino reto',
            'muscle_group' => 'chest',
            'set_number' => 1,
            'reps' => 10,
            'weight_kg' => 40,
            'completed' => true,
        ]);

        $this->actingAs($this->networkAdmin);

        $this->get(route('students.technical-sheet', $this->student))
            ->assertOk()
            ->assertSee('Acompanhamento de medidas')
            ->assertSee('78,00 kg')
            ->assertSee('Supino reto')
            ->assertSee('40 kg')
            ->assertSee('technicalMuscleGroupModal-chest', false)
            ->assertDontSee('Treinos concluídos');

        $this->get(route('students.workouts', $this->student))
            ->assertOk()
            ->assertSee('Treinos aplicados')
            ->assertSee('Treinos concluídos')
            ->assertSee('Supino reto')
            ->assertSee('Aplicar treino');
    }

    /** @test */
    public function test_plan_details_show_all_units_and_class_schedules(): void
    {
        $plan = MembershipPlan::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Plano Completo',
            'price' => 249.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'weekly_accesses' => 5,
            'daily_checkins' => 2,
            'checkin_advance_minutes' => 30,
            'all_units' => true,
            'all_modalities' => true,
            'type' => MembershipPlan::TYPE_MIXED,
        ]);

        $subscription = $this->activateSubscription();
        $subscription->update([
            'membership_plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'plan_type' => MembershipPlan::TYPE_MIXED,
            'weekly_accesses' => 5,
            'daily_checkins' => 2,
            'checkin_advance_minutes' => 30,
        ]);

        $class = SchoolClass::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'name' => 'Aula de Teste',
            'status' => 'active',
        ]);

        ClassSchedule::create([
            'class_id' => $class->id,
            'weekday' => 1,
            'start_time' => '07:00',
            'end_time' => '08:00',
            'vacancies' => 12,
        ]);

        $this->actingAs($this->networkAdmin);

        $this->get(route('students.show', $this->student))
            ->assertOk()
            ->assertSee('Plano Completo')
            ->assertSee('Unidades acessíveis')
            ->assertSee('Todas')
            ->assertSee('Turmas e dias')
            ->assertSee('Aula de Teste')
            ->assertSee('Segunda-feira 07:00 - 08:00');
    }

    /** @test */
    public function test_staff_can_review_and_edit_an_assessment_with_activity_extract(): void
    {
        $this->activateSubscription();

        $previous = StudentAssessment::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'goal' => 'weight_loss',
            'experience_level' => 'beginner',
            'training_frequency' => 3,
            'parq_answers' => array_fill_keys(array_keys(StudentAssessment::PARQ_QUESTIONS), false),
            'weight_kg' => 80,
            'height_cm' => 180,
            'submitted_at' => now()->subDays(40),
        ]);

        $current = StudentAssessment::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'goal' => 'conditioning',
            'experience_level' => 'intermediate',
            'training_frequency' => 4,
            'parq_answers' => array_fill_keys(array_keys(StudentAssessment::PARQ_QUESTIONS), false),
            'weight_kg' => 78,
            'height_cm' => 180,
            'submitted_at' => now()->subDays(2),
        ]);

        $log = WorkoutLog::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'session_name' => 'Treino de força',
            'status' => WorkoutLog::STATUS_COMPLETED,
            'started_at' => now()->subDays(10),
            'completed_at' => now()->subDays(10)->addHour(),
            'duration_seconds' => 3600,
        ]);

        $log->sets()->create([
            'exercise_name' => 'Supino reto',
            'muscle_group' => 'chest',
            'set_number' => 1,
            'reps' => 10,
            'weight_kg' => 40,
            'completed' => true,
        ]);

        $this->actingAs($this->networkAdmin);

        $this->get(route('students.assessments.verify', [$this->student, $current]))
            ->assertOk()
            ->assertSee('Verificar avaliação')
            ->assertSee('Extrato de atividade')
            ->assertSee('Treino de força')
            ->assertSee('Supino reto')
            ->assertSee('Peito')
            ->assertSee('Alterado')
            ->assertSee('Anterior: Perda de peso');

        $this->put(route('students.assessments.verify.update', [$this->student, $current]), $this->payload([
            'goal' => 'muscle_gain',
            'weight_kg' => '77,500',
            'trainer_notes' => 'Aluno liberado para treino de força.',
        ]))->assertRedirect(route('students.show', $this->student));

        $current->refresh();

        $this->assertSame('muscle_gain', $current->goal);
        $this->assertEqualsWithDelta(77.5, (float) $current->weight_kg, 0.001);
        $this->assertSame('Aluno liberado para treino de força.', $current->trainer_notes);
        $this->assertNotNull($current->reviewed_at);
        $this->assertSame($this->networkAdmin->id, $current->reviewed_by);
    }

    /** @test */
    public function test_user_without_verification_permission_cannot_open_the_review_screen(): void
    {
        $this->activateSubscription();

        $assessment = StudentAssessment::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'goal' => 'weight_loss',
            'experience_level' => 'beginner',
            'training_frequency' => 3,
            'parq_answers' => array_fill_keys(array_keys(StudentAssessment::PARQ_QUESTIONS), false),
            'submitted_at' => now(),
        ]);

        $receptionist = User::where('email', 'recepcao@smartgym.com.br')->firstOrFail();

        $this->actingAs($receptionist)
            ->get(route('students.assessments.verify', [$this->student, $assessment]))
            ->assertForbidden();
    }

    private function login(): void
    {
        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'avaliacao@exemplo.com',
            'password' => 'SenhaAvaliacao123',
        ])->assertRedirect();
    }

    private function activateSubscription(): StudentSubscription
    {
        return StudentSubscription::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $this->student->id,
            'status' => StudentSubscription::STATUS_ACTIVE,
            'plan_name' => 'Plano Avaliação',
            'price' => 129.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'paid_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'goal' => 'weight_loss',
            'goal_notes' => 'Perder 5 kg com saúde.',
            'experience_level' => 'beginner',
            'training_frequency' => 4,
            'medical_conditions' => 'Nenhuma',
            'medications' => '',
            'injuries' => '',
            'surgeries' => '',
            'smoker' => '0',
            'alcohol_consumption' => 'social',
            'sleep_hours' => 7,
            'stress_level' => 3,
            'parq_answers' => array_fill_keys(array_keys(StudentAssessment::PARQ_QUESTIONS), '0'),
            'emergency_contact_name' => 'Maria Contato',
            'emergency_contact_phone' => '(41) 99999-0000',
            'weight_kg' => 80,
            'height_cm' => 180,
            'body_fat_percent' => 18.5,
            'waist_cm' => 90,
            'consent' => '1',
        ], $overrides);
    }
}
