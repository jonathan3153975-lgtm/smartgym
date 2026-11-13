<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('payment_gateway_setting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('pending');
            $table->date('booked_for');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('preference_id')->nullable();
            $table->string('gateway_payment_id')->nullable();
            $table->text('init_point')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'class_id', 'booked_for'], 'class_booking_student_class_date_unique');
            $table->index(['class_id', 'booked_for', 'status']);
            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_bookings');
    }
};
