<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->foreignId('modality_id')
                ->nullable()
                ->after('unit_id')
                ->constrained()
                ->nullOnDelete();

            $table->index(['modality_id', 'unit_id']);
        });

        Schema::table('units', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('state');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['modality_id']);
            $table->dropColumn('modality_id');
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
