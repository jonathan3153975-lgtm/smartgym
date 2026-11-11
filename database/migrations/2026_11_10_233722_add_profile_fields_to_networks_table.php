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
        Schema::table('networks', function (Blueprint $table) {
            // Endereço
            $table->string('zip_code', 9)->nullable()->after('document');
            $table->string('street')->nullable()->after('zip_code');
            $table->string('number', 20)->nullable()->after('street');
            $table->string('complement')->nullable()->after('number');
            $table->string('neighborhood')->nullable()->after('complement');
            $table->string('city')->nullable()->after('neighborhood');
            $table->string('state', 2)->nullable()->after('city');

            // Responsável
            $table->string('responsible_name')->nullable()->after('state');
            $table->string('responsible_cpf', 14)->nullable()->after('responsible_name');
            $table->string('responsible_email')->nullable()->after('responsible_cpf');
            $table->string('responsible_phone', 20)->nullable()->after('responsible_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('networks', function (Blueprint $table) {
            $table->dropColumn([
                'zip_code',
                'street',
                'number',
                'complement',
                'neighborhood',
                'city',
                'state',
                'responsible_name',
                'responsible_cpf',
                'responsible_email',
                'responsible_phone',
            ]);
        });
    }
};
