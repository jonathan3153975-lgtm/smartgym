<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->string('email');
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->rememberToken();
            $table->timestamps();

            $table->unique(['network_id', 'email']);
            $table->index(['network_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_accounts');
    }
};
