<?php

namespace App\Support\Payments;

use App\Models\ClassBooking;
use App\Models\ClassSchedule;
use App\Models\MembershipPlan;
use App\Models\PaymentGatewaySetting;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCharge;
use App\Models\StudentSale;
use App\Models\StudentSaleItem;
use App\Models\StudentSubscription;
use App\Models\SubscriptionPayment;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentChargeService
{
    public function __construct(private readonly MercadoPagoCredentials $credentials) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function sellPlan(Student $student, MembershipPlan $plan, Unit $unit, ?int $userId, array $options = []): StudentCharge
    {
        $price = isset($options['price']) ? (float) $options['price'] : (float) $plan->price;

        if ($price <= 0) {
            throw ValidationException::withMessages([
                'plan_id' => 'Este plano não possui valor para venda.',
            ]);
        }

        return DB::transaction(function () use ($student, $plan, $unit, $userId, $options, $price) {
            $startsAt = $options['starts_at'] ?? null;
            $endsAt = $options['ends_at'] ?? null;

            $subscription = StudentSubscription::create([
                'network_id' => $student->network_id,
                'unit_id' => $unit->id,
                'student_id' => $student->id,
                'membership_plan_id' => $plan->id,
                'student_sale_item_id' => $options['sale_item_id'] ?? null,
                'status' => StudentSubscription::STATUS_PENDING,
                'gateway_provider' => 'manual',
                'plan_name' => $plan->name,
                'price' => $price,
                'period' => $plan->period,
                'duration_days' => $plan->duration_days ?: MembershipPlan::durationForPeriod($plan->period),
                'plan_type' => $plan->type,
                'modality_ids' => $plan->modalities()->pluck('modalities.id')->map(fn ($id) => (int) $id)->all(),
                'weekly_accesses' => (int) ($options['weekly_accesses'] ?? $plan->weekly_accesses),
                'daily_checkins' => (int) ($options['daily_checkins'] ?? $plan->daily_checkins),
                'checkin_advance_minutes' => (int) ($options['checkin_advance_minutes'] ?? $plan->checkin_advance_minutes ?? MembershipPlan::DEFAULT_CHECKIN_ADVANCE),
                'auto_renew' => (bool) ($options['auto_renew'] ?? false),
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
            ]);

            return $this->createCharge($student, $unit, StudentCharge::TYPE_PLAN, $options['description'] ?? 'Plano '.$plan->name, $price, $subscription, [
                'plan_id' => $plan->id,
                'plan_type' => $plan->type,
                'period' => $plan->period,
                'duration_days' => $subscription->duration_days,
            ], $userId, $options);
        });
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function sellDayPass(
        Student $student,
        Unit $unit,
        Carbon $validDate,
        int $checkins,
        float $amount,
        ?int $userId,
        ?string $description = null,
        array $options = []
    ): StudentCharge {
        return DB::transaction(function () use ($student, $unit, $validDate, $checkins, $amount, $userId, $description, $options) {
            $subscription = StudentSubscription::create([
                'network_id' => $student->network_id,
                'unit_id' => $unit->id,
                'student_id' => $student->id,
                'student_sale_item_id' => $options['sale_item_id'] ?? null,
                'status' => StudentSubscription::STATUS_PENDING,
                'gateway_provider' => 'manual',
                'plan_name' => 'Diária - '.$validDate->format('d/m/Y'),
                'price' => $amount,
                'period' => 'daily',
                'duration_days' => 1,
                'plan_type' => MembershipPlan::TYPE_BASIC,
                'weekly_accesses' => 7,
                'daily_checkins' => $checkins,
                'checkin_advance_minutes' => MembershipPlan::DEFAULT_CHECKIN_ADVANCE,
                'starts_at' => $validDate->copy()->startOfDay(),
                'ends_at' => $validDate->copy()->endOfDay(),
            ]);

            return $this->createCharge(
                $student,
                $unit,
                StudentCharge::TYPE_DAY_PASS,
                $description ?: 'Diária de '.$validDate->format('d/m/Y').' ('.$checkins.' check-in(s))',
                $amount,
                $subscription,
                ['valid_date' => $validDate->toDateString(), 'checkins' => $checkins],
                $userId,
                $options
            );
        });
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function sellClass(
        Student $student,
        ClassSchedule $schedule,
        Carbon $bookedFor,
        int $earlyMinutes,
        float $amount,
        ?int $userId,
        array $options = []
    ): StudentCharge {
        $class = $schedule->schoolClass;

        if (! $class instanceof SchoolClass) {
            throw ValidationException::withMessages([
                'class_id' => 'Selecione uma turma válida.',
            ]);
        }

        $start = Carbon::parse($bookedFor->toDateString().' '.$schedule->start_time);
        $opens = $start->copy()->subMinutes($earlyMinutes);
        $closes = $start->copy()->addMinutes(30);

        $occupied = ClassBooking::query()
            ->where('class_schedule_id', $schedule->id)
            ->whereDate('booked_for', $bookedFor->toDateString())
            ->whereIn('status', [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED])
            ->count();

        if ($occupied >= $schedule->vacancies) {
            throw ValidationException::withMessages([
                'class_id' => 'Este horário está sem vagas para a data selecionada.',
            ]);
        }

        $existing = ClassBooking::query()
            ->where('student_id', $student->id)
            ->where('class_schedule_id', $schedule->id)
            ->whereDate('booked_for', $bookedFor->toDateString())
            ->first();

        if ($existing && in_array($existing->status, [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED], true)) {
            throw ValidationException::withMessages([
                'class_id' => 'O aluno já possui uma reserva neste horário e data.',
            ]);
        }

        return DB::transaction(function () use ($student, $class, $schedule, $bookedFor, $earlyMinutes, $amount, $userId, $opens, $closes, $existing, $options) {
            $data = [
                'network_id' => $student->network_id,
                'unit_id' => $class->unit_id,
                'student_id' => $student->id,
                'class_id' => $class->id,
                'class_schedule_id' => $schedule->id,
                'payment_gateway_setting_id' => null,
                'status' => ClassBooking::STATUS_PENDING,
                'type' => ClassBooking::TYPE_SINGLE,
                'booked_for' => $bookedFor->toDateString(),
                'early_minutes' => $earlyMinutes,
                'checkin_opens_at' => $opens,
                'checkin_closes_at' => $closes,
                'amount' => $amount,
                'gateway_payload' => [
                    'flexible_credit' => (bool) ($options['flexible_credit'] ?? false),
                ],
            ];

            if ($existing) {
                $existing->update($data);
                $booking = $existing->fresh();
            } else {
                $booking = ClassBooking::create($data);
            }

            return $this->createCharge(
                $student,
                $class->unit,
                StudentCharge::TYPE_CLASS,
                $options['description'] ?? 'Aula avulsa: '.$class->name.' - '.$schedule->weekday_label.' '.$schedule->time_label.' - '.$bookedFor->format('d/m/Y'),
                $amount,
                $booking,
                [
                    'class_id' => $class->id,
                    'class_schedule_id' => $schedule->id,
                    'booked_for' => $bookedFor->toDateString(),
                    'early_minutes' => $earlyMinutes,
                ],
                $userId,
                $options
            );
        });
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function sellOther(Student $student, Unit $unit, string $description, float $amount, ?int $userId, array $options = []): StudentCharge
    {
        return $this->createCharge($student, $unit, StudentCharge::TYPE_OTHER, $description, $amount, null, [], $userId, $options);
    }

    public function markPaid(StudentCharge $charge, string $method = 'manual', ?int $userId = null): void
    {
        if ($charge->isPaid()) {
            return;
        }

        DB::transaction(function () use ($charge, $method, $userId) {
            $paidAt = now();

            if ($charge->reference_type === 'subscription' && $charge->subscription) {
                $subscription = $charge->subscription;
                $validDate = $charge->metadata['valid_date'] ?? null;
                $isRenewal = (bool) data_get($charge->metadata, 'renewal', false);
                $duration = max(1, (int) ($subscription->duration_days ?: 30));

                if ($validDate) {
                    $start = $subscription->starts_at ?? Carbon::parse($validDate)->startOfDay();
                    $end = $subscription->ends_at ?? Carbon::parse($validDate)->endOfDay();
                } elseif ($isRenewal) {
                    $start = $subscription->starts_at ?? now();
                    $base = $subscription->ends_at && $subscription->ends_at->isFuture()
                        ? $subscription->ends_at->copy()
                        : now();
                    $end = $base->copy()->addDays($duration);
                } else {
                    $start = $subscription->starts_at ?? now();
                    $end = $subscription->ends_at ?? now()->addDays($duration);
                }

                $subscription->update([
                    'status' => StudentSubscription::STATUS_ACTIVE,
                    'starts_at' => $start,
                    'ends_at' => $end,
                    'paid_at' => $paidAt,
                ]);

                $subscription->payments()->create([
                    'network_id' => $charge->network_id,
                    'unit_id' => $charge->unit_id,
                    'provider' => $method === 'pix' ? 'mercado_pago_pix' : 'manual',
                    'status' => SubscriptionPayment::STATUS_APPROVED,
                    'amount' => $charge->amount,
                    'external_reference' => 'charge:'.$charge->id,
                    'gateway_payment_id' => $charge->gateway_payment_id,
                    'paid_at' => $paidAt,
                    'gateway_payload' => ['charge_id' => $charge->id, 'confirmed_by' => $userId],
                ]);
            }

            if ($charge->reference_type === 'class_booking' && $charge->classBooking) {
                $charge->classBooking->update([
                    'status' => ClassBooking::STATUS_CONFIRMED,
                    'paid_at' => $paidAt,
                    'gateway_payment_id' => $charge->gateway_payment_id,
                ]);
            }

            $charge->update([
                'status' => StudentCharge::STATUS_PAID,
                'payment_method' => $method,
                'paid_at' => $paidAt,
                'updated_by' => $userId,
            ]);

            $this->syncSaleState($charge);
        });
    }

    public function cancel(StudentCharge $charge, ?int $userId = null): void
    {
        if (! $charge->isPending()) {
            return;
        }

        DB::transaction(function () use ($charge, $userId) {
            if ($charge->reference_type === 'subscription' && $charge->subscription) {
                $charge->subscription->update(['status' => StudentSubscription::STATUS_CANCELED]);
            }

            if ($charge->reference_type === 'class_booking' && $charge->classBooking) {
                $charge->classBooking->update(['status' => ClassBooking::STATUS_CANCELED]);
            }

            $charge->update([
                'status' => StudentCharge::STATUS_CANCELED,
                'canceled_at' => now(),
                'updated_by' => $userId,
            ]);

            $this->syncSaleState($charge);
        });
    }

    public function createPix(StudentCharge $charge): StudentCharge
    {
        if (! $charge->isPending()) {
            throw ValidationException::withMessages(['charge' => 'Esta cobrança não está pendente.']);
        }

        $setting = $this->credentials->resolve($charge->network, $charge->unit);

        if (! $setting) {
            throw ValidationException::withMessages([
                'charge' => 'Configure as credenciais do Mercado Pago para gerar o Pix.',
            ]);
        }

        $email = $charge->student?->email;

        if (! $email) {
            throw ValidationException::withMessages([
                'charge' => 'Informe o e-mail do aluno para gerar o Pix.',
            ]);
        }

        $payload = MercadoPagoClient::forSetting($setting)->createPayment([
            'transaction_amount' => round((float) $charge->amount, 2),
            'description' => $charge->description,
            'payment_method_id' => 'pix',
            'payer' => ['email' => $email],
            'external_reference' => self::externalReference($charge),
            'notification_url' => route('webhooks.mercado-pago', ['charge' => $charge->id]),
        ]);

        $qrCode = data_get($payload, 'point_of_interaction.transaction_data.qr_code');
        $qrCodeBase64 = data_get($payload, 'point_of_interaction.transaction_data.qr_code_base64');

        if (! $qrCode) {
            throw new MercadoPagoException('O Mercado Pago não retornou o código Pix.');
        }

        $charge->update([
            'payment_gateway_setting_id' => $setting->id,
            'gateway_payment_id' => isset($payload['id']) ? (string) $payload['id'] : null,
            'pix_qr_code' => $qrCode,
            'pix_qr_code_base64' => $qrCodeBase64,
            'pix_generated_at' => now(),
            'gateway_payload' => $payload,
        ]);

        if (($payload['status'] ?? null) === 'approved') {
            $this->markPaid($charge, 'pix');
        }

        return $charge->fresh();
    }

    /**
     * Create a Mercado Pago checkout link for a pending charge.
     *
     * @return array{url: string, preference_id: string|null}
     */
    public function createCheckoutLink(StudentCharge $charge, ?int $userId = null): array
    {
        if (! $charge->isPending()) {
            throw ValidationException::withMessages(['charge' => 'Esta cobrança não está pendente.']);
        }

        $charge->loadMissing(['network', 'unit', 'student']);

        $setting = $this->credentials->resolve($charge->network, $charge->unit);

        if (! $setting) {
            throw new MercadoPagoException('Configure as credenciais do Mercado Pago para gerar o link de pagamento.');
        }

        $email = $charge->student?->email;

        if (! $email) {
            throw new MercadoPagoException('Informe o e-mail do aluno para gerar o link de pagamento.');
        }

        $network = $charge->network;
        $redirectUrl = $network
            ? route('student.portal.finance.index', $network)
            : url('/');

        $payload = [
            'items' => [[
                'id' => 'charge-'.$charge->id,
                'title' => mb_substr($charge->description ?: 'Cobrança', 0, 120),
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => round((float) $charge->amount, 2),
            ]],
            'payer' => array_filter([
                'name' => $charge->student?->name,
                'email' => $email,
            ]),
            'external_reference' => self::externalReference($charge),
            'notification_url' => route('webhooks.mercado-pago', ['charge' => $charge->id]),
            'back_urls' => [
                'success' => $redirectUrl,
                'pending' => $redirectUrl,
                'failure' => $redirectUrl,
            ],
            'statement_descriptor' => mb_substr(config('app.name'), 0, 22),
            'metadata' => [
                'charge_id' => $charge->id,
                'network_id' => $charge->network_id,
                'unit_id' => $charge->unit_id,
                'student_id' => $charge->student_id,
            ],
        ];

        $preference = MercadoPagoClient::forSetting($setting)->createPreference($payload);

        $initPoint = $setting->environment === 'production'
            ? ($preference['init_point'] ?? $preference['sandbox_init_point'] ?? null)
            : ($preference['sandbox_init_point'] ?? $preference['init_point'] ?? null);

        if (! $initPoint) {
            throw new MercadoPagoException('O Mercado Pago não retornou o link de pagamento.');
        }

        $preferenceId = isset($preference['id']) ? (string) $preference['id'] : null;

        $charge->update([
            'payment_gateway_setting_id' => $setting->id,
            'gateway_payload' => array_merge($charge->gateway_payload ?? [], [
                'checkout' => [
                    'preference_id' => $preferenceId,
                    'init_point' => $initPoint,
                    'created_at' => now()->toIso8601String(),
                ],
            ]),
            'updated_by' => $userId,
        ]);

        return ['url' => $initPoint, 'preference_id' => $preferenceId];
    }

    public function syncPixPayment(StudentCharge $charge, string $paymentId): StudentCharge
    {
        $setting = $charge->gatewaySetting;

        if (! $setting?->isUsable()) {
            $setting = $this->credentials->resolve($charge->network, $charge->unit);
        }

        if (! $setting) {
            throw new MercadoPagoException('Não há credenciais do Mercado Pago para confirmar a cobrança.');
        }

        $payload = MercadoPagoClient::forSetting($setting)->getPayment($paymentId);

        $reference = $payload['external_reference'] ?? null;

        if ($reference !== self::externalReference($charge)) {
            throw new MercadoPagoException('O pagamento informado não pertence a esta cobrança.');
        }

        $charge->update([
            'gateway_payment_id' => $paymentId,
            'gateway_payload' => $payload,
        ]);

        if (($payload['status'] ?? null) === 'approved') {
            $this->markPaid($charge, 'pix');
        }

        return $charge->fresh();
    }

    public function gatewaySettingForPix(StudentCharge $charge): ?PaymentGatewaySetting
    {
        return $this->credentials->resolve($charge->network, $charge->unit);
    }

    public static function externalReference(StudentCharge $charge): string
    {
        return 'charge:'.$charge->id;
    }

    public static function chargeIdFromReference(?string $reference): ?int
    {
        if (! $reference || ! preg_match('/^charge:(\d+)$/', $reference, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }

    private function syncSaleState(StudentCharge $charge): void
    {
        if ($charge->student_sale_item_id) {
            StudentSaleItem::whereKey($charge->student_sale_item_id)
                ->update(['status' => $charge->status]);
        }

        if ($charge->student_sale_id) {
            StudentSale::find($charge->student_sale_id)?->refreshStatus();
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $options
     */
    private function createCharge(
        Student $student,
        ?Unit $unit,
        string $type,
        string $description,
        float $amount,
        ?object $reference,
        array $metadata,
        ?int $userId,
        array $options = []
    ): StudentCharge {
        $referenceType = match (true) {
            $reference instanceof StudentSubscription => 'subscription',
            $reference instanceof ClassBooking => 'class_booking',
            default => null,
        };

        return StudentCharge::create([
            'network_id' => $student->network_id,
            'unit_id' => $unit?->id,
            'student_id' => $student->id,
            'student_sale_id' => $options['sale_id'] ?? null,
            'student_sale_item_id' => $options['sale_item_id'] ?? null,
            'type' => $type,
            'description' => $description,
            'amount' => $amount,
            'status' => StudentCharge::STATUS_PENDING,
            'reference_type' => $referenceType,
            'reference_id' => $reference?->id,
            'due_date' => $options['due_date'] ?? now()->toDateString(),
            'metadata' => $metadata,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }
}
