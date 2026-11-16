<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->json('modality_ids')->nullable()->after('plan_type');
        });

        DB::table('student_subscriptions')
            ->whereNotNull('membership_plan_id')
            ->select(['id', 'membership_plan_id'])
            ->orderBy('id')
            ->chunk(100, function ($subscriptions): void {
                foreach ($subscriptions as $subscription) {
                    $modalityIds = DB::table('membership_plan_modalities')
                        ->where('membership_plan_id', $subscription->membership_plan_id)
                        ->pluck('modality_id')
                        ->map(fn ($id) => (int) $id)
                        ->values()
                        ->all();

                    if ($modalityIds === []) {
                        continue;
                    }

                    DB::table('student_subscriptions')
                        ->where('id', $subscription->id)
                        ->update(['modality_ids' => json_encode($modalityIds)]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->dropColumn('modality_ids');
        });
    }
};
