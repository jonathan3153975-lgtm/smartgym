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
        Schema::create('student_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive', 'transferred'])->default('active');
            $table->boolean('is_primary')->default(false); // Unidade principal do aluno
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Um aluno não pode ter o mesmo vínculo duplicado na mesma unidade
            $table->unique(['student_id', 'unit_id', 'status'], 'unique_active_student_unit')
                ->where('status', 'active');

            $table->index(['student_id', 'status']);
            $table->index(['network_id', 'unit_id', 'status']);
            $table->index(['unit_id', 'status']);
            $table->index(['is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_units');
    }
};
