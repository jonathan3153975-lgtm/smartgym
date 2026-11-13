<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_gateway_setting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('gateway_provider', 40)->default('mercado_pago');
            $table->string('gateway_reference')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->string('plan_name');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('period', 20)->nullable();
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['network_id', 'status']);
            $table->index('gateway_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_subscriptions');
    }
};
