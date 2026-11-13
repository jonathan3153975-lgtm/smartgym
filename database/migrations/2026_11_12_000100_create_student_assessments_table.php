<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('draft');

            $table->string('goal', 40)->nullable();
            $table->text('goal_notes')->nullable();
            $table->string('experience_level', 20)->nullable();
            $table->unsignedTinyInteger('training_frequency')->nullable();

            $table->text('medical_conditions')->nullable();
            $table->text('medications')->nullable();
            $table->text('injuries')->nullable();
            $table->text('surgeries')->nullable();
            $table->boolean('smoker')->default(false);
            $table->string('alcohol_consumption', 20)->nullable();
            $table->unsignedTinyInteger('sleep_hours')->nullable();
            $table->unsignedTinyInteger('stress_level')->nullable();

            $table->json('parq_answers')->nullable();
            $table->boolean('parq_positive')->default(false);

            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();

            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('body_fat_percent', 4, 1)->nullable();
            $table->decimal('waist_cm', 5, 2)->nullable();
            $table->decimal('hip_cm', 5, 2)->nullable();
            $table->decimal('chest_cm', 5, 2)->nullable();
            $table->decimal('arm_cm', 5, 2)->nullable();
            $table->decimal('thigh_cm', 5, 2)->nullable();

            $table->text('observations')->nullable();
            $table->boolean('consent')->default(false);
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();

            $table->index(['network_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_assessments');
    }
};
