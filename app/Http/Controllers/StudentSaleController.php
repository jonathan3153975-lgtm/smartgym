<?php

namespace App\Http\Controllers;

use App\Models\ClassBooking;
use App\Models\ClassSchedule;
use App\Models\MembershipPlan;
use App\Models\Student;
use App\Models\StudentCharge;
use App\Models\StudentSale;
use App\Models\StudentSaleItem;
use App\Models\StudentSalePayment;
use App\Models\Unit;
use App\Support\Payments\StudentChargeService;
use App\Support\Sales\StudentSaleService;
use App\Support\Settings\DayPassPrice;
use App\Support\TenantContext;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StudentSaleController extends Controller
{
    public function __construct(
        private readonly StudentSaleService $sales,
        private readonly DayPassPrice $dayPassPrice,
        private readonly StudentChargeService $charges,
    ) {}

    public function create(Student $student): View
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessStudent($student), 404);

        $student->load(['units.unit', 'subscriptions']);

        $accessibleUnits = $tenant->unitsQuery()->orderBy('name')->get(['id', 'name', 'network_id', 'region_id']);
        $accessibleUnitIds = $accessibleUnits->pluck('id')->map(fn ($id) => (int) $id);

        abort_if($accessibleUnits->isEmpty(), 404, 'Nenhuma unidade disponível.');

        $primaryUnitId = $student->units->firstWhere('is_primary')?->unit_id
            ?? $student->units->first()?->unit_id;
        $defaultUnitId = $accessibleUnitIds->contains((int) $primaryUnitId)
            ? (int) $primaryUnitId
            : (int) $accessibleUnits->first()->id;

        $plans = $accessibleUnits
            ->flatMap(fn (Unit $unit) => MembershipPlan::query()
                ->eligibleForUnit($unit)
                ->with(['units:id,name', 'modalities:id,name'])
                ->get())
            ->unique('id')
            ->sortBy('price')
            ->values();

        $classes = ClassSchedule::query()
            ->whereHas('schoolClass', fn ($query) => $query
                ->where('network_id', $student->network_id)
                ->whereIn('unit_id', $accessibleUnitIds)
                ->where('status', 'active'))
            ->with(['schoolClass.unit:id,name', 'schoolClass.modality:id,name', 'person:id,name', 'teacher:id,name'])
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();

        $classesData = $classes
            ->map(function (ClassSchedule $schedule): array {
                $class = $schedule->schoolClass;
                $nextDate = $this->nextDateForWeekday($schedule->weekday);
                $occupied = ClassBooking::query()
                    ->where('class_schedule_id', $schedule->id)
                    ->whereDate('booked_for', $nextDate->toDateString())
                    ->whereIn('status', [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED])
                    ->count();

                $available = max(0, (int) $schedule->vacancies - $occupied);

                return [
                    'id' => $schedule->id,
                    'class_id' => $class?->id,
                    'name' => $class?->name ?? 'Aula',
                    'unit_id' => (int) ($class?->unit_id ?? 0),
                    'unit_name' => $class?->unit?->name ?? 'Unidade',
                    'modality_id' => $class?->modality_id !== null ? (int) $class->modality_id : null,
                    'modality_name' => $class?->modality?->name,
                    'weekday' => (int) $schedule->weekday,
                    'weekday_label' => $schedule->weekday_label,
                    'start_time' => $this->timeLabel($schedule->start_time),
                    'end_time' => $schedule->end_time ? $this->timeLabel($schedule->end_time) : null,
                    'time_label' => $schedule->time_label,
                    'vacancies' => (int) $schedule->vacancies,
                    'available' => $available,
                    'drop_in_price' => $class?->drop_in_price !== null ? (float) $class->drop_in_price : null,
                    'next_date' => $nextDate->toDateString(),
                    'teacher_name' => $schedule->person?->name ?? $schedule->teacher?->name,
                ];
            })
            ->filter(fn (array $class) => $class['available'] > 0)
            ->values();

        $dayPassByUnit = $accessibleUnits->mapWithKeys(fn (Unit $unit) => [
            (int) $unit->id => $this->dayPassPrice->resolve($unit)['price'],
        ]);

        $plansData = $plans->map(function (MembershipPlan $plan) use ($accessibleUnitIds): array {
            $duration = max(1, (int) ($plan->duration_days ?: MembershipPlan::durationForPeriod($plan->period)));

            $planUnitIds = $plan->all_units
                ? $accessibleUnitIds
                : $plan->units->pluck('id')->map(fn ($id) => (int) $id)->intersect($accessibleUnitIds)->values();

            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'type' => $plan->type,
                'type_label' => $plan->type_label,
                'price' => (float) $plan->price,
                'period' => $plan->period,
                'period_label' => $plan->period_label,
                'duration_days' => $duration,
                'weekly_accesses' => (int) $plan->weekly_accesses,
                'daily_checkins' => (int) $plan->daily_checkins,
                'checkin_advance_minutes' => (int) ($plan->checkin_advance_minutes ?? MembershipPlan::DEFAULT_CHECKIN_ADVANCE),
                'includes_classes' => $plan->includesClasses(),
                'modality_ids' => $plan->modalities->pluck('id')->map(fn ($id) => (int) $id)->values(),
                'unit_ids' => $planUnitIds,
            ];
        });

        $unitsData = $accessibleUnits
            ->map(fn (Unit $unit) => [
                'id' => (int) $unit->id,
                'name' => $unit->name,
            ])
            ->values();

        return view('pages.students.sales.create', [
            'student' => $student,
            'unit' => $accessibleUnits->firstWhere('id', $defaultUnitId),
            'classesData' => $classesData,
            'plansData' => $plansData,
            'unitsData' => $unitsData,
            'defaultUnitId' => $defaultUnitId,
            'dayPassByUnit' => $dayPassByUnit,
        ]);
    }

    public function store(Request $request, Student $student): RedirectResponse|JsonResponse
    {
        $tenant = $this->tenant();
        abort_unless($tenant->canAccessStudent($student), 404);

        $unitId = $validated['unit_id'] ?? $this->primaryUnit($student)?->id;
        $accessUnit = $unitId
            ? $tenant->unitsQuery()->whereKey($unitId)->first()
            : null;

        if (! $accessUnit) {
            $message = 'Selecione uma unidade disponível para o seu nível de acesso.';

            return $request->wantsJson()
                ? response()->json(['message' => $message, 'errors' => ['unit_id' => [$message]]], 422)
                : back()->withErrors(['unit_id' => $message]);
        }

        $unit = $accessUnit;
        $launchedAt = ! empty($validated['launched_at'])
            ? Carbon::parse($validated['launched_at'])->startOfDay()
            : now()->startOfDay();

        $validated = $request->validate([
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'launched_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.type' => ['required', Rule::in([
                StudentSaleItem::TYPE_PLAN,
                StudentSaleItem::TYPE_DAY_PASS,
                StudentSaleItem::TYPE_CLASS,
                StudentSaleItem::TYPE_OTHER,
            ])],
            'items.*.plan_id' => ['nullable', 'integer'],
            'items.*.class_id' => ['nullable', 'integer'],
            'items.*.class_schedule_id' => ['nullable', 'integer'],
            'items.*.description' => ['nullable', 'string', 'max:255'],
            'items.*.amount' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'items.*.launched_at' => ['nullable', 'date'],
            'items.*.starts_at' => ['nullable', 'date'],
            'items.*.ends_at' => ['nullable', 'date'],
            'items.*.booked_for' => ['nullable', 'date'],
            'items.*.weekly_accesses' => ['nullable', 'integer', 'between:1,21'],
            'items.*.daily_checkins' => ['nullable', 'integer', 'between:1,10'],
            'items.*.checkin_advance_minutes' => ['nullable', 'integer', 'between:0,120'],
            'items.*.flexible_credit' => ['nullable', 'boolean'],
            'items.*.auto_renew' => ['nullable', 'boolean'],
            'items.*.slot_ids' => ['nullable', 'array'],
            'items.*.slot_ids.*' => ['integer', 'exists:classes,id'],
        ]);

        $dailyPlanIds = MembershipPlan::query()
            ->whereIn('id', collect($validated['items'])->pluck('plan_id')->filter()->map(fn ($id) => (int) $id))
            ->where('period', 'daily')
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        if ($dailyPlanIds->isNotEmpty()) {
            $invalidItem = collect($validated['items'])->contains(function (array $item) use ($dailyPlanIds): bool {
                if ($item['type'] === StudentSaleItem::TYPE_OTHER) {
                    return false;
                }

                return ! ($item['type'] === StudentSaleItem::TYPE_PLAN
                    && $dailyPlanIds->contains((int) ($item['plan_id'] ?? 0)));
            });

            if ($invalidItem) {
                throw ValidationException::withMessages([
                    'items' => 'Uma venda com diária só pode incluir produtos ou serviços além da própria diária.',
                ]);
            }
        }

        $sale = $this->sales->create($student, $unit, $validated['items'], auth()->id(), $launchedAt);
        $message = 'Venda '.$sale->code.' registrada. As cobranças ficaram pendentes no contrato do aluno.';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'sale_id' => $sale->id,
                'redirect' => route('students.sales.checkout', [$student, $sale]),
            ]);
        }

        return redirect()
            ->route('students.sales.checkout', [$student, $sale])
            ->with('success', $message);
    }

    public function checkout(Student $student, StudentSale $sale): View
    {
        $this->ensureSale($student, $sale);

        $sale->load(['items.plan', 'items.schoolClass', 'items.schedule', 'payments']);

        return view('pages.students.sales.checkout', [
            'student' => $student,
            'sale' => $sale,
            'methods' => StudentSalePayment::METHODS,
        ]);
    }

    public function destroyItem(Student $student, StudentSale $sale, StudentSaleItem $item): RedirectResponse
    {
        $this->ensureSale($student, $sale);

        abort_unless((int) $item->student_sale_id === (int) $sale->id, 404);

        if (in_array($sale->status, [StudentSale::STATUS_PAID, StudentSale::STATUS_CANCELED], true)) {
            return back()->withErrors(['sale' => 'Itens de uma venda paga ou cancelada não podem ser excluídos.']);
        }

        $charge = StudentCharge::query()
            ->where('student_sale_item_id', $item->id)
            ->with(['subscription', 'classBooking'])
            ->first();

        if ($charge && $charge->status === StudentCharge::STATUS_PAID) {
            return back()->withErrors(['sale' => 'Este item já foi pago e não pode ser excluído.']);
        }

        $saleDeleted = DB::transaction(function () use ($sale, $item, $charge) {
            if ($charge) {
                if ($charge->reference_type === 'class_booking' && $charge->classBooking) {
                    $charge->classBooking->delete();
                }

                if ($charge->reference_type === 'subscription' && $charge->subscription) {
                    $charge->subscription->delete();
                }

                $charge->delete();
            }

            $item->delete();

            $sale->update([
                'total' => round((float) $sale->items()->sum('amount'), 2),
                'updated_by' => auth()->id(),
            ]);

            if ($sale->items()->count() === 0) {
                $sale->delete();

                return true;
            }

            $sale->refreshStatus();

            return false;
        });

        $message = 'Item excluído da venda.';

        return $saleDeleted
            ? redirect()->route('students.sales.create', $student)->with('success', $message)
            : redirect()->route('students.sales.checkout', [$student, $sale])->with('success', $message);
    }

    public function finalize(Request $request, Student $student, StudentSale $sale): RedirectResponse|JsonResponse
    {
        $this->ensureSale($student, $sale);

        if ($sale->status === StudentSale::STATUS_PAID) {
            $message = 'Esta venda já foi finalizada.';

            return $request->wantsJson()
                ? response()->json(['message' => $message], 422)
                : back()->withErrors(['sale' => $message]);
        }

        $validated = $request->validate([
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', Rule::in(array_keys(StudentSalePayment::METHODS))],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.installments' => ['nullable', 'integer', 'min:1', 'max:24'],
        ]);

        $paymentsTotal = round(collect($validated['payments'])->sum(fn ($payment) => (float) $payment['amount']), 2);

        if (abs($paymentsTotal - (float) $sale->total) > 0.01) {
            throw ValidationException::withMessages([
                'payments' => 'A soma das formas de pagamento deve ser igual ao total da venda.',
            ]);
        }

        $paymentMethods = collect($validated['payments'])->pluck('method')->unique()->values();
        $chargeMethod = $paymentMethods->count() === 1 ? $paymentMethods->first() : 'mixed';

        DB::transaction(function () use ($validated, $sale, $chargeMethod) {
            foreach ($validated['payments'] as $payment) {
                $sale->payments()->create([
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'installments' => $payment['method'] === StudentSalePayment::METHOD_CREDIT
                        ? (int) ($payment['installments'] ?? 1)
                        : 1,
                    'status' => 'confirmed',
                    'created_by' => auth()->id(),
                ]);
            }

            foreach ($sale->charges()->where('status', StudentCharge::STATUS_PENDING)->get() as $charge) {
                $this->charges->markPaid($charge, $chargeMethod, auth()->id());
            }

            $sale->refreshStatus();
        });

        $message = 'Venda '.$sale->code.' finalizada com sucesso.';
        $redirect = route('students.contracts', $student);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => $redirect,
            ]);
        }

        return redirect()->to($redirect)->with('success', $message);
    }

    private function ensureSale(Student $student, StudentSale $sale): void
    {
        abort_unless($this->tenant()->canAccessStudent($student), 404);
        abort_unless(
            (int) $sale->student_id === (int) $student->id
            && (int) $sale->network_id === (int) $student->network_id,
            404
        );
    }

    private function primaryUnit(Student $student): ?Unit
    {
        $studentUnit = $student->units()->where('is_primary', true)->first()
            ?? $student->units()->first();

        return $studentUnit?->unit;
    }

    private function nextDateForWeekday(int $weekday): Carbon
    {
        $date = now()->startOfDay();

        while ($date->dayOfWeek !== $weekday) {
            $date->addDay();
        }

        return $date;
    }

    private function timeLabel(mixed $time): string
    {
        if ($time instanceof \DateTimeInterface) {
            return $time->format('H:i');
        }

        return substr((string) $time, 0, 5);
    }

    private function tenant(): TenantContext
    {
        $tenant = TenantContext::current();

        abort_if($tenant->isPlatform(), 403, 'Selecione uma rede para gerenciar vendas.');
        abort_unless($tenant->networkId(), 403, 'Selecione uma rede para gerenciar vendas.');

        return $tenant;
    }
}
