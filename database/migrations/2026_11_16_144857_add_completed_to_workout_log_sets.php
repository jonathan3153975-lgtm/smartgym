<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_log_sets', function (Blueprint $table) {
            $table->boolean('completed')->default(false)->after('cardio_seconds');
        });
    }

    public function down(): void
    {
        Schema::table('workout_log_sets', function (Blueprint $table) {
            $table->dropColumn('completed');
        });
    }
};
