<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_sale_items', function (Blueprint $table) {
            $table->boolean('auto_renew')->default(false)->after('checkin_advance_minutes');
        });

        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->boolean('auto_renew')->default(false)->after('checkin_advance_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('student_sale_items', function (Blueprint $table) {
            $table->dropColumn('auto_renew');
        });

        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->dropColumn('auto_renew');
        });
    }
};
