<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->unsignedTinyInteger('weekly_accesses')->default(7)->after('duration_days');
            $table->unsignedTinyInteger('daily_checkins')->default(1)->after('weekly_accesses');
        });
    }

    public function down(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->dropColumn(['weekly_accesses', 'daily_checkins']);
        });
    }
};
