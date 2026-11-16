<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->unsignedTinyInteger('weekly_accesses')->nullable()->after('duration_days');
            $table->unsignedTinyInteger('daily_checkins')->nullable()->after('weekly_accesses');
        });
    }

    public function down(): void
    {
        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['weekly_accesses', 'daily_checkins']);
        });
    }
};
