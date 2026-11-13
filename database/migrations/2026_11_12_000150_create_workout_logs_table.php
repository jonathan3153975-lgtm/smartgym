<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('workout_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_name');
            $table->string('status', 20)->default('in_progress');
            $table->dateTime('started_at');
            $table->dateTime('completed_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'started_at']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_logs');
    }
};
