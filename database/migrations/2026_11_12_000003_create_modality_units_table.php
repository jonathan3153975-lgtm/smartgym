<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modality_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modality_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['modality_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modality_units');
    }
};
