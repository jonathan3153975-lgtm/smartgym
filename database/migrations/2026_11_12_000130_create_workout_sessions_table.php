<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_plan_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('day_label', 40)->nullable();
            $table->unsignedTinyInteger('order')->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['workout_plan_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_sessions');
    }
};
