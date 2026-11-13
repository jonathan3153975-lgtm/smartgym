<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('modality_category_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('created_level', ['network', 'region', 'unit']);
            $table->string('name');
            $table->string('image')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['network_id', 'created_level']);
            $table->index(['network_id', 'name']);
            $table->index(['region_id', 'created_level']);
            $table->index(['unit_id', 'created_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modalities');
    }
};
