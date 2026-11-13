<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('muscle_group', 40);
            $table->string('equipment', 80)->nullable();
            $table->text('instructions')->nullable();
            $table->string('status', 20)->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['network_id', 'muscle_group']);
            $table->index(['network_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
