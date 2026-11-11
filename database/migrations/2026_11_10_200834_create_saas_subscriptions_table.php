<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('saas_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('saas_plans')->restrictOnDelete();
            $table->enum('status', ['trial', 'active', 'past_due', 'canceled', 'expired'])->default('trial');
            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->string('gateway')->nullable(); // stripe, mercadopago, etc.
            $table->string('gateway_subscription_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['network_id', 'status']);
            $table->index(['gateway_subscription_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saas_subscriptions');
    }
};
