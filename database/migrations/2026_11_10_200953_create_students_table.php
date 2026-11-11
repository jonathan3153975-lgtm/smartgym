<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('social_name')->nullable();
            $table->string('cpf', 14)->nullable(); // Formatted CPF
            $table->string('cpf_raw', 11)->nullable(); // Raw CPF for indexing
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->text('address')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('street')->nullable();
            $table->string('number')->nullable();
            $table->string('complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('avatar')->nullable();
            $table->text('admin_notes')->nullable();
            $table->string('source')->nullable(); // Origem do cadastro: walk_in, online, referral, etc.
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Unique CPF per network (when provided)
            $table->unique(['network_id', 'cpf_raw'], 'unique_cpf_per_network')->whereNull('deleted_at');

            $table->index(['network_id', 'status']);
            $table->index(['network_id', 'name']);
            $table->index(['network_id', 'email']);
            $table->index(['network_id', 'phone']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
