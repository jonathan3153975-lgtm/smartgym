<?php

namespace Tests\Feature;

use App\Models\Exercise;
use App\Models\Network;
use App\Models\Student;
use App\Models\StudentAccount;
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
use Tests\TestCase;

class WorkoutModuleTest extends TestCase
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
            'name' => 'Aluno Treino',
            'email' => 'treino@exemplo.com',
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
            'email' => 'treino@exemplo.com',
            'password' => 'SenhaTreino123',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'status' => 'active',
        ]);

        StudentSubscription::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $this->student->id,
            'status' => StudentSubscription::STATUS_ACTIVE,
            'plan_name' => 'Plano Treino',
            'price' => 129.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'paid_at' => now(),
        ]);
    }

    /** @test */
    public function test_admin_can_manage_the_exercise_catalog(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->get(route('admin.exercises.index'))
            ->assertOk()
            ->assertSee('Exercícios')
            ->assertSee($this->exercise->name);

        $this->postJson(route('admin.exercises.store'), [
            'name' => 'Remada baixa',
            'muscle_group' => 'back',
            'equipment' => 'machine',
            'instructions' => 'Puxe mantendo a coluna neutra.',
            'status' => 'active',
        ])->assertOk()->assertJsonPath('success', true);

        $exercise = Exercise::where('name', 'Remada baixa')->firstOrFail();

        $this->assertSame($this->network->id, $exercise->network_id);

        $this->putJson(route('admin.exercises.update', $exercise), [
            'name' => 'Remada baixa pronada',
            'muscle_group' => 'back',
            'equipment' => 'machine',
            'instructions' => 'Atualizado.',
            'status' => 'active',
        ])->assertOk();

        $this->assertSame('Remada baixa pronada', $exercise->fresh()->name);

        $this->patchJson(route('admin.exercises.toggle-status', $exercise))
            ->assertOk()
            ->assertJsonPath('status', 'inactive');

        $this->deleteJson(route('admin.exercises.destroy', $exercise))->assertOk();

        $this->assertSoftDeleted('exercises', ['id' => $exercise->id]);
    }

    /** @test */
    public function test_admin_can_create_a_plan_and_build_its_sessions_and_exercises(): void
    {
        $this->actingAs($this->networkAdmin);

        $this->postJson(route('admin.workout-plans.store'), [
            'name' => 'Hipertrofia - Fase 1',
            'objective' => 'Ganho de massa muscular',
            'status' => 'active',
        ])->assertOk()->assertJsonPath('success', true);

        $plan = WorkoutPlan::firstOrFail();

        $this->assertNull($plan->student_id);
        $this->assertSame(WorkoutPlan::VISIBILITY_PUBLIC, $plan->visibility);

        $this->get(route('admin.workout-plans.index'))
            ->assertOk()
            ->assertSee('Planos de treino')
            ->assertSee('Hipertrofia - Fase 1');

        $this->post(route('admin.workout-plans.sessions.store', $plan), [
            'name' => 'Treino A - Costas e bíceps',
            'notes' => 'Aquecer antes de iniciar.',
        ])->assertRedirect(route('admin.workout-plans.builder', $plan));

        $session = WorkoutSession::firstOrFail();

        $this->post(route('admin.workout-sessions.exercises.store', $session), [
            'exercise_id' => $this->exercise->id,
            'sets' => 3,
            'reps' => 10,
            'rest_seconds' => 60,
            'suggested_load' => '20 kg',
        ])->assertRedirect(route('admin.workout-plans.builder', $plan));

        $workoutExercise = WorkoutExercise::firstOrFail();

        $this->assertSame($this->exercise->name, $workoutExercise->exercise_name);
        $this->assertSame($this->exercise->muscle_group, $workoutExercise->muscle_group);
        $this->assertSame(3, $workoutExercise->sets);

        $this->get(route('admin.workout-plans.builder', $plan))
            ->assertOk()
            ->assertSee('Hipertrofia - Fase 1')
            ->assertSee('Treino A - Costas e bíceps')
            ->assertSee($this->exercise->name)
            ->assertSee('Adicionar sessão');
    }

    /** @test */
    public function test_student_can_start_complete_and_review_a_workout(): void
    {
        $plan = $this->createPlan();
        $session = $this->createSession($plan);
        $workoutExercise = $this->createWorkoutExercise($session);

        $this->login();

        $this->get(route('student.portal.workouts.index', $this->network))
            ->assertOk()
            ->assertSee('Hipertrofia - Fase 1')
            ->assertSee('Treino A');

        $this->get(route('student.portal.workouts.session', [$this->network, $session]))
            ->assertOk()
            ->assertSee($this->exercise->name)
            ->assertSee('Iniciar treino');

        $this->post(route('student.portal.workouts.start', [$this->network, $session]))
            ->assertRedirect();

        $log = WorkoutLog::firstOrFail();

        $this->assertSame(WorkoutLog::STATUS_IN_PROGRESS, $log->status);

        $this->get(route('student.portal.workouts.log', [$this->network, $log]))
            ->assertOk()
            ->assertSee('Treino em andamento')
            ->assertSee($this->exercise->name)
            ->assertSee('Descanso 60s')
            ->assertSee('Concluir série');

        $this->post(route('student.portal.workouts.complete', [$this->network, $log]), [
            'sets' => [
                [
                    'workout_exercise_id' => $workoutExercise->id,
                    'exercise_id' => $this->exercise->id,
                    'exercise_name' => $this->exercise->name,
                    'muscle_group' => $this->exercise->muscle_group,
                    'set_number' => 1,
                    'reps' => 12,
                    'weight_kg' => 20,
                    'rest_seconds' => 60,
                ],
                [
                    'workout_exercise_id' => $workoutExercise->id,
                    'exercise_id' => $this->exercise->id,
                    'exercise_name' => $this->exercise->name,
                    'muscle_group' => $this->exercise->muscle_group,
                    'set_number' => 2,
                    'reps' => 10,
                    'weight_kg' => 22.5,
                    'rest_seconds' => 60,
                ],
            ],
        ])->assertRedirect(route('student.portal.workouts.history.show', [$this->network, $log]));

        $log->refresh();

        $this->assertSame(WorkoutLog::STATUS_COMPLETED, $log->status);
        $this->assertNotNull($log->completed_at);
        $this->assertNotNull($log->duration_seconds);
        $this->assertSame(2, $log->sets()->count());
        $log->load('sets');
        $this->assertEqualsWithDelta(465.0, $log->total_volume, 0.01);

        $this->get(route('student.portal.workouts.history', $this->network))
            ->assertOk()
            ->assertSee('Histórico de treinos')
            ->assertSee('Treino A');

        $this->get(route('student.portal.workouts.history.show', [$this->network, $log]))
            ->assertOk()
            ->assertSee($this->exercise->name)
            ->assertSee('465');

        $this->get(route('student.portal.workouts.evolution', $this->network))
            ->assertOk()
            ->assertSee('Evolução dos treinos')
            ->assertSee('Volume por grupo muscular');
    }

    /** @test */
    public function test_student_without_an_active_plan_cannot_access_workouts(): void
    {
        StudentSubscription::query()->update(['status' => StudentSubscription::STATUS_CANCELED]);

        $this->login();

        $this->get(route('student.portal.workouts.index', $this->network))
            ->assertRedirect(route('student.portal.plans', $this->network))
            ->assertSessionHasErrors('plan');
    }

    /** @test */
    public function test_student_cannot_access_another_students_workout(): void
    {
        $plan = $this->createPlan();
        $session = $this->createSession($plan);
        $this->createWorkoutExercise($session);
        $log = WorkoutLog::create([
            'network_id' => $this->network->id,
            'student_id' => $this->student->id,
            'workout_plan_id' => $plan->id,
            'workout_session_id' => $session->id,
            'session_name' => $session->name,
            'status' => WorkoutLog::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        $otherStudent = Student::create([
            'network_id' => $this->network->id,
            'name' => 'Outro Aluno',
            'email' => 'outro.treino@exemplo.com',
            'status' => 'active',
        ]);

        StudentAccount::create([
            'network_id' => $this->network->id,
            'student_id' => $otherStudent->id,
            'email' => 'outro.treino@exemplo.com',
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

        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'outro.treino@exemplo.com',
            'password' => 'SenhaOutro123',
        ])->assertRedirect();

        $this->get(route('student.portal.workouts.log', [$this->network, $log]))->assertNotFound();
        $this->get(route('student.portal.workouts.session', [$this->network, $session]))->assertNotFound();
    }

    /** @test */
    public function test_admin_can_reorder_session_exercises(): void
    {
        $this->actingAs($this->networkAdmin);

        $secondExercise = Exercise::create([
            'network_id' => $this->network->id,
            'name' => 'Exercício secundário',
            'muscle_group' => 'back',
            'equipment' => 'barbell',
            'status' => 'active',
        ]);

        $plan = WorkoutPlan::create([
            'network_id' => $this->network->id,
            'name' => 'Treino para ordenar',
            'visibility' => WorkoutPlan::VISIBILITY_PUBLIC,
            'status' => WorkoutPlan::STATUS_ACTIVE,
        ]);

        $session = $plan->sessions()->create(['name' => 'Sessão', 'order' => 1]);

        $first = $session->exercises()->create([
            'exercise_id' => $this->exercise->id,
            'exercise_name' => $this->exercise->name,
            'muscle_group' => $this->exercise->muscle_group,
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
            'rest_seconds' => 60,
        ]);

        $second = $session->exercises()->create([
            'exercise_id' => $secondExercise->id,
            'exercise_name' => $secondExercise->name,
            'muscle_group' => $secondExercise->muscle_group,
            'order' => 2,
            'sets' => 3,
            'reps' => 10,
            'rest_seconds' => 60,
        ]);

        $this->postJson(route('admin.workout-sessions.exercises.reorder', $session), [
            'ids' => [$second->id, $first->id],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(1, $second->fresh()->order);
        $this->assertSame(2, $first->fresh()->order);
    }

    private function login(): void
    {
        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'treino@exemplo.com',
            'password' => 'SenhaTreino123',
        ])->assertRedirect();
    }

    private function createPlan(): WorkoutPlan
    {
        return WorkoutPlan::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $this->student->id,
            'name' => 'Hipertrofia - Fase 1',
            'objective' => 'Ganho de massa muscular',
            'visibility' => WorkoutPlan::VISIBILITY_PRIVATE,
            'status' => WorkoutPlan::STATUS_ACTIVE,
        ]);
    }

    private function createSession(WorkoutPlan $plan): WorkoutSession
    {
        return $plan->sessions()->create([
            'name' => 'Treino A',
            'day_label' => 'Segunda-feira',
            'order' => 1,
        ]);
    }

    private function createWorkoutExercise(WorkoutSession $session): WorkoutExercise
    {
        return $session->exercises()->create([
            'exercise_id' => $this->exercise->id,
            'exercise_name' => $this->exercise->name,
            'muscle_group' => $this->exercise->muscle_group,
            'order' => 1,
            'sets' => 3,
            'reps' => 10,
            'rest_seconds' => 60,
            'suggested_load' => '20 kg',
        ]);
    }
}
