<?php

namespace Tests\Feature;

use App\Models\MembershipPlan;
use App\Models\Network;
use App\Models\PaymentGatewaySetting;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\StudentSubscription;
use App\Models\StudentUnit;
use App\Models\SubscriptionPayment;
use App\Models\Unit;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StudentCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Network $network;

    private Unit $unit;

    private Student $student;

    private StudentAccount $account;

    private MembershipPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->network = Network::where('slug', 'smartgym-demo')->firstOrFail();
        $this->unit = Unit::where('identifier', 'UNID001')->firstOrFail();

        $this->student = Student::create([
            'network_id' => $this->network->id,
            'name' => 'Aluno Checkout',
            'email' => 'checkout@exemplo.com',
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
            'email' => 'checkout@exemplo.com',
            'password' => 'SenhaCheckout123',
            'email_verified_at' => now(),
            'must_change_password' => false,
            'status' => 'active',
        ]);

        $this->plan = MembershipPlan::create([
            'network_id' => $this->network->id,
            'created_level' => 'network',
            'name' => 'Plano Online',
            'description' => 'Plano comercializado pelo portal',
            'price' => 129.90,
            'period' => 'monthly',
            'duration_days' => 30,
            'weekly_accesses' => 5,
            'daily_checkins' => 1,
            'all_units' => true,
            'all_modalities' => true,
        ]);
    }

    /** @test */
    public function test_checkout_uses_the_unit_credentials_when_configured(): void
    {
        $this->makeSetting(null, 'network-token');
        $unitSetting = $this->makeSetting($this->unit, 'unit-token');

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-unit',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-unit',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-unit',
            ], 201),
        ]);

        $this->login();

        $this->post(route('student.portal.plans.checkout', [$this->network, $this->plan]))
            ->assertRedirect('https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-unit');

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://api.mercadopago.com/checkout/preferences'
                && $request->hasHeader('Authorization', 'Bearer unit-token')
                && ($data['external_reference'] ?? null) === 'subscription:1'
                && ($data['notification_url'] ?? null) === route('webhooks.mercado-pago', ['subscription' => 1])
                && ($data['items'][0]['unit_price'] ?? null) === 129.9;
        });

        $subscription = StudentSubscription::firstOrFail();

        $this->assertSame(StudentSubscription::STATUS_PENDING, $subscription->status);
        $this->assertSame($unitSetting->id, $subscription->payment_gateway_setting_id);
        $this->assertSame('pref-unit', $subscription->gateway_reference);

        $this->assertDatabaseHas('subscription_payments', [
            'student_subscription_id' => $subscription->id,
            'status' => SubscriptionPayment::STATUS_PENDING,
            'preference_id' => 'pref-unit',
        ]);
    }

    /** @test */
    public function test_checkout_falls_back_to_the_network_credentials(): void
    {
        $networkSetting = $this->makeSetting(null, 'network-token');

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-network',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-network',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-network',
            ], 201),
        ]);

        $this->login();

        $this->post(route('student.portal.plans.checkout', [$this->network, $this->plan]))
            ->assertRedirect('https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-network');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer network-token'));

        $this->assertSame(
            $networkSetting->id,
            StudentSubscription::firstOrFail()->payment_gateway_setting_id
        );
    }

    /** @test */
    public function test_checkout_requires_configured_credentials(): void
    {
        Http::fake();

        $this->login();

        $this->post(route('student.portal.plans.checkout', [$this->network, $this->plan]))
            ->assertSessionHasErrors('plan');

        Http::assertNothingSent();
        $this->assertDatabaseCount('student_subscriptions', 0);
    }

    /** @test */
    public function test_webhook_approves_the_subscription(): void
    {
        $setting = $this->makeSetting($this->unit, 'unit-token');
        $subscription = $this->makeSubscription([
            'payment_gateway_setting_id' => $setting->id,
        ]);

        SubscriptionPayment::create([
            'student_subscription_id' => $subscription->id,
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'payment_gateway_setting_id' => $setting->id,
            'status' => SubscriptionPayment::STATUS_PENDING,
            'amount' => 129.90,
            'external_reference' => 'subscription:'.$subscription->id,
        ]);

        Http::fake([
            'api.mercadopago.com/v1/payments/555' => Http::response([
                'id' => 555,
                'status' => 'approved',
                'transaction_amount' => 129.90,
                'external_reference' => 'subscription:'.$subscription->id,
            ]),
        ]);

        $this->postJson(route('webhooks.mercado-pago', ['subscription' => $subscription->id]), [
            'type' => 'payment',
            'action' => 'payment.updated',
            'data' => ['id' => '555'],
        ])->assertOk()->assertJsonPath('status', 'approved');

        $subscription->refresh();

        $this->assertSame(StudentSubscription::STATUS_ACTIVE, $subscription->status);
        $this->assertSame('555', $subscription->gateway_payment_id);
        $this->assertNotNull($subscription->starts_at);
        $this->assertNotNull($subscription->ends_at);
        $this->assertTrue($subscription->ends_at->greaterThan(now()->addDays(29)));

        $this->assertDatabaseHas('subscription_payments', [
            'student_subscription_id' => $subscription->id,
            'gateway_payment_id' => '555',
            'status' => SubscriptionPayment::STATUS_APPROVED,
        ]);
    }

    /** @test */
    public function test_webhook_rejects_a_payment_from_another_subscription(): void
    {
        $setting = $this->makeSetting($this->unit, 'unit-token');
        $subscription = $this->makeSubscription([
            'payment_gateway_setting_id' => $setting->id,
        ]);

        Http::fake([
            'api.mercadopago.com/v1/payments/888' => Http::response([
                'id' => 888,
                'status' => 'approved',
                'external_reference' => 'subscription:999',
            ]),
        ]);

        $this->postJson(route('webhooks.mercado-pago', ['subscription' => $subscription->id]), [
            'type' => 'payment',
            'data' => ['id' => '888'],
        ])->assertStatus(500);

        $this->assertSame(StudentSubscription::STATUS_PENDING, $subscription->fresh()->status);
    }

    /** @test */
    public function test_webhook_rejects_an_invalid_signature_when_a_secret_is_configured(): void
    {
        $setting = $this->makeSetting($this->unit, 'unit-token');
        $setting->update(['webhook_secret' => 'super-secret']);

        $subscription = $this->makeSubscription([
            'payment_gateway_setting_id' => $setting->id,
        ]);

        Http::fake();

        $this->postJson(route('webhooks.mercado-pago', ['subscription' => $subscription->id]), [
            'type' => 'payment',
            'data' => ['id' => '999'],
        ], [
            'x-signature' => 'ts=123,v1=invalid',
            'x-request-id' => 'request-1',
        ])->assertStatus(401);

        Http::assertNothingSent();
        $this->assertSame(StudentSubscription::STATUS_PENDING, $subscription->fresh()->status);
    }

    /** @test */
    public function test_return_page_confirms_the_payment_and_activates_the_plan(): void
    {
        $setting = $this->makeSetting($this->unit, 'unit-token');
        $subscription = $this->makeSubscription([
            'payment_gateway_setting_id' => $setting->id,
        ]);

        Http::fake([
            'api.mercadopago.com/v1/payments/777' => Http::response([
                'id' => 777,
                'status' => 'approved',
                'transaction_amount' => 129.90,
                'external_reference' => 'subscription:'.$subscription->id,
            ]),
        ]);

        $this->login();

        $this->get(route('student.portal.checkout.status', [
            'network' => $this->network,
            'studentSubscription' => $subscription,
            'payment_id' => '777',
        ]))
            ->assertOk()
            ->assertSee('Pagamento aprovado')
            ->assertSee('Plano Online');

        $this->assertSame(StudentSubscription::STATUS_ACTIVE, $subscription->fresh()->status);
    }

    /** @test */
    public function test_student_with_an_active_plan_lands_on_home(): void
    {
        $this->makeSubscription([
            'status' => StudentSubscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
            'paid_at' => now(),
        ]);

        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'checkout@exemplo.com',
            'password' => 'SenhaCheckout123',
        ])->assertRedirect(route('student.portal.home', $this->network));

        $this->get(route('student.portal.home', $this->network))
            ->assertOk()
            ->assertSee('Plano Online')
            ->assertSee('Válido até');
    }

    /** @test */
    public function test_checkout_blocks_a_second_active_subscription(): void
    {
        $this->makeSubscription([
            'status' => StudentSubscription::STATUS_ACTIVE,
            'starts_at' => now(),
            'ends_at' => now()->addDays(30),
        ]);

        Http::fake();

        $this->login();

        $this->post(route('student.portal.plans.checkout', [$this->network, $this->plan]))
            ->assertSessionHasErrors('plan');

        Http::assertNothingSent();
        $this->assertSame(1, StudentSubscription::count());
    }

    private function login(): void
    {
        $this->post(route('student.portal.login.store', $this->network), [
            'email' => 'checkout@exemplo.com',
            'password' => 'SenhaCheckout123',
        ])->assertRedirect();
    }

    private function makeSetting(?Unit $unit, string $token): PaymentGatewaySetting
    {
        return PaymentGatewaySetting::create([
            'network_id' => $this->network->id,
            'unit_id' => $unit?->id,
            'provider' => PaymentGatewaySetting::PROVIDER_MERCADO_PAGO,
            'environment' => 'sandbox',
            'access_token' => $token,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeSubscription(array $attributes = []): StudentSubscription
    {
        return StudentSubscription::create(array_merge([
            'network_id' => $this->network->id,
            'unit_id' => $this->unit->id,
            'student_id' => $this->student->id,
            'membership_plan_id' => $this->plan->id,
            'status' => StudentSubscription::STATUS_PENDING,
            'plan_name' => $this->plan->name,
            'price' => $this->plan->price,
            'period' => $this->plan->period,
            'duration_days' => 30,
        ], $attributes));
    }
}
