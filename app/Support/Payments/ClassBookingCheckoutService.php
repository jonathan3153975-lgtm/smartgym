<?php

namespace App\Support\Payments;

use App\Models\ClassBooking;
use App\Models\PaymentGatewaySetting;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Support\NetworkTheme;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ClassBookingCheckoutService
{
    public function __construct(private readonly MercadoPagoCredentials $credentials) {}

    /**
     * Create a pending class booking and its Mercado Pago preference.
     *
     * @return string The Mercado Pago checkout URL.
     */
    public function start(StudentAccount $account, SchoolClass $class): string
    {
        $student = $account->student;

        if (! $student instanceof Student) {
            throw ValidationException::withMessages([
                'class' => 'Não foi possível identificar o aluno para esta reserva.',
            ]);
        }

        if ($student->activeSubscription()) {
            throw ValidationException::withMessages([
                'class' => 'Esta aula já está incluída no seu plano ativo.',
            ]);
        }

        if (! $class->isActive()) {
            throw ValidationException::withMessages([
                'class' => 'Esta aula não está disponível.',
            ]);
        }

        $price = (float) $class->drop_in_price;

        if ($price <= 0) {
            throw ValidationException::withMessages([
                'class' => 'Esta aula não está disponível para compra avulsa.',
            ]);
        }

        $bookedFor = $this->nextDateForWeekday($class->weekday);

        $occupied = ClassBooking::query()
            ->where('class_id', $class->id)
            ->whereDate('booked_for', $bookedFor->toDateString())
            ->whereIn('status', [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED])
            ->count();

        if ($occupied >= $class->vacancies) {
            throw ValidationException::withMessages([
                'class' => 'Esta aula está sem vagas para a data selecionada.',
            ]);
        }

        $existing = ClassBooking::query()
            ->where('student_id', $student->id)
            ->where('class_id', $class->id)
            ->whereDate('booked_for', $bookedFor->toDateString())
            ->first();

        if ($existing && in_array($existing->status, [ClassBooking::STATUS_PENDING, ClassBooking::STATUS_CONFIRMED], true)) {
            throw ValidationException::withMessages([
                'class' => 'Você já possui uma reserva para esta aula nesta data.',
            ]);
        }

        $setting = $this->credentials->resolve($student->network, $class->unit);

        if (! $setting) {
            throw ValidationException::withMessages([
                'class' => 'O pagamento online ainda não está configurado para esta unidade.',
            ]);
        }

        $booking = DB::transaction(function () use ($existing, $student, $class, $bookedFor, $price, $setting) {
            $data = [
                'network_id' => $student->network_id,
                'unit_id' => $class->unit_id,
                'student_id' => $student->id,
                'class_id' => $class->id,
                'payment_gateway_setting_id' => $setting->id,
                'status' => ClassBooking::STATUS_PENDING,
                'type' => ClassBooking::TYPE_SINGLE,
                'booked_for' => $bookedFor->toDateString(),
                'amount' => $price,
                'paid_at' => null,
                'gateway_payment_id' => null,
            ];

            if ($existing) {
                $existing->update($data);

                return $existing->fresh();
            }

            return ClassBooking::create($data);
        });

        try {
            $preference = MercadoPagoClient::forSetting($setting)
                ->createPreference($this->preferencePayload($booking, $class, $account, $bookedFor));

            $preferenceId = isset($preference['id']) ? (string) $preference['id'] : null;
            $initPoint = $this->initPoint($setting, $preference);

            if (! $preferenceId || ! $initPoint) {
                throw new MercadoPagoException('O Mercado Pago não retornou o link de pagamento.');
            }
        } catch (MercadoPagoException $exception) {
            $booking->update([
                'status' => ClassBooking::STATUS_FAILED,
                'gateway_payload' => ['error' => $exception->getMessage()],
            ]);

            Log::warning('Falha ao criar a preferência de aula avulsa no Mercado Pago.', [
                'class_booking_id' => $booking->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $booking->update([
            'preference_id' => $preferenceId,
            'init_point' => $initPoint,
            'gateway_payload' => $preference,
        ]);

        return $initPoint;
    }

    public function syncPayment(ClassBooking $booking, string $paymentId): ClassBooking
    {
        $setting = $booking->gatewaySetting;

        if (! $setting?->isUsable()) {
            $setting = $this->credentials->resolve($booking->network, $booking->unit);
        }

        if (! $setting) {
            throw new MercadoPagoException('Não há credenciais do Mercado Pago para confirmar a reserva.');
        }

        $payload = MercadoPagoClient::forSetting($setting)->getPayment($paymentId);

        $reference = $payload['external_reference'] ?? null;
        $metadataId = $payload['metadata']['class_booking_id'] ?? null;

        if ($reference !== $this->externalReference($booking)
            && (string) $metadataId !== (string) $booking->id) {
            throw new MercadoPagoException('O pagamento informado não pertence a esta reserva.');
        }

        $status = (string) ($payload['status'] ?? ClassBooking::STATUS_PENDING);

        $booking->update([
            'status' => $status === 'approved' ? ClassBooking::STATUS_CONFIRMED : $booking->status,
            'gateway_payment_id' => $paymentId,
            'paid_at' => $status === 'approved' ? now() : $booking->paid_at,
            'gateway_payload' => $payload,
        ]);

        return $booking->fresh();
    }

    public static function externalReference(ClassBooking $booking): string
    {
        return 'class-booking:'.$booking->id;
    }

    public static function bookingIdFromReference(?string $reference): ?int
    {
        if (! $reference || ! preg_match('/^class-booking:(\d+)$/', $reference, $matches)) {
            return null;
        }

        return (int) $matches[1];
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
     * @return array<string, mixed>
     */
    private function preferencePayload(
        ClassBooking $booking,
        SchoolClass $class,
        StudentAccount $account,
        Carbon $bookedFor
    ): array {
        $redirectUrl = route('student.portal.classes.booking.status', [
            'network' => $booking->network,
            'classBooking' => $booking,
        ]);

        return [
            'items' => [
                array_filter([
                    'id' => 'class-'.$class->id,
                    'title' => 'Aula avulsa: '.$class->name,
                    'description' => $class->unit?->name.' - '.$bookedFor->format('d/m/Y').' '.$class->time_label,
                    'quantity' => 1,
                    'currency_id' => 'BRL',
                    'unit_price' => round((float) $class->drop_in_price, 2),
                ], fn ($value) => $value !== null && $value !== ''),
            ],
            'payer' => array_filter([
                'name' => $booking->student?->name,
                'email' => $account->email,
            ]),
            'external_reference' => self::externalReference($booking),
            'notification_url' => route('webhooks.mercado-pago', ['class_booking' => $booking->id]),
            'back_urls' => [
                'success' => $redirectUrl,
                'pending' => $redirectUrl,
                'failure' => $redirectUrl,
            ],
            'auto_return' => 'approved',
            'statement_descriptor' => mb_substr(NetworkTheme::companyName($booking->network), 0, 22),
            'metadata' => [
                'network_id' => $booking->network_id,
                'unit_id' => $booking->unit_id,
                'student_id' => $booking->student_id,
                'class_id' => $class->id,
                'class_booking_id' => $booking->id,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $preference
     */
    private function initPoint(PaymentGatewaySetting $setting, array $preference): ?string
    {
        $production = $preference['init_point'] ?? null;
        $sandbox = $preference['sandbox_init_point'] ?? null;

        return $setting->environment === 'production'
            ? ($production ?? $sandbox)
            : ($sandbox ?? $production);
    }
}
