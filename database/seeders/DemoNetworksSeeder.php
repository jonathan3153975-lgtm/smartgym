<?php

namespace Database\Seeders;

use App\Models\BodyMeasurement;
use App\Models\ClassBooking;
use App\Models\ClassSchedule;
use App\Models\DayPassPriceHistory;
use App\Models\Exercise;
use App\Models\MembershipPlan;
use App\Models\Modality;
use App\Models\ModalityCategory;
use App\Models\Network;
use App\Models\Person;
use App\Models\Region;
use App\Models\SaasPlan;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentAssessment;
use App\Models\StudentCharge;
use App\Models\StudentCheckin;
use App\Models\StudentSale;
use App\Models\StudentSaleItem;
use App\Models\StudentSubscription;
use App\Models\StudentUnit;
use App\Models\SubscriptionPayment;
use App\Models\Unit;
use App\Models\User;
use App\Models\WorkoutExercise;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoNetworksSeeder extends Seeder
{
    private User $owner;

    private string $studentPasswordHash;

    private int $personSequence = 0;

    private int $studentSequence = 0;

    public function run(): void
    {
        $this->owner = User::where('email', 'jonathan3153975@gmail.com')->firstOrFail();
        $this->studentPasswordHash = Hash::make('SenhaAluno123');

        $this->cleanDatabase();

        $enterprise = SaasPlan::where('slug', 'enterprise')->firstOrFail();

        $usina = $this->createNetwork(
            'Usina do Corpo',
            'usina-do-corpo',
            '12.345.678/0001-90',
            $enterprise->id,
            'Porto Alegre',
            'RS',
            $this->usinaUnits()
        );

        $engenharia = $this->createNetwork(
            'Engenharia do Corpo',
            'engenharia-do-corpo',
            '98.765.432/0001-10',
            $enterprise->id,
            'Caxias do Sul',
            'RS',
            $this->engenhariaUnits()
        );

        $this->seedNetwork($usina);
        $this->seedNetwork($engenharia);

        $this->command?->info('Duas redes demo criadas com 5 unidades e 100 alunos por unidade.');
    }

    private function cleanDatabase(): void
    {
        $ownerPersonId = $this->owner->person_id;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'audit_logs',
            'body_measurements',
            'class_bookings',
            'class_schedules',
            'classes',
            'day_pass_price_histories',
            'exercises',
            'membership_plan_modalities',
            'membership_plan_units',
            'membership_plans',
            'modality_units',
            'modalities',
            'modality_categories',
            'payment_gateway_settings',
            'regions',
            'saas_subscriptions',
            'student_accounts',
            'student_addresses',
            'student_assessments',
            'student_charges',
            'student_checkins',
            'student_contacts',
            'student_notifications',
            'student_observations',
            'student_sale_item_slots',
            'student_sale_items',
            'student_sale_payments',
            'student_sales',
            'student_subscriptions',
            'student_units',
            'students',
            'subscription_payments',
            'units',
            'workout_exercises',
            'workout_log_sets',
            'workout_logs',
            'workout_plans',
            'workout_sessions',
            'networks',
            'sessions',
            'password_reset_tokens',
            'failed_jobs',
            'job_batches',
            'jobs',
            'cache',
            'cache_locks',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        DB::table('user_vinculos')->where('user_id', '!=', $this->owner->id)->delete();
        DB::table('users')->where('id', '!=', $this->owner->id)->delete();
        DB::table('people')->where('id', '!=', $ownerPersonId ?? 0)->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $platformRole = DB::table('roles')->where('name', 'platform_admin')->first();

        DB::table('user_vinculos')->where('user_id', $this->owner->id)->update([
            'network_id' => null,
            'region_id' => null,
            'unit_id' => null,
            'role_id' => $platformRole?->id,
            'status' => 'active',
            'updated_at' => now(),
        ]);
    }

    private function createNetwork(
        string $name,
        string $slug,
        string $document,
        int $planId,
        string $city,
        string $state,
        array $units
    ): Network {
        $network = Network::create([
            'name' => $name,
            'slug' => $slug,
            'document' => $document,
            'city' => $city,
            'state' => $state,
            'status' => 'active',
            'plan_id' => $planId,
            'settings' => [
                'company_name' => $name,
                'theme_mode' => 'dark',
                'day_pass_price' => 35,
            ],
        ]);

        DB::table('saas_subscriptions')->insert([
            'network_id' => $network->id,
            'plan_id' => $planId,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addYear(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $region = Region::create([
            'network_id' => $network->id,
            'name' => $city.' e região',
            'description' => 'Região de demonstração da rede '.$name,
            'status' => 'active',
            'settings' => ['day_pass_price' => 38],
        ]);

        foreach ($units as $index => $unitData) {
            $unit = Unit::create([
                'network_id' => $network->id,
                'region_id' => $region->id,
                'name' => $unitData['name'],
                'identifier' => strtoupper(substr($slug, 0, 3)).str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'address' => $unitData['address'],
                'zip_code' => $unitData['zip'],
                'street' => $unitData['street'],
                'number' => $unitData['number'],
                'neighborhood' => $unitData['neighborhood'],
                'city' => $unitData['city'],
                'state' => $unitData['state'],
                'phone' => $unitData['phone'],
                'email' => strtolower($slug).($index + 1).'@demo.com.br',
                'latitude' => $unitData['lat'],
                'longitude' => $unitData['lng'],
                'status' => 'active',
                'settings' => [
                    'day_pass_price' => 40 + ($index * 5),
                ],
            ]);

            DayPassPriceHistory::create([
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'price' => 40 + ($index * 5),
                'changed_by' => $this->owner->id,
            ]);

            $unit->setRelation('region', $region);
        }

        return $network;
    }

    private function seedNetwork(Network $network): void
    {
        $region = $network->regions()->firstOrFail();
        $units = $network->units()->orderBy('id')->get();

        $category = ModalityCategory::create([
            'network_id' => $network->id,
            'name' => 'Coletivas',
        ]);

        $modalities = collect(['Funcional', 'Yoga', 'Spinning', 'Boxe', 'Pilates'])
            ->map(fn (string $name) => Modality::create([
                'network_id' => $network->id,
                'created_level' => 'network',
                'modality_category_id' => $category->id,
                'name' => $name,
                'created_by' => $this->owner->id,
                'updated_by' => $this->owner->id,
            ]));

        foreach ($modalities as $modality) {
            $modality->units()->sync($units->pluck('id')->all());
        }

        $plans = $this->createPlans($network, $modalities);

        $people = [];
        $schedulesByUnit = [];

        foreach ($units as $unitIndex => $unit) {
            $people[$unit->id] = $this->createUnitPeople($network, $unit, $unitIndex);
            $schedulesByUnit[$unit->id] = $this->createUnitClasses($network, $unit, $modalities, $people[$unit->id]);
        }

        foreach ($units as $unitIndex => $unit) {
            $students = $this->createStudents($network, $unit, $unitIndex, $plans, $people[$unit->id]);

            $this->createBookingsAndCheckins($network, $unit, $students, $schedulesByUnit[$unit->id]);
            $this->createPendingCharges($network, $unit, $students);
            $this->createDemoPurchases($network, $unit, $students);
            $this->createDemoSales($network, $unit, $students, $plans);
        }

        $this->createWorkoutCatalog($network, $units, $people);
    }

    /**
     * @param  Collection<int, Modality>  $modalities
     * @return array<string, MembershipPlan>
     */
    private function createPlans(Network $network, $modalities): array
    {
        $basic = MembershipPlan::create([
            'network_id' => $network->id,
            'created_level' => 'network',
            'name' => 'Essencial',
            'description' => 'Acesso à musculação e cardio em todas as unidades da rede.',
            'type' => MembershipPlan::TYPE_BASIC,
            'price' => 99.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'weekly_accesses' => 5,
            'daily_checkins' => 1,
            'checkin_advance_minutes' => 20,
            'all_units' => true,
            'all_modalities' => false,
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        $classes = MembershipPlan::create([
            'network_id' => $network->id,
            'created_level' => 'network',
            'name' => 'Aulas Coletivas',
            'description' => 'Aulas de funcional, yoga, spinning, boxe e pilates.',
            'type' => MembershipPlan::TYPE_CLASSES,
            'price' => 149.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'weekly_accesses' => 5,
            'daily_checkins' => $modalities->count(),
            'checkin_advance_minutes' => 20,
            'all_units' => true,
            'all_modalities' => false,
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);
        $classes->modalities()->sync($modalities->pluck('id')->all());

        $mixed = MembershipPlan::create([
            'network_id' => $network->id,
            'created_level' => 'network',
            'name' => 'Completo',
            'description' => 'Academia e aulas coletivas em todas as unidades.',
            'type' => MembershipPlan::TYPE_MIXED,
            'price' => 199.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'weekly_accesses' => 6,
            'daily_checkins' => 2,
            'checkin_advance_minutes' => 20,
            'all_units' => true,
            'all_modalities' => false,
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);
        $mixed->modalities()->sync($modalities->pluck('id')->all());

        $dayPass = MembershipPlan::create([
            'network_id' => $network->id,
            'created_level' => 'network',
            'name' => 'Diária',
            'description' => 'Acesso por um dia.',
            'type' => MembershipPlan::TYPE_BASIC,
            'price' => 40,
            'period' => 'daily',
            'duration_days' => 1,
            'weekly_accesses' => 1,
            'daily_checkins' => 1,
            'checkin_advance_minutes' => 20,
            'all_units' => true,
            'all_modalities' => false,
            'created_by' => $this->owner->id,
            'updated_by' => $this->owner->id,
        ]);

        return compact('basic', 'classes', 'mixed', 'dayPass');
    }

    /**
     * @return array<int, Person>
     */
    private function createUnitPeople(Network $network, Unit $unit, int $unitIndex): array
    {
        $names = [
            'Professor '.$unit->name,
            'Personal '.$unit->name,
            'Coordenação '.$unit->name,
        ];

        $roles = [
            Person::FUNCTION_INSTRUCTOR,
            Person::FUNCTION_PERSONAL_TRAINER,
            Person::FUNCTION_ADMINISTRATION,
        ];

        $people = [];

        foreach ($names as $index => $name) {
            $this->personSequence++;

            $people[] = Person::create([
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'person_type' => 'individual',
                'category' => 'staff',
                'job_function' => $roles[$index],
                'name' => $name,
                'document' => '000.000.'.str_pad((string) $this->personSequence, 3, '0', STR_PAD_LEFT).'-00',
                'document_raw' => str_pad((string) (70000000000 + $this->personSequence), 11, '0', STR_PAD_LEFT),
                'email' => 'equipe'.$this->personSequence.'@demo.com.br',
                'phone' => '(51) 99999-'.str_pad((string) $this->personSequence, 4, '0', STR_PAD_LEFT),
                'status' => 'active',
                'created_by' => $this->owner->id,
                'updated_by' => $this->owner->id,
            ]);
        }

        return $people;
    }

    /**
     * @param  Collection<int, Modality>  $modalities
     * @param  array<int, Person>  $people
     * @return array<int, ClassSchedule>
     */
    private function createUnitClasses(Network $network, Unit $unit, $modalities, array $people): array
    {
        $classes = [
            ['Funcional', 1, '07:00', '08:00'],
            ['Yoga', 2, '08:00', '09:00'],
            ['Spinning', 3, '18:00', '19:00'],
            ['Boxe', 4, '19:00', '20:00'],
        ];

        $schedules = [];
        $teacher = $people[0];

        foreach ($classes as $index => [$name, $weekday, $start, $end]) {
            $modality = $modalities->firstWhere('name', $name);

            $class = SchoolClass::create([
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'modality_id' => $modality?->id,
                'name' => $name.' - '.$unit->name,
                'drop_in_price' => 45 + ($index * 10),
                'status' => 'active',
                'created_by' => $this->owner->id,
                'updated_by' => $this->owner->id,
            ]);

            foreach ([$weekday, (($weekday + 2) % 7)] as $day) {
                $schedules[] = ClassSchedule::create([
                    'class_id' => $class->id,
                    'person_id' => $teacher->id,
                    'weekday' => $day,
                    'start_time' => $start,
                    'end_time' => $end,
                    'vacancies' => 15,
                ]);
            }
        }

        return $schedules;
    }

    /**
     * @param  array<string, MembershipPlan>  $plans
     * @param  array<int, Person>  $people
     * @return array<int, Student>
     */
    private function createStudents(Network $network, Unit $unit, int $unitIndex, array $plans, array $people): array
    {
        $students = [];
        $planCycle = [$plans['basic'], $plans['classes'], $plans['mixed']];

        for ($i = 1; $i <= 100; $i++) {
            $this->studentSequence++;
            $sequence = $this->studentSequence;
            $personName = $this->studentName($sequence);

            $student = Student::create([
                'network_id' => $network->id,
                'name' => $personName,
                'cpf' => str_pad((string) (90000000000 + $sequence), 11, '0', STR_PAD_LEFT),
                'cpf_raw' => str_pad((string) (90000000000 + $sequence), 11, '0', STR_PAD_LEFT),
                'birth_date' => now()->subYears(18 + ($sequence % 35))->subDays($sequence % 300),
                'gender' => ['male', 'female', 'other', 'prefer_not_to_say'][$sequence % 4],
                'email' => 'aluno'.$sequence.'@'.$network->slug.'.demo',
                'phone' => '(51) 98888-'.str_pad((string) ($sequence % 10000), 4, '0', STR_PAD_LEFT),
                'whatsapp' => '(51) 98888-'.str_pad((string) ($sequence % 10000), 4, '0', STR_PAD_LEFT),
                'city' => $unit->city,
                'state' => $unit->state,
                'status' => $sequence % 10 === 9 ? 'inactive' : 'active',
                'source' => 'demo',
                'created_by' => $this->owner->id,
                'updated_by' => $this->owner->id,
            ]);

            // Spread registrations over the last months so growth charts have history.
            $registeredAt = now()
                ->subDays(($sequence * 13) % 210)
                ->setTime(9 + ($sequence % 10), ($sequence * 7) % 60);

            $student->forceFill(['created_at' => $registeredAt])->saveQuietly();

            StudentUnit::create([
                'student_id' => $student->id,
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'status' => 'active',
                'is_primary' => true,
                'starts_at' => $registeredAt->toDateString(),
                'created_by' => $this->owner->id,
                'updated_by' => $this->owner->id,
            ]);

            StudentAccount::create([
                'network_id' => $network->id,
                'student_id' => $student->id,
                'email' => $student->email,
                'password' => $this->studentPasswordHash,
                'email_verified_at' => now(),
                'must_change_password' => false,
                'status' => 'active',
            ]);

            $bucket = $sequence % 10;

            if ($bucket < 7) {
                $plan = $planCycle[$sequence % count($planCycle)];
                $this->createSubscription($network, $unit, $student, $plan, StudentSubscription::STATUS_ACTIVE);
            } elseif ($bucket < 9) {
                $plan = $planCycle[$sequence % count($planCycle)];
                $this->createSubscription($network, $unit, $student, $plan, StudentSubscription::STATUS_PENDING);
            }

            if ($sequence % 3 === 0) {
                $this->createAssessment($network, $student, $sequence);
            }

            if ($sequence % 5 === 0) {
                $this->createMeasurements($network, $student, $sequence);
            }

            $students[] = $student;
        }

        return $students;
    }

    private function createSubscription(
        Network $network,
        Unit $unit,
        Student $student,
        MembershipPlan $plan,
        string $status
    ): StudentSubscription {
        $isActive = $status === StudentSubscription::STATUS_ACTIVE;

        $subscription = StudentSubscription::create([
            'network_id' => $network->id,
            'unit_id' => $unit->id,
            'student_id' => $student->id,
            'membership_plan_id' => $plan->id,
            'status' => $status,
            'gateway_provider' => 'manual',
            'plan_name' => $plan->name,
            'price' => $plan->price,
            'period' => $plan->period,
            'duration_days' => $plan->duration_days,
            'plan_type' => $plan->type,
            'modality_ids' => $plan->modalities()->pluck('modalities.id')->map(fn ($id) => (int) $id)->all(),
            'weekly_accesses' => $plan->weekly_accesses,
            'daily_checkins' => $plan->daily_checkins,
            'checkin_advance_minutes' => $plan->checkin_advance_minutes,
            'starts_at' => null,
            'ends_at' => null,
            'paid_at' => null,
        ]);

        $seed = (int) $subscription->id;

        if ($isActive) {
            $paidAt = now()
                ->subDays(5 + (($seed * 7) % 100))
                ->setTime(9 + ($seed % 9), ($seed * 3) % 60);

            $subscription->update([
                'starts_at' => $paidAt->copy()->subDays(2),
                'ends_at' => now()->addDays(5 + ($seed % 40)),
                'paid_at' => $paidAt,
            ]);

            SubscriptionPayment::create([
                'student_subscription_id' => $subscription->id,
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'provider' => 'manual',
                'status' => SubscriptionPayment::STATUS_APPROVED,
                'amount' => $plan->price,
                'external_reference' => 'demo:'.$subscription->id,
                'paid_at' => $paidAt,
            ]);

            StudentCharge::create([
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'student_id' => $student->id,
                'type' => StudentCharge::TYPE_PLAN,
                'description' => 'Plano '.$plan->name,
                'amount' => $plan->price,
                'status' => StudentCharge::STATUS_PAID,
                'payment_method' => $this->paymentMethodFor($seed),
                'reference_type' => 'subscription',
                'reference_id' => $subscription->id,
                'due_date' => $paidAt->toDateString(),
                'paid_at' => $paidAt,
                'metadata' => [
                    'plan_type' => $plan->type,
                    'period' => $plan->period,
                ],
                'created_by' => $this->owner->id,
                'updated_by' => $this->owner->id,
            ]);
        } else {
            StudentCharge::create([
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'student_id' => $student->id,
                'type' => StudentCharge::TYPE_PLAN,
                'description' => 'Plano '.$plan->name,
                'amount' => $plan->price,
                'status' => StudentCharge::STATUS_PENDING,
                'reference_type' => 'subscription',
                'reference_id' => $subscription->id,
                'due_date' => now()->addDays($seed % 6 === 0 ? 0 : 3 + ($seed % 18))->toDateString(),
                'metadata' => [
                    'plan_type' => $plan->type,
                    'period' => $plan->period,
                ],
                'created_by' => $this->owner->id,
                'updated_by' => $this->owner->id,
            ]);
        }

        return $subscription;
    }

    private function paymentMethodFor(int $seed): string
    {
        return ['pix', 'credit_card', 'cash', 'debit_card', 'transfer', 'pix', 'credit_card'][$seed % 7];
    }

    private function createAssessment(Network $network, Student $student, int $sequence): void
    {
        StudentAssessment::create([
            'network_id' => $network->id,
            'student_id' => $student->id,
            'status' => StudentAssessment::STATUS_COMPLETED,
            'goal' => ['weight_loss', 'muscle_gain', 'conditioning', 'health'][$sequence % 4],
            'experience_level' => ['beginner', 'intermediate', 'advanced'][$sequence % 3],
            'training_frequency' => 3 + ($sequence % 3),
            'parq_answers' => [],
            'parq_positive' => false,
            'weight_kg' => 60 + ($sequence % 40),
            'height_cm' => 160 + ($sequence % 25),
            'body_fat_percent' => 15 + ($sequence % 20),
            'consent' => true,
            'submitted_at' => now()->subDays($sequence % 20),
        ]);
    }

    private function createMeasurements(Network $network, Student $student, int $sequence): void
    {
        for ($month = 0; $month < 3; $month++) {
            BodyMeasurement::create([
                'network_id' => $network->id,
                'student_id' => $student->id,
                'measured_at' => now()->subMonths($month)->toDateString(),
                'weight_kg' => 70 + ($sequence % 20) - $month,
                'body_fat_percent' => 22 - $month,
                'waist_cm' => 85 - $month,
                'hip_cm' => 100,
                'chest_cm' => 95,
                'arm_cm' => 32,
                'thigh_cm' => 55,
                'calf_cm' => 38,
            ]);
        }
    }

    /**
     * @param  array<int, Student>  $students
     * @param  array<int, ClassSchedule>  $schedules
     */
    private function createBookingsAndCheckins(Network $network, Unit $unit, array $students, array $schedules): void
    {
        if (empty($schedules)) {
            return;
        }

        foreach (array_slice($students, 0, 20) as $index => $student) {
            $schedule = $schedules[$index % count($schedules)];
            $date = $this->nextDateForWeekday($schedule->weekday);
            $start = Carbon::parse($date->toDateString().' '.$schedule->start_time);

            ClassBooking::create([
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'student_id' => $student->id,
                'class_id' => $schedule->class_id,
                'class_schedule_id' => $schedule->id,
                'status' => ClassBooking::STATUS_CONFIRMED,
                'type' => ClassBooking::TYPE_PLAN,
                'booked_for' => $date->toDateString(),
                'early_minutes' => 20,
                'checkin_opens_at' => $start->copy()->subMinutes(20),
                'checkin_closes_at' => $start->copy()->addMinutes(30),
                'amount' => 0,
                'paid_at' => now(),
            ]);
        }

        foreach ($students as $index => $student) {
            if ($index % 3 !== 0) {
                continue;
            }

            StudentCheckin::create([
                'network_id' => $network->id,
                'student_id' => $student->id,
                'unit_id' => $unit->id,
                'type' => $index % 6 === 0 ? StudentCheckin::TYPE_TRIAL : 'access',
                'checked_in_at' => now()->subDays(4 + (($index * 5) % 80))->setTime(7 + ($index % 12), 0),
                'notes' => 'Check-in de demonstração',
            ]);
        }
    }

    /**
     * @param  array<int, Student>  $students
     */
    private function createPendingCharges(Network $network, Unit $unit, array $students): void
    {
        $charges = [
            ['type' => StudentCharge::TYPE_OTHER, 'description' => 'Avaliação física e acompanhamento', 'amount' => 120, 'due' => 8],
            ['type' => StudentCharge::TYPE_DAY_PASS, 'description' => 'Diária avulsa', 'amount' => 45, 'due' => 0],
            ['type' => StudentCharge::TYPE_CLASS, 'description' => 'Aula avulsa - Boxe', 'amount' => 55, 'due' => 6],
            ['type' => StudentCharge::TYPE_OTHER, 'description' => 'Uniforme e acessórios', 'amount' => 90, 'due' => -4],
            ['type' => StudentCharge::TYPE_PLAN, 'description' => 'Plano Essencial', 'amount' => 99.90, 'due' => -9, 'metadata' => ['plan_type' => MembershipPlan::TYPE_BASIC]],
        ];

        foreach ($charges as $index => $charge) {
            $student = $students[12 + $index] ?? null;

            if (! $student) {
                continue;
            }

            StudentCharge::create([
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'student_id' => $student->id,
                'type' => $charge['type'],
                'description' => $charge['description'],
                'amount' => $charge['amount'],
                'status' => StudentCharge::STATUS_PENDING,
                'due_date' => now()->addDays($charge['due'])->toDateString(),
                'metadata' => $charge['metadata'] ?? null,
                'created_by' => $this->owner->id,
                'updated_by' => $this->owner->id,
            ]);
        }
    }

    /**
     * @param  array<int, Student>  $students
     */
    private function createDemoPurchases(Network $network, Unit $unit, array $students): void
    {
        foreach ($students as $index => $student) {
            if ($index % 9 === 0) {
                $paidAt = now()->subDays(($index * 11) % 90)->setTime(8 + ($index % 10), 0);

                StudentCharge::create([
                    'network_id' => $network->id,
                    'unit_id' => $unit->id,
                    'student_id' => $student->id,
                    'type' => StudentCharge::TYPE_DAY_PASS,
                    'description' => 'Diária de '.$paidAt->format('d/m/Y'),
                    'amount' => 45 + (($index % 4) * 5),
                    'status' => StudentCharge::STATUS_PAID,
                    'payment_method' => $this->paymentMethodFor($index + 11),
                    'due_date' => $paidAt->toDateString(),
                    'paid_at' => $paidAt,
                    'metadata' => ['valid_date' => $paidAt->toDateString(), 'checkins' => 1],
                    'created_by' => $this->owner->id,
                    'updated_by' => $this->owner->id,
                ]);
            }

            if ($index % 14 === 0) {
                $paidAt = now()->subDays(($index * 9) % 83)->setTime(17 + ($index % 4), 0);

                StudentCharge::create([
                    'network_id' => $network->id,
                    'unit_id' => $unit->id,
                    'student_id' => $student->id,
                    'type' => StudentCharge::TYPE_CLASS,
                    'description' => 'Aula avulsa - Boxe',
                    'amount' => 50 + (($index % 3) * 5),
                    'status' => StudentCharge::STATUS_PAID,
                    'payment_method' => $this->paymentMethodFor($index + 5),
                    'due_date' => $paidAt->toDateString(),
                    'paid_at' => $paidAt,
                    'metadata' => ['weekday' => 2, 'start_time' => '18:00'],
                    'created_by' => $this->owner->id,
                    'updated_by' => $this->owner->id,
                ]);
            }

            if ($index % 17 === 0) {
                StudentCharge::create([
                    'network_id' => $network->id,
                    'unit_id' => $unit->id,
                    'student_id' => $student->id,
                    'type' => StudentCharge::TYPE_DAY_PASS,
                    'description' => 'Diária avulsa',
                    'amount' => 45,
                    'status' => StudentCharge::STATUS_PENDING,
                    'due_date' => now()->addDays($index % 12)->toDateString(),
                    'created_by' => $this->owner->id,
                    'updated_by' => $this->owner->id,
                ]);
            }

            if ($index % 23 === 0) {
                StudentCharge::create([
                    'network_id' => $network->id,
                    'unit_id' => $unit->id,
                    'student_id' => $student->id,
                    'type' => StudentCharge::TYPE_CLASS,
                    'description' => 'Aula avulsa - Funcional',
                    'amount' => 55,
                    'status' => StudentCharge::STATUS_PENDING,
                    'due_date' => now()->subDays(1 + ($index % 15))->toDateString(),
                    'created_by' => $this->owner->id,
                    'updated_by' => $this->owner->id,
                ]);
            }
        }
    }

    /**
     * @param  array<int, Student>  $students
     * @param  array<string, MembershipPlan>  $plans
     */
    private function createDemoSales(Network $network, Unit $unit, array $students, array $plans): void
    {
        $methodSets = [
            ['cash', 'credit_card'],
            ['pix'],
            ['debit_card', 'cash'],
            ['credit_card'],
            ['pix', 'credit_card'],
        ];

        foreach (array_slice($students, 50, 5) as $index => $student) {
            $launchedAt = now()->subDays(6 + ($index * 7))->startOfDay();
            $methods = $methodSets[$index % count($methodSets)];

            $sale = StudentSale::create([
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'student_id' => $student->id,
                'status' => StudentSale::STATUS_PENDING,
                'launched_at' => $launchedAt->toDateString(),
                'total' => 0,
                'created_by' => $this->owner->id,
                'updated_by' => $this->owner->id,
            ]);

            $sale->update(['code' => 'VND-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT)]);

            $charges = [];
            $dayPassAmount = 45 + ($index * 3);

            $dayPassItem = StudentSaleItem::create([
                'student_sale_id' => $sale->id,
                'type' => StudentSaleItem::TYPE_DAY_PASS,
                'unit_id' => $unit->id,
                'description' => 'Diária de '.$launchedAt->format('d/m/Y'),
                'amount' => $dayPassAmount,
                'launched_at' => $launchedAt->toDateString(),
                'starts_at' => $launchedAt->toDateString(),
                'ends_at' => $launchedAt->toDateString(),
                'weekly_accesses' => 7,
                'daily_checkins' => 1,
                'status' => StudentCharge::STATUS_PAID,
            ]);

            $charges[] = StudentCharge::create([
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'student_id' => $student->id,
                'student_sale_id' => $sale->id,
                'student_sale_item_id' => $dayPassItem->id,
                'type' => StudentCharge::TYPE_DAY_PASS,
                'description' => 'Diária de '.$launchedAt->format('d/m/Y'),
                'amount' => $dayPassAmount,
                'status' => StudentCharge::STATUS_PAID,
                'payment_method' => count($methods) === 1 ? $methods[0] : 'mixed',
                'due_date' => $launchedAt->toDateString(),
                'paid_at' => $launchedAt->copy()->setTime(11, 0),
                'metadata' => ['valid_date' => $launchedAt->toDateString(), 'checkins' => 1],
                'created_by' => $this->owner->id,
                'updated_by' => $this->owner->id,
            ]);

            if ($index % 2 === 0) {
                $plan = $plans['basic'];

                $planItem = StudentSaleItem::create([
                    'student_sale_id' => $sale->id,
                    'type' => StudentSaleItem::TYPE_PLAN,
                    'membership_plan_id' => $plan->id,
                    'unit_id' => $unit->id,
                    'description' => 'Plano '.$plan->name,
                    'amount' => $plan->price,
                    'launched_at' => $launchedAt->toDateString(),
                    'starts_at' => $launchedAt->toDateString(),
                    'ends_at' => $launchedAt->copy()->addDays(30)->toDateString(),
                    'weekly_accesses' => $plan->weekly_accesses,
                    'daily_checkins' => $plan->daily_checkins,
                    'checkin_advance_minutes' => $plan->checkin_advance_minutes,
                    'status' => StudentCharge::STATUS_PAID,
                    'metadata' => ['plan_type' => $plan->type, 'period' => $plan->period],
                ]);

                $charges[] = StudentCharge::create([
                    'network_id' => $network->id,
                    'unit_id' => $unit->id,
                    'student_id' => $student->id,
                    'student_sale_id' => $sale->id,
                    'student_sale_item_id' => $planItem->id,
                    'type' => StudentCharge::TYPE_PLAN,
                    'description' => 'Plano '.$plan->name,
                    'amount' => $plan->price,
                    'status' => StudentCharge::STATUS_PAID,
                    'payment_method' => count($methods) === 1 ? $methods[0] : 'mixed',
                    'due_date' => $launchedAt->toDateString(),
                    'paid_at' => $launchedAt->copy()->setTime(11, 5),
                    'metadata' => ['plan_type' => $plan->type, 'period' => $plan->period],
                    'created_by' => $this->owner->id,
                    'updated_by' => $this->owner->id,
                ]);
            }

            $total = round(collect($charges)->sum('amount'), 2);
            $sale->update(['total' => $total, 'status' => StudentSale::STATUS_PAID]);

            $remaining = $total;

            foreach ($methods as $methodIndex => $method) {
                $isLast = $methodIndex === count($methods) - 1;
                $part = $isLast ? $remaining : round($total / count($methods), 2);

                $sale->payments()->create([
                    'method' => $method,
                    'amount' => $part,
                    'installments' => $method === 'credit_card' ? 3 : 1,
                    'status' => 'confirmed',
                    'created_by' => $this->owner->id,
                ]);

                $remaining = round($remaining - $part, 2);
            }
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Collection<int, Unit>  $units
     * @param  array<int, array<int, Person>>  $people
     */
    private function createWorkoutCatalog(Network $network, $units, array $people): void
    {
        $exercises = ExerciseSeeder::seedForNetwork($network, $this->owner->id);

        foreach ($units as $unit) {
            $plan = WorkoutPlan::create([
                'network_id' => $network->id,
                'unit_id' => $unit->id,
                'name' => 'Treino Full Body - '.$unit->name,
                'objective' => 'Condicionamento geral',
                'status' => WorkoutPlan::STATUS_ACTIVE,
                'visibility' => WorkoutPlan::VISIBILITY_PUBLIC,
                'created_by' => $this->owner->id,
                'updated_by' => $this->owner->id,
            ]);

            foreach (['Treino A', 'Treino B', 'Treino C'] as $sessionIndex => $sessionName) {
                $session = WorkoutSession::create([
                    'workout_plan_id' => $plan->id,
                    'name' => $sessionName,
                    'day_label' => ['Segunda', 'Quarta', 'Sexta'][$sessionIndex],
                    'order' => $sessionIndex + 1,
                ]);

                $exercises->slice($sessionIndex * 4, 4)->values()->each(function (Exercise $exercise, $order) use ($session) {
                    WorkoutExercise::create([
                        'workout_session_id' => $session->id,
                        'exercise_id' => $exercise->id,
                        'exercise_name' => $exercise->name,
                        'muscle_group' => $exercise->muscle_group,
                        'order' => $order + 1,
                        'sets' => 3,
                        'reps' => '12',
                        'rest_seconds' => 60,
                        'suggested_load' => 'Carga moderada',
                    ]);
                });
            }
        }
    }

    private function studentName(int $sequence): string
    {
        $first = ['Ana', 'Bruno', 'Carla', 'Daniel', 'Eduarda', 'Felipe', 'Gabriela', 'Henrique', 'Isabela', 'João', 'Karina', 'Lucas', 'Mariana', 'Nicolas', 'Olívia', 'Pedro', 'Rafaela', 'Samuel', 'Tatiane', 'Vinícius'];
        $last = ['Silva', 'Souza', 'Oliveira', 'Santos', 'Pereira', 'Costa', 'Rodrigues', 'Almeida', 'Nascimento', 'Lima', 'Araújo', 'Fernandes', 'Carvalho', 'Gomes', 'Martins', 'Rocha', 'Ribeiro', 'Alves', 'Monteiro', 'Barbosa'];

        return $first[$sequence % count($first)].' '.$last[intdiv($sequence, count($first)) % count($last)];
    }

    private function nextDateForWeekday(int $weekday): Carbon
    {
        $date = now()->startOfDay();

        while ($date->dayOfWeek !== $weekday) {
            $date->addDay();
        }

        return $date;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function usinaUnits(): array
    {
        return [
            [
                'name' => 'Usina do Corpo - Andradas',
                'address' => 'R. dos Andradas, 1258 - Centro Histórico',
                'zip' => '90020-008',
                'street' => 'R. dos Andradas',
                'number' => '1258',
                'neighborhood' => 'Centro Histórico',
                'city' => 'Porto Alegre',
                'state' => 'RS',
                'phone' => '(51) 3221-1000',
                'lat' => -30.0277,
                'lng' => -51.2287,
            ],
            [
                'name' => 'Usina do Corpo - Caxias Centro',
                'address' => 'Av. Júlio de Castilhos, 1648 - Nossa Senhora de Lourdes',
                'zip' => '95010-001',
                'street' => 'Av. Júlio de Castilhos',
                'number' => '1648',
                'neighborhood' => 'Nossa Senhora de Lourdes',
                'city' => 'Caxias do Sul',
                'state' => 'RS',
                'phone' => '(54) 3221-2000',
                'lat' => -29.1678,
                'lng' => -51.1794,
            ],
            [
                'name' => 'Usina do Corpo - Gravataí Centro',
                'address' => 'Rua Adolfo Inácio Barcelos, 774 - Centro',
                'zip' => '94010-000',
                'street' => 'Rua Adolfo Inácio Barcelos',
                'number' => '774',
                'neighborhood' => 'Centro',
                'city' => 'Gravataí',
                'state' => 'RS',
                'phone' => '(51) 3488-3000',
                'lat' => -29.9413,
                'lng' => -50.9928,
            ],
            [
                'name' => 'Usina do Corpo - Novo Hamburgo Centro',
                'address' => 'R. Joaquim Nabuco, 831 - Centro',
                'zip' => '93510-000',
                'street' => 'R. Joaquim Nabuco',
                'number' => '831',
                'neighborhood' => 'Centro',
                'city' => 'Novo Hamburgo',
                'state' => 'RS',
                'phone' => '(51) 3594-4000',
                'lat' => -29.6783,
                'lng' => -51.1306,
            ],
            [
                'name' => 'Usina do Corpo - São Leopoldo',
                'address' => 'Av. Theodomiro Porto da Fonseca, 73',
                'zip' => '93010-090',
                'street' => 'Av. Theodomiro Porto da Fonseca',
                'number' => '73',
                'neighborhood' => 'Rio Branco',
                'city' => 'São Leopoldo',
                'state' => 'RS',
                'phone' => '(51) 3590-5000',
                'lat' => -29.7603,
                'lng' => -51.1472,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function engenhariaUnits(): array
    {
        return [
            [
                'name' => 'Engenharia do Corpo - São Leopoldo',
                'address' => 'Avenida São Borja, 363 - Rio Branco',
                'zip' => '93032-000',
                'street' => 'Avenida São Borja',
                'number' => '363',
                'neighborhood' => 'Rio Branco',
                'city' => 'São Leopoldo',
                'state' => 'RS',
                'phone' => '(51) 98135-2408',
                'lat' => -29.77270,
                'lng' => -51.13272,
            ],
            [
                'name' => 'Engenharia do Corpo - Lourdes Caxias',
                'address' => 'Rua Sinimbu, 485 - Lourdes',
                'zip' => '95020-000',
                'street' => 'Rua Sinimbu',
                'number' => '485',
                'neighborhood' => 'Lourdes',
                'city' => 'Caxias do Sul',
                'state' => 'RS',
                'phone' => '(54) 3221-6000',
                'lat' => -29.1660,
                'lng' => -51.1800,
            ],
            [
                'name' => 'Engenharia do Corpo - Protásio',
                'address' => 'Avenida Protásio Alves, 2859 - Petrópolis',
                'zip' => '90410-000',
                'street' => 'Avenida Protásio Alves',
                'number' => '2859',
                'neighborhood' => 'Petrópolis',
                'city' => 'Porto Alegre',
                'state' => 'RS',
                'phone' => '(51) 3333-7000',
                'lat' => -30.0330,
                'lng' => -51.1900,
            ],
            [
                'name' => 'Engenharia do Corpo - CT Ingleses',
                'address' => 'Rodovia João Gualberto Soares, 809 - Ingleses',
                'zip' => '88058-000',
                'street' => 'Rodovia João Gualberto Soares',
                'number' => '809',
                'neighborhood' => 'Ingleses',
                'city' => 'Florianópolis',
                'state' => 'SC',
                'phone' => '(48) 98876-7758',
                'lat' => -27.4300,
                'lng' => -48.3900,
            ],
            [
                'name' => 'Engenharia do Corpo - Canoas Shopping',
                'address' => 'Avenida Guilherme Schell, 6750 - Centro',
                'zip' => '92010-000',
                'street' => 'Avenida Guilherme Schell',
                'number' => '6750',
                'neighborhood' => 'Centro',
                'city' => 'Canoas',
                'state' => 'RS',
                'phone' => '(51) 3472-8000',
                'lat' => -29.9170,
                'lng' => -51.1830,
            ],
        ];
    }
}
