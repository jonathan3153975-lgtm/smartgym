<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_log_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_log_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->string('exercise_name');
            $table->string('muscle_group', 40);
            $table->unsignedTinyInteger('set_number');
            $table->unsignedSmallInteger('reps')->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->unsignedSmallInteger('rest_seconds')->nullable();
            $table->timestamps();

            $table->index(['workout_log_id', 'exercise_name']);
            $table->index('muscle_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_log_sets');
    }
};
