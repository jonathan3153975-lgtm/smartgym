<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30)->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->date('launched_at')->nullable();
            $table->string('notes', 500)->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['network_id', 'status']);
        });

        Schema::create('student_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_sale_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->foreignId('membership_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->date('launched_at')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedTinyInteger('weekly_accesses')->nullable();
            $table->unsignedTinyInteger('daily_checkins')->nullable();
            $table->unsignedSmallInteger('checkin_advance_minutes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['student_sale_id', 'type']);
        });

        Schema::create('student_sale_item_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_sale_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->timestamps();

            $table->unique(['student_sale_item_id', 'class_id']);
        });

        Schema::table('student_charges', function (Blueprint $table) {
            $table->foreignId('student_sale_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
            $table->foreignId('student_sale_item_id')->nullable()->after('student_sale_id')->constrained()->nullOnDelete();
        });

        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->foreignId('student_sale_item_id')->nullable()->after('membership_plan_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('student_subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_sale_item_id');
        });

        Schema::table('student_charges', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_sale_item_id');
            $table->dropConstrainedForeignId('student_sale_id');
        });

        Schema::dropIfExists('student_sale_item_slots');
        Schema::dropIfExists('student_sale_items');
        Schema::dropIfExists('student_sales');
    }
};
