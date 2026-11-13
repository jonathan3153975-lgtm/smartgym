<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plan_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['membership_plan_id', 'unit_id']);
        });

        Schema::create('membership_plan_modalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modality_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['membership_plan_id', 'modality_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_plan_modalities');
        Schema::dropIfExists('membership_plan_units');
    }
};
