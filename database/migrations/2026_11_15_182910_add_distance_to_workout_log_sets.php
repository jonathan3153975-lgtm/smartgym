<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_log_sets', function (Blueprint $table) {
            $table->decimal('distance_km', 8, 3)->nullable()->after('weight_kg');
            $table->unsignedInteger('cardio_seconds')->nullable()->after('distance_km');
        });
    }

    public function down(): void
    {
        Schema::table('workout_log_sets', function (Blueprint $table) {
            $table->dropColumn('distance_km');
        });
    }
};
