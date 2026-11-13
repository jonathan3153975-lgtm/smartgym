<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_gateway_setting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 40)->default('mercado_pago');
            $table->string('status', 20)->default('pending');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('external_reference')->nullable();
            $table->string('preference_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->text('init_point')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamps();

            $table->index(['student_subscription_id', 'status']);
            $table->index('preference_id');
            $table->index('gateway_payment_id');
            $table->index('external_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
