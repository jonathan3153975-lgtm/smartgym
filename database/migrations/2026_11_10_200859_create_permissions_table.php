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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Ex: 'students.view', 'students.create', 'networks.manage'
            $table->string('name'); // Ex: 'Visualizar Alunos', 'Criar Alunos', 'Gerenciar Redes'
            $table->string('module'); // Ex: 'students', 'networks', 'units', 'reports'
            $table->string('action'); // Ex: 'view', 'create', 'edit', 'delete', 'manage', 'export'
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->index(['module', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
