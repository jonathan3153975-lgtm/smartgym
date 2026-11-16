<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->string('status', 20)->default('pending');
            $table->string('payment_method', 30)->nullable();
            $table->string('reference_type', 30)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('due_date')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('payment_gateway_setting_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway_payment_id')->nullable();
            $table->text('pix_qr_code')->nullable();
            $table->longText('pix_qr_code_base64')->nullable();
            $table->dateTime('pix_generated_at')->nullable();
            $table->json('gateway_payload')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'status']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['network_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_charges');
    }
};
