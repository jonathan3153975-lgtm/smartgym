<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_bookings', function (Blueprint $table) {
            $table->unsignedSmallInteger('early_minutes')->default(20)->after('booked_for');
            $table->dateTime('checkin_opens_at')->nullable()->after('early_minutes');
            $table->dateTime('checkin_closes_at')->nullable()->after('checkin_opens_at');
            $table->dateTime('checked_in_at')->nullable()->after('checkin_closes_at');
        });
    }

    public function down(): void
    {
        Schema::table('class_bookings', function (Blueprint $table) {
            $table->dropColumn(['early_minutes', 'checkin_opens_at', 'checkin_closes_at', 'checked_in_at']);
        });
    }
};
