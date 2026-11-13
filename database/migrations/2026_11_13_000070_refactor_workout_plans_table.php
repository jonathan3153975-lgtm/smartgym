<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workout_plans', function (Blueprint $table) {
            $table->foreignId('student_id')->nullable()->change();
            $table->string('visibility', 20)->default('public')->after('status');
            $table->foreignId('source_plan_id')
                ->nullable()
                ->after('visibility')
                ->constrained('workout_plans')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workout_plans', function (Blueprint $table) {
            $table->dropForeign(['source_plan_id']);
            $table->dropColumn(['visibility', 'source_plan_id']);
        });
    }
};
