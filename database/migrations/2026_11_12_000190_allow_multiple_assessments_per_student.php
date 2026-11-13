<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The unique index also supports the foreign key on MariaDB/MySQL, so the
        // regular index must exist before the unique index can be dropped.
        Schema::table('student_assessments', function (Blueprint $table) {
            $table->index(['student_id', 'submitted_at'], 'student_assessments_student_submitted_index');
        });

        Schema::table('student_assessments', function (Blueprint $table) {
            $table->dropUnique(['student_id']);
        });
    }

    public function down(): void
    {
        Schema::table('student_assessments', function (Blueprint $table) {
            $table->unique('student_id');
        });

        Schema::table('student_assessments', function (Blueprint $table) {
            $table->dropIndex('student_assessments_student_submitted_index');
        });
    }
};
