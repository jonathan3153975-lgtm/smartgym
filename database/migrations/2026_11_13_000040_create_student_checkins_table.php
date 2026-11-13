<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_checkins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('trial');
            $table->dateTime('checked_in_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'type']);
            $table->index(['network_id', 'checked_in_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_checkins');
    }
};
