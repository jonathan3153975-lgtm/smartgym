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

        $this->get(route('admin.exercises.index', ['search' => $this->exercise->name]))
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
    public function test_student_can_update_the_plan_definitions_when_completing_a_workout(): void
    {
        $plan = $this->createPlan();
        $session = $this->createSession($plan);
        $workoutExercise = $this->createWorkoutExercise($session);

        $this->login();

        $this->post(route('student.portal.workouts.start', [$this->network, $session]))
            ->assertRedirect();

        $log = WorkoutLog::firstOrFail();

        $this->post(route('student.portal.workouts.complete', [$this->network, $log]), [
            'update_plan' => 1,
            'sets' => [
                [
                    'workout_exercise_id' => $workoutExercise->id,
                    'exercise_id' => $this->exercise->id,
                    'exercise_name' => $this->exercise->name,
                    'muscle_group' => $this->exercise->muscle_group,
                    'set_number' => 1,
                    'reps' => 12,
                    'weight_kg' => '30,500',
                    'rest_seconds' => 60,
                ],
            ],
        ])->assertRedirect(route('student.portal.workouts.history.show', [$this->network, $log]));

        $workoutExercise->refresh();

        $this->assertSame(12, $workoutExercise->reps);
        $this->assertSame('30,500', $workoutExercise->suggested_load);
    }

    /** @test */
    public function test_student_can_save_progress_and_resume_the_workout(): void
    {
        $plan = $this->createPlan();
        $session = $this->createSession($plan);
        $workoutExercise = $this->createWorkoutExercise($session);

        $this->login();

        $this->post(route('student.portal.workouts.start', [$this->network, $session]))
            ->assertRedirect();

        $log = WorkoutLog::firstOrFail();

        $this->postJson(route('student.portal.workouts.save-progress', [$this->network, $log]), [
            'notes' => 'Pausa para o almoço.',
            'sets' => [
                [
                    'workout_exercise_id' => $workoutExercise->id,
                    'exercise_id' => $this->exercise->id,
                    'exercise_name' => $this->exercise->name,
                    'muscle_group' => $this->exercise->muscle_group,
                    'set_number' => 1,
                    'reps' => 12,
                    'weight_kg' => '30,500',
                    'rest_seconds' => 60,
                    'touched' => 1,
                ],
            ],
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertSame(1, $log->sets()->count());
        $this->assertSame(12, $log->sets()->first()->reps);
        $this->assertSame('Pausa para o almoço.', $log->fresh()->notes);

        $this->get(route('student.portal.workouts.log', [$this->network, $log]))
            ->assertOk()
            ->assertSee('value="12"', false)
            ->assertSee('value="30,5"', false);
    }

    /** @test */
    public function test_student_can_replace_an_exercise_before_starting_it(): void
    {
        $plan = $this->createPlan();
        $session = $this->createSession($plan);
        $workoutExercise = $this->createWorkoutExercise($session);

        $replacement = Exercise::create([
            'network_id' => $this->network->id,
            'name' => 'Remada de teste',
            'muscle_group' => 'back',
            'equipment' => 'cable',
            'status' => 'active',
        ]);

        $this->login();

        $this->post(route('student.portal.workouts.start', [$this->network, $session]))
            ->assertRedirect();

        $log = WorkoutLog::firstOrFail();

        $this->post(route('student.portal.workouts.exercises.replace', [$this->network, $log, $workoutExercise]), [
            'exercise_id' => $replacement->id,
        ])->assertRedirect(route('student.portal.workouts.log', [$this->network, $log]));

        $workoutExercise->refresh();

        $this->assertSame($replacement->id, $workoutExercise->exercise_id);
        $this->assertSame('Remada de teste', $workoutExercise->exercise_name);
        $this->assertSame('back', $workoutExercise->muscle_group);
    }

    /** @test */
    public function test_dashboard_frequency_uses_eligible_days_and_ignores_future_dates(): void
    {
        $plan = $this->createPlan();
        $session = $this->createSession($plan);

        $this->student->activeSubscriptions()->update([
            'weekly_accesses' => 5,
            'starts_at' => now()->subDays(35),
        ]);

        for ($day = 0; $day < 15; $day++) {
            $this->student->workoutLogs()->create([
                'network_id' => $this->network->id,
                'workout_plan_id' => $plan->id,
                'workout_session_id' => $session->id,
                'session_name' => 'Treino '.$day,
                'status' => WorkoutLog::STATUS_COMPLETED,
                'started_at' => now()->subDays($day),
                'completed_at' => now()->subDays($day)->addHour(),
                'duration_seconds' => 3600,
            ]);
        }

        $this->student->workoutLogs()->create([
            'network_id' => $this->network->id,
            'workout_plan_id' => $plan->id,
            'workout_session_id' => $session->id,
            'session_name' => 'Treino futuro',
            'status' => WorkoutLog::STATUS_COMPLETED,
            'started_at' => now()->addDays(2),
            'completed_at' => now()->addDays(2)->addHour(),
            'duration_seconds' => 3600,
        ]);

        $this->login();

        $this->get(route('student.portal.dashboard', $this->network))
            ->assertOk()
            ->assertViewHas('actualDays', 15)
            ->assertViewHas('expectedDays', 20)
            ->assertViewHas('frequencyPercent', 75);
    }

    /** @test */
    public function test_only_completed_sets_are_recorded_when_finishing_a_workout(): void
    {
        $plan = $this->createPlan();
        $session = $this->createSession($plan);
        $workoutExercise = $this->createWorkoutExercise($session);

        $this->login();

        $this->post(route('student.portal.workouts.start', [$this->network, $session]))
            ->assertRedirect();

        $log = WorkoutLog::firstOrFail();

        $this->post(route('student.portal.workouts.complete', [$this->network, $log]), [
            'sets' => [
                [
                    'workout_exercise_id' => $workoutExercise->id,
                    'exercise_id' => $this->exercise->id,
                    'exercise_name' => $this->exercise->name,
                    'muscle_group' => $this->exercise->muscle_group,
                    'set_number' => 1,
                    'reps' => 10,
                    'weight_kg' => 20,
                    'rest_seconds' => 60,
                    'completed' => 1,
                ],
                [
                    'workout_exercise_id' => $workoutExercise->id,
                    'exercise_id' => $this->exercise->id,
                    'exercise_name' => $this->exercise->name,
                    'muscle_group' => $this->exercise->muscle_group,
                    'set_number' => 2,
                    'reps' => 8,
                    'weight_kg' => 20,
                    'rest_seconds' => 60,
                    'completed' => 0,
                ],
            ],
        ])->assertRedirect();

        $this->assertSame(1, $log->sets()->count());
        $this->assertSame(10, $log->sets()->first()->reps);
        $this->assertTrue($log->sets()->first()->completed);
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
            ->assertSee('Concluir série')
            ->assertSee('value="10"', false)
            ->assertSee('value="20 kg"', false);

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
                    'weight_kg' => '22,500',
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
            ->assertSee('Histórico')
            ->assertSee('Treino A');

        $this->get(route('student.portal.workouts.history.show', [$this->network, $log]))
            ->assertOk()
            ->assertSee($this->exercise->name)
            ->assertSee('465');

        $this->get(route('student.portal.workouts.session', [$this->network, $session]))
            ->assertOk()
            ->assertSee('Última carga: 22,5 kg')
            ->assertSee('Máxima: 22,5 kg');

        $this->get(route('student.portal.workouts.evolution', $this->network))
            ->assertOk()
            ->assertSee('Histórico')
            ->assertSee('Exercícios por grupo muscular');
    }

    /** @test */
    public function test_student_can_complete_a_cardio_exercise_with_time_and_distance(): void
    {
        $cardio = Exercise::create([
            'network_id' => $this->network->id,
            'name' => 'Esteira de teste',
            'muscle_group' => 'cardio',
            'equipment' => 'cardio',
            'status' => 'active',
        ]);

        $plan = $this->createPlan();
        $session = $this->createSession($plan);

        $workoutExercise = $session->exercises()->create([
            'exercise_id' => $cardio->id,
            'exercise_name' => $cardio->name,
            'muscle_group' => $cardio->muscle_group,
            'order' => 1,
            'sets' => 1,
            'reps' => 1,
            'rest_seconds' => 0,
            'suggested_load' => '10:00',
        ]);

        $this->login();

        $this->post(route('student.portal.workouts.start', [$this->network, $session]))
            ->assertRedirect();

        $log = WorkoutLog::firstOrFail();

        $this->get(route('student.portal.workouts.log', [$this->network, $log]))
            ->assertOk()
            ->assertSee('Tempo de cardio')
            ->assertSee('Distância percorrida (km)')
            ->assertSee('Iniciar');

        $this->post(route('student.portal.workouts.complete', [$this->network, $log]), [
            'sets' => [
                [
                    'workout_exercise_id' => $workoutExercise->id,
                    'exercise_id' => $cardio->id,
                    'exercise_name' => $cardio->name,
                    'muscle_group' => $cardio->muscle_group,
                    'set_number' => 1,
                    'reps' => '',
                    'weight_kg' => '',
                    'cardio_seconds' => 750,
                    'distance_km' => '2,500',
                    'rest_seconds' => 0,
                ],
            ],
        ])->assertRedirect(route('student.portal.workouts.history.show', [$this->network, $log]));

        $log->refresh();

        $this->assertSame(WorkoutLog::STATUS_COMPLETED, $log->status);

        $set = $log->sets()->firstOrFail();

        $this->assertSame(750, $set->cardio_seconds);
        $this->assertEqualsWithDelta(2.5, (float) $set->distance_km, 0.001);
        $this->assertNull($set->weight_kg);

        $this->get(route('student.portal.workouts.history.show', [$this->network, $log]))
            ->assertOk()
            ->assertSee('12 min 30 s')
            ->assertSee('2,500 km');

        $this->get(route('student.portal.workouts.history', $this->network))
            ->assertOk()
            ->assertSee('Resumo por grupo muscular')
            ->assertSee('100%')
            ->assertSee('Cardio');
    }

    /** @test */
    public function test_copying_or_applying_a_plan_increments_its_usage_counter(): void
    {
        $plan = $this->createPlan();
        $plan->update([
            'student_id' => null,
            'visibility' => WorkoutPlan::VISIBILITY_PUBLIC,
        ]);

        $session = $this->createSession($plan);
        $this->createWorkoutExercise($session);

        $this->login();

        $this->post(route('student.portal.workouts.plans.copy', [$this->network, $plan]))
            ->assertRedirect();

        $this->assertSame(1, $plan->fresh()->copies_count);
        $this->assertSame('1 uso', $plan->fresh()->copies_label);

        $this->actingAs($this->networkAdmin)
            ->post(route('students.workouts.apply', $this->student), [
                'workout_plan_id' => $plan->id,
            ])
            ->assertRedirect();

        $this->assertSame(2, $plan->fresh()->copies_count);
    }

    /** @test */
    public function test_history_can_be_filtered_by_month_and_year(): void
    {
        $plan = $this->createPlan();
        $session = $this->createSession($plan);

        $old = $this->student->workoutLogs()->create([
            'network_id' => $this->network->id,
            'workout_plan_id' => $plan->id,
            'workout_session_id' => $session->id,
            'session_name' => 'Treino Antigo',
            'status' => WorkoutLog::STATUS_COMPLETED,
            'started_at' => now()->subMonths(2)->startOfMonth(),
            'completed_at' => now()->subMonths(2)->startOfMonth()->addHour(),
            'duration_seconds' => 3600,
        ]);

        $this->student->workoutLogs()->create([
            'network_id' => $this->network->id,
            'workout_plan_id' => $plan->id,
            'workout_session_id' => $session->id,
            'session_name' => 'Treino Recente',
            'status' => WorkoutLog::STATUS_COMPLETED,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
            'duration_seconds' => 1800,
        ]);

        $this->login();

        $this->get(route('student.portal.workouts.history', [
            $this->network,
            'month' => $old->started_at->month,
            'year' => $old->started_at->year,
        ]))
            ->assertOk()
            ->assertSee('Treino Antigo')
            ->assertDontSee('Treino Recente')
            ->assertSee((string) $old->started_at->year);
    }

    /** @test */
    public function test_only_the_creator_can_delete_a_workout_plan(): void
    {
        $own = $this->createPlan();

        $this->login();

        $this->delete(route('student.portal.workouts.plans.destroy', [$this->network, $own]))
            ->assertRedirect(route('student.portal.workouts.index', $this->network));

        $this->assertSoftDeleted('workout_plans', ['id' => $own->id]);

        $applied = WorkoutPlan::create([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $this->student->id,
            'name' => 'Treino aplicado pela equipe',
            'visibility' => WorkoutPlan::VISIBILITY_PRIVATE,
            'status' => WorkoutPlan::STATUS_ACTIVE,
            'created_by' => $this->networkAdmin->id,
        ]);

        $this->delete(route('student.portal.workouts.plans.destroy', [$this->network, $applied]))
            ->assertForbidden();

        $studentCreated = $this->createPlan();

        $this->actingAs($this->networkAdmin)
            ->deleteJson(route('admin.workout-plans.destroy', $studentCreated))
            ->assertForbidden();
    }

    /** @test */
    public function test_student_can_quick_add_an_exercise_from_the_picker(): void
    {
        $plan = $this->createPlan();
        $session = $this->createSession($plan);

        $this->login();

        $this->postJson(route('student.portal.workouts.plans.exercises.store', [$this->network, $session]), [
            'exercise_id' => $this->exercise->id,
            'sets' => 3,
            'reps' => 10,
            'rest_seconds' => 60,
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('workout_exercises', [
            'workout_session_id' => $session->id,
            'exercise_id' => $this->exercise->id,
            'sets' => 3,
            'reps' => 10,
        ]);
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
