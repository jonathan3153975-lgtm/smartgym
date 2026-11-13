<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('provider', 40)->default('mercado_pago');
            $table->string('environment', 20)->default('sandbox');
            $table->string('public_key')->nullable();
            $table->text('access_token')->nullable();
            $table->text('webhook_secret')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['network_id', 'unit_id', 'provider'], 'payment_gateway_scope_unique');
            $table->index(['network_id', 'provider', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_settings');
    }
};
