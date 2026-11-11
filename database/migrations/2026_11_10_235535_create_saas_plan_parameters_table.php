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
        Schema::create('saas_plan_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saas_plan_id')->constrained('saas_plans')->cascadeOnDelete();
            $table->string('description');
            $table->decimal('value', 12, 2)->default(0);
            $table->timestamps();

            $table->index(['saas_plan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saas_plan_parameters');
    }
};
