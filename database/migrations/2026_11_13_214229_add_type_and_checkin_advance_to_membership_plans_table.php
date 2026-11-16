<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->string('type', 20)->default('basic')->after('description');
            $table->unsignedSmallInteger('checkin_advance_minutes')->default(20)->after('daily_checkins');
        });

        // Preserve the previous behaviour of plans that already included modalities.
        DB::table('membership_plans')
            ->where('all_modalities', true)
            ->update(['type' => 'mixed']);

        $planIdsWithModalities = DB::table('membership_plan_modalities')
            ->distinct()
            ->pluck('membership_plan_id');

        if ($planIdsWithModalities->isNotEmpty()) {
            DB::table('membership_plans')
                ->whereIn('id', $planIdsWithModalities)
                ->update(['type' => 'mixed']);
        }

        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->string('plan_type', 20)->default('basic')->after('duration_days');
            $table->unsignedSmallInteger('checkin_advance_minutes')->default(20)->after('daily_checkins');
        });

        DB::table('membership_plans')
            ->select(['id', 'type', 'checkin_advance_minutes'])
            ->orderBy('id')
            ->chunk(100, function ($plans): void {
                foreach ($plans as $plan) {
                    DB::table('student_subscriptions')
                        ->where('membership_plan_id', $plan->id)
                        ->update([
                            'plan_type' => $plan->type,
                            'checkin_advance_minutes' => $plan->checkin_advance_minutes,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['plan_type', 'checkin_advance_minutes']);
        });

        Schema::table('membership_plans', function (Blueprint $table) {
            $table->dropColumn(['type', 'checkin_advance_minutes']);
        });
    }
};
