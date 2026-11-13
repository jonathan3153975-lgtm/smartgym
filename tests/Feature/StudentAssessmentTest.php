<?php

namespace Tests\Feature;

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
            ->assertSee('Sua ficha de avaliação')
            ->assertSee('Ganho de massa muscular')
            ->assertSee('Atenção');
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
            ->assertSee('Sua ficha de avaliação')
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
            ->assertSee('Comparação com a avaliação anterior')
            ->assertSee('-2,00 kg')
            ->assertSee('-2,0%');

        $this->get(route('student.portal.assessment.history', $this->network))
            ->assertOk()
            ->assertSee('Histórico de avaliações')
            ->assertSee('Perda de peso')
            ->assertSee('Condicionamento físico');

        $this->get(route('student.portal.assessment.show-one', [$this->network, $first]))
            ->assertOk()
            ->assertSee('Sua ficha de avaliação')
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
