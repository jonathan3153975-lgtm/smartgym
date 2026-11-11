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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('person_type', ['individual', 'company']);
            $table->enum('category', ['staff', 'client'])->default('client');
            $table->string('name');
            $table->string('trade_name')->nullable();
            $table->string('document', 18)->nullable();
            $table->string('document_raw', 14)->nullable();
            $table->string('state_registration')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('legal_representative_name')->nullable();
            $table->string('legal_representative_document', 14)->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('zip_code', 9)->nullable();
            $table->string('street')->nullable();
            $table->string('number', 20)->nullable();
            $table->string('complement')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['network_id', 'name']);
            $table->index(['network_id', 'document_raw']);
            $table->index(['network_id', 'status']);
            $table->index(['network_id', 'category']);
            $table->index(['unit_id', 'status']);
            $table->index('email');
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
