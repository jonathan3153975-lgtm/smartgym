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
        Schema::create('networks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('document')->nullable(); // CNPJ
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])->default('pending');
            $table->foreignId('plan_id')->nullable()->constrained('saas_plans')->nullOnDelete();
            $table->json('settings')->nullable(); // White-label settings, configurations
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status']);
            $table->index(['slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('networks');
    }
};
