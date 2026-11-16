<?php

namespace App\Support\Sales;

use App\Models\ClassBooking;
use App\Models\ClassSchedule;
use App\Models\MembershipPlan;
use App\Models\Student;
use App\Models\StudentSale;
use App\Models\StudentSaleItem;
use App\Models\StudentSaleItemSlot;
use App\Models\StudentSubscription;
use App\Models\Unit;
use App\Support\Payments\StudentChargeService;
use App\Support\Settings\DayPassPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentSaleService
{
    public function __construct(
        private readonly StudentChargeService $charges,
        private readonly DayPassPrice $dayPassPrice,
    ) {}

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function create(Student $student, Unit $unit, array $items, ?int $userId, ?Carbon $launchedAt = null): StudentSale
    {
        return DB::transaction(function () use ($student, $unit, $items, $userId, $launchedAt) {
            $saleLaunchedAt = $launchedAt?->copy()->startOfDay() ?? now()->startOfDay();

            $sale = StudentSale::create([
                'network_id' => $student->network_id,
                'unit_id' => $unit->id,
                'student_id' => $student->id,
                'status' => StudentSale::STATUS_PENDING,
                'launched_at' => $saleLaunchedAt->toDateString(),
                'total' => 0,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            $sale->update([
                'code' => 'VND-'.str_pad((string) $sale->id, 6, '0', STR_PAD_LEFT),
            ]);

            $total = 0.0;

            foreach (array_values($items) as $index => $data) {
                $item = $this->createItem($sale, $student, $unit, $data, $index, $userId, $saleLaunchedAt);
                $total += (float) $item->amount;
            }

            $sale->update(['total' => $total]);

            return $sale->fresh(['items']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createItem(
        StudentSale $sale,
        Student $student,
        Unit $unit,
        array $data,
        int $index,
        ?int $userId,
        Carbon $launchedAt
    ): StudentSaleItem {
        $type = (string) ($data['type'] ?? '');

        return match ($type) {
            StudentSaleItem::TYPE_PLAN => $this->createPlanItem($sale, $student, $unit, $data, $index, $userId, $launchedAt),
            StudentSaleItem::TYPE_DAY_PASS => $this->createDayPassItem($sale, $student, $unit, $data, $index, $userId, $launchedAt),
            StudentSaleItem::TYPE_CLASS => $this->createClassItem($sale, $student, $unit, $data, $index, $userId, $launchedAt),
            StudentSaleItem::TYPE_OTHER => $this->createOtherItem($sale, $student, $unit, $data, $index, $userId, $launchedAt),
            default => throw ValidationException::withMessages([
                "items.{$index}.type" => 'Selecione um tipo de venda válido.',
            ]),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createPlanItem(
        StudentSale $sale,
        Student $student,
        Unit $unit,
        array $data,
        int $index,
        ?int $userId,
        Carbon $launchedAt
    ): StudentSaleItem {
        $plan = MembershipPlan::query()
            ->eligibleForUnit($unit)
            ->whereKey($data['plan_id'] ?? 0)
            ->first();

        if (! $plan) {
            throw ValidationException::withMessages([
                "items.{$index}.plan_id" => 'Selecione um plano disponível para a unidade do aluno.',
            ]);
        }

        if ($plan->price === null || (float) $plan->price <= 0) {
            throw ValidationException::withMessages([
                "items.{$index}.plan_id" => 'Este plano não possui valor para venda.',
            ]);
        }

        $startsAt = $this->dateOrToday($data['starts_at'] ?? null);
        $duration = max(1, (int) ($plan->duration_days ?: MembershipPlan::durationForPeriod($plan->period)));
        $endsAt = $this->dateOrNull($data['ends_at'] ?? null) ?? $startsAt->copy()->addDays($duration);

        if ($endsAt->lt($startsAt)) {
            throw ValidationException::withMessages([
                "items.{$index}.ends_at" => 'A data final deve ser igual ou posterior à data inicial.',
            ]);
        }

        $weeklyAccesses = (int) ($data['weekly_accesses'] ?? $plan->weekly_accesses);
        $dailyCheckins = (int) ($data['daily_checkins'] ?? $plan->daily_checkins);
        $advance = (int) ($data['checkin_advance_minutes'] ?? $plan->checkin_advance_minutes ?? MembershipPlan::DEFAULT_CHECKIN_ADVANCE);

        if ($weeklyAccesses < 1 || $weeklyAccesses > 7) {
            throw ValidationException::withMessages([
                "items.{$index}.weekly_accesses" => 'Informe entre 1 e 7 dias de acesso por semana.',
            ]);
        }

        if ($dailyCheckins < 1 || $dailyCheckins > 10) {
            throw ValidationException::withMessages([
                "items.{$index}.daily_checkins" => 'Informe entre 1 e 10 check-ins por dia.',
            ]);
        }

        $price = (float) $plan->price;

        if ($plan->period === 'daily') {
            $submittedAmount = isset($data['amount']) ? (float) $data['amount'] : 0;
            $dayPass = $this->dayPassPrice->resolve($unit);
            $price = $submittedAmount > 0
                ? $submittedAmount
                : ($dayPass['price'] ?? $price);
        }

        $autoRenew = filter_var($data['auto_renew'] ?? false, FILTER_VALIDATE_BOOL);

        $item = StudentSaleItem::create([
            'student_sale_id' => $sale->id,
            'type' => StudentSaleItem::TYPE_PLAN,
            'membership_plan_id' => $plan->id,
            'unit_id' => $unit->id,
            'description' => 'Plano '.$plan->name,
            'amount' => $price,
            'launched_at' => $launchedAt->toDateString(),
            'starts_at' => $startsAt->toDateString(),
            'ends_at' => $endsAt->toDateString(),
            'weekly_accesses' => $weeklyAccesses,
            'daily_checkins' => $dailyCheckins,
            'checkin_advance_minutes' => $advance,
            'auto_renew' => $autoRenew,
            'status' => StudentSubscription::STATUS_PENDING,
            'metadata' => [
                'plan_type' => $plan->type,
                'period' => $plan->period,
                'duration_days' => $duration,
            ],
        ]);

        $this->syncSlots($item, $plan, $student, $data['slot_ids'] ?? [], $weeklyAccesses, $index, $unit);

        $charge = $this->charges->sellPlan($student, $plan, $unit, $userId, [
            'sale_id' => $sale->id,
            'sale_item_id' => $item->id,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'weekly_accesses' => $weeklyAccesses,
            'daily_checkins' => $dailyCheckins,
            'checkin_advance_minutes' => $advance,
            'auto_renew' => $autoRenew,
            'due_date' => $launchedAt->toDateString(),
            'price' => $price,
        ]);

        $item->update(['status' => $charge->status]);

        return $item->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createDayPassItem(
        StudentSale $sale,
        Student $student,
        Unit $unit,
        array $data,
        int $index,
        ?int $userId,
        Carbon $launchedAt
    ): StudentSaleItem {
        $validDate = $this->dateOrNull($data['starts_at'] ?? null)
            ?? $this->dateOrNull($data['valid_date'] ?? null);

        if (! $validDate) {
            throw ValidationException::withMessages([
                "items.{$index}.starts_at" => 'Informe o dia de validade da diária.',
            ]);
        }

        if ($validDate->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                "items.{$index}.starts_at" => 'O dia de validade não pode ser anterior a hoje.',
            ]);
        }

        $checkins = (int) ($data['daily_checkins'] ?? 1);
        $amount = (float) ($data['amount'] ?? 0);

        if ($checkins < 1 || $checkins > 10) {
            throw ValidationException::withMessages([
                "items.{$index}.daily_checkins" => 'Informe entre 1 e 10 check-ins para a diária.',
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                "items.{$index}.amount" => 'Informe o valor da diária.',
            ]);
        }

        $description = trim((string) ($data['description'] ?? '')) ?: 'Diária de '.$validDate->format('d/m/Y');

        $item = StudentSaleItem::create([
            'student_sale_id' => $sale->id,
            'type' => StudentSaleItem::TYPE_DAY_PASS,
            'unit_id' => $unit->id,
            'description' => $description,
            'amount' => $amount,
            'launched_at' => $launchedAt->toDateString(),
            'starts_at' => $validDate->toDateString(),
            'ends_at' => $validDate->toDateString(),
            'weekly_accesses' => 7,
            'daily_checkins' => $checkins,
            'status' => StudentSubscription::STATUS_PENDING,
        ]);

        $charge = $this->charges->sellDayPass(
            $student,
            $unit,
            $validDate,
            $checkins,
            $amount,
            $userId,
            $description,
            [
                'sale_id' => $sale->id,
                'sale_item_id' => $item->id,
                'due_date' => $launchedAt->toDateString(),
            ]
        );

        $item->update(['status' => $charge->status]);

        return $item->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createClassItem(
        StudentSale $sale,
        Student $student,
        Unit $unit,
        array $data,
        int $index,
        ?int $userId,
        Carbon $launchedAt
    ): StudentSaleItem {
        $schedule = $this->resolveSchedule($student, $data, $index, $unit);
        $class = $schedule->schoolClass;

        $bookedFor = $this->dateOrNull($data['booked_for'] ?? null)
            ?? $this->dateOrNull($data['starts_at'] ?? null)
            ?? $this->nextDateForWeekday($schedule->weekday);

        if ($bookedFor->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                "items.{$index}.booked_for" => 'A data da aula não pode ser anterior a hoje.',
            ]);
        }

        if ((int) $bookedFor->dayOfWeek !== (int) $schedule->weekday) {
            throw ValidationException::withMessages([
                "items.{$index}.booked_for" => 'A data escolhida não corresponde ao dia da turma.',
            ]);
        }

        $amount = (float) ($data['amount'] ?? $class?->drop_in_price ?? 0);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                "items.{$index}.amount" => 'Informe o valor da aula avulsa.',
            ]);
        }

        $advance = (int) ($data['checkin_advance_minutes']
            ?? $student->activeSubscriptionForModality($class?->modality_id)?->checkin_advance_minutes
            ?? MembershipPlan::DEFAULT_CHECKIN_ADVANCE);

        $flexibleCredit = filter_var($data['flexible_credit'] ?? false, FILTER_VALIDATE_BOOL);

        $item = StudentSaleItem::create([
            'student_sale_id' => $sale->id,
            'type' => StudentSaleItem::TYPE_CLASS,
            'class_id' => $class?->id,
            'class_schedule_id' => $schedule->id,
            'unit_id' => $class?->unit_id,
            'description' => 'Aula avulsa: '.($class?->name ?? 'Aula').' - '.$schedule->weekday_label.' '.$schedule->time_label.' - '.$bookedFor->format('d/m/Y'),
            'amount' => $amount,
            'launched_at' => $launchedAt->toDateString(),
            'starts_at' => $bookedFor->toDateString(),
            'ends_at' => $bookedFor->toDateString(),
            'daily_checkins' => 1,
            'checkin_advance_minutes' => $advance,
            'status' => ClassBooking::STATUS_PENDING,
            'metadata' => [
                'weekday' => $schedule->weekday,
                'start_time' => (string) $schedule->start_time,
                'flexible_credit' => $flexibleCredit,
            ],
        ]);

        $charge = $this->charges->sellClass(
            $student,
            $schedule,
            $bookedFor,
            $advance,
            $amount,
            $userId,
            [
                'sale_id' => $sale->id,
                'sale_item_id' => $item->id,
                'due_date' => $launchedAt->toDateString(),
                'flexible_credit' => $flexibleCredit,
            ]
        );

        $item->update(['status' => $charge->status]);

        return $item->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createOtherItem(
        StudentSale $sale,
        Student $student,
        Unit $unit,
        array $data,
        int $index,
        ?int $userId,
        Carbon $launchedAt
    ): StudentSaleItem {
        $description = trim((string) ($data['description'] ?? ''));
        $amount = (float) ($data['amount'] ?? 0);

        if ($description === '') {
            throw ValidationException::withMessages([
                "items.{$index}.description" => 'Informe a descrição do produto ou serviço.',
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                "items.{$index}.amount" => 'Informe o valor do produto ou serviço.',
            ]);
        }

        $item = StudentSaleItem::create([
            'student_sale_id' => $sale->id,
            'type' => StudentSaleItem::TYPE_OTHER,
            'unit_id' => $unit->id,
            'description' => $description,
            'amount' => $amount,
            'launched_at' => $launchedAt->toDateString(),
            'starts_at' => $this->dateOrNull($data['starts_at'] ?? null)?->toDateString(),
            'ends_at' => $this->dateOrNull($data['ends_at'] ?? null)?->toDateString(),
            'status' => StudentSubscription::STATUS_PENDING,
        ]);

        $charge = $this->charges->sellOther($student, $unit, $description, $amount, $userId, [
            'sale_id' => $sale->id,
            'sale_item_id' => $item->id,
            'due_date' => $launchedAt->toDateString(),
        ]);

        $item->update(['status' => $charge->status]);

        return $item->fresh();
    }

    /**
     * @param  array<int, mixed>  $slotIds
     */
    private function syncSlots(
        StudentSaleItem $item,
        MembershipPlan $plan,
        Student $student,
        array $slotIds,
        int $weeklyAccesses,
        int $index,
        Unit $unit
    ): void {
        if (! $plan->includesClasses() || $slotIds === []) {
            return;
        }

        $slotIds = collect($slotIds)->map(fn ($id) => (int) $id)->unique()->values();

        $allowedUnits = $plan->all_units
            ? collect([$unit->id])
            : $plan->units()->pluck('units.id')->intersect(collect([$unit->id]));

        $allowedModalities = $plan->modalities()->pluck('modalities.id')->map(fn ($id) => (int) $id);

        $schedules = ClassSchedule::query()
            ->whereIn('id', $slotIds)
            ->whereHas('schoolClass', fn ($query) => $query
                ->where('status', 'active')
                ->whereIn('unit_id', $allowedUnits))
            ->with('schoolClass')
            ->get();

        if ($schedules->count() !== $slotIds->count()) {
            throw ValidationException::withMessages([
                "items.{$index}.slot_ids" => 'Selecione apenas aulas disponíveis nas unidades do plano.',
            ]);
        }

        if ($allowedModalities->isNotEmpty()) {
            $outside = $schedules->filter(fn (ClassSchedule $schedule) => $schedule->schoolClass?->modality_id !== null
                && ! $allowedModalities->contains((int) $schedule->schoolClass->modality_id));

            if ($outside->isNotEmpty()) {
                throw ValidationException::withMessages([
                    "items.{$index}.slot_ids" => 'Selecione apenas aulas das modalidades incluídas no plano.',
                ]);
            }
        }

        if ($schedules->count() > $weeklyAccesses) {
            throw ValidationException::withMessages([
                "items.{$index}.slot_ids" => 'A quantidade de horários não pode ultrapassar os dias de acesso do plano.',
            ]);
        }

        foreach ($schedules as $schedule) {
            $date = $this->nextDateForWeekday($schedule->weekday);

            if (! $this->hasVacancy($schedule, $date)) {
                throw ValidationException::withMessages([
                    "items.{$index}.slot_ids" => 'O horário '.$schedule->weekday_label.' '.$schedule->time_label.' está sem vagas na próxima data.',
                ]);
            }

            StudentSaleItemSlot::create([
                'student_sale_item_id' => $item->id,
                'class_schedule_id' => $schedule->id,
                'weekday' => $schedule->weekday,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveSchedule(Student $student, array $data, int $index, Unit $unit): ClassSchedule
    {
        $query = ClassSchedule::query()
            ->whereHas('schoolClass', fn ($builder) => $builder
                ->where('network_id', $student->network_id)
                ->where('unit_id', $unit->id)
                ->where('status', 'active'))
            ->with('schoolClass');

        if (! empty($data['class_schedule_id'])) {
            $schedule = (clone $query)->whereKey($data['class_schedule_id'])->first();
        } elseif (! empty($data['class_id'])) {
            $schedule = (clone $query)->where('class_id', $data['class_id'])->orderBy('weekday')->orderBy('start_time')->first();
        } else {
            $schedule = null;
        }

        if (! $schedule) {
            throw ValidationException::withMessages([
                "items.{$index}.class_schedule_id" => 'Selecione uma turma e horário disponíveis para o aluno.',
            ]);
        }

        if (! $this->hasVacancy($schedule, $this->nextDateForWeekday($schedule->weekday))) {
            throw ValidationException::withMessages([
                "items.{$index}.class_schedule_id" => 'Este horário está sem vagas na próxima data.',
            ]);
        }

        return $schedule;
    }

    private function hasVacancy(ClassSchedule $schedule, Carbon $date): bool
    {
        $occupied = ClassBooking::query()
            ->where('class_schedule_id', $schedule->id)
            ->whereDate('booked_for', $date->toDateString())
            ->whereIn('status', [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED])
            ->count();

        return $occupied < max(1, (int) $schedule->vacancies);
    }

    private function nextDateForWeekday(int $weekday): Carbon
    {
        $date = now()->startOfDay();

        while ($date->dayOfWeek !== $weekday) {
            $date->addDay();
        }

        return $date;
    }

    private function dateOrToday(mixed $value): Carbon
    {
        return $this->dateOrNull($value) ?? now()->startOfDay();
    }

    private function dateOrNull(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
