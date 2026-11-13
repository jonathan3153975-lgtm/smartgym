<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exercise_id')->nullable()->constrained()->nullOnDelete();
            $table->string('exercise_name');
            $table->string('muscle_group', 40);
            $table->unsignedTinyInteger('order')->default(1);
            $table->unsignedTinyInteger('sets')->default(3);
            $table->string('reps', 40)->default('10');
            $table->unsignedSmallInteger('rest_seconds')->default(60);
            $table->string('suggested_load', 80)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workout_session_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_exercises');
    }
};
