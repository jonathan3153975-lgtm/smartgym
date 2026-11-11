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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: 'network_admin', 'unit_manager', 'receptionist'
            $table->string('display_name'); // Ex: 'Administrador da Rede', 'Gerente da Unidade'
            $table->string('level'); // 'platform', 'network', 'region', 'unit'
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false); // Roles criados pelo sistema não podem ser excluídos
            $table->timestamps();

            $table->unique(['name', 'level']);
            $table->index(['level']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
