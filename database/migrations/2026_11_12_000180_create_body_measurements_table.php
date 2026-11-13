<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->date('measured_at');
            $table->decimal('weight_kg', 5, 2);
            $table->decimal('body_fat_percent', 4, 1)->nullable();
            $table->decimal('waist_cm', 5, 2)->nullable();
            $table->decimal('hip_cm', 5, 2)->nullable();
            $table->decimal('chest_cm', 5, 2)->nullable();
            $table->decimal('arm_cm', 5, 2)->nullable();
            $table->decimal('thigh_cm', 5, 2)->nullable();
            $table->decimal('calf_cm', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'measured_at']);
            $table->index(['network_id', 'measured_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('body_measurements');
    }
};
