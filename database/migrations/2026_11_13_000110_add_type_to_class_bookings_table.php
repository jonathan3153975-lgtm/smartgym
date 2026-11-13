<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_bookings', function (Blueprint $table) {
            $table->string('type', 20)->default('single')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('class_bookings', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
