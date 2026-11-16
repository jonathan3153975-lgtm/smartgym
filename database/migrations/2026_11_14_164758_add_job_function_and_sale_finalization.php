<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('job_function', 40)->nullable()->after('category');
        });

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('teacher_id')->constrained('people')->nullOnDelete();
        });

        Schema::table('class_bookings', function (Blueprint $table) {
            $table->boolean('credit_available')->default(false)->after('checked_in_at');
            $table->dateTime('credit_granted_at')->nullable()->after('credit_available');
        });

        Schema::create('student_sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_sale_id')->constrained()->cascadeOnDelete();
            $table->string('method', 30);
            $table->decimal('amount', 10, 2);
            $table->unsignedTinyInteger('installments')->default(1);
            $table->string('status', 20)->default('confirmed');
            $table->string('notes', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('student_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('type', 30)->default('info');
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'read_at']);
        });

        // Link existing schedule teachers to their person records when possible.
        DB::table('class_schedules')
            ->whereNotNull('teacher_id')
            ->whereNull('person_id')
            ->select(['id', 'teacher_id'])
            ->orderBy('id')
            ->chunk(100, function ($schedules): void {
                foreach ($schedules as $schedule) {
                    $personId = DB::table('users')->where('id', $schedule->teacher_id)->value('person_id');

                    if ($personId) {
                        DB::table('class_schedules')->where('id', $schedule->id)->update(['person_id' => $personId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_notifications');

        Schema::table('class_bookings', function (Blueprint $table) {
            $table->dropColumn(['credit_available', 'credit_granted_at']);
        });

        Schema::dropIfExists('student_sale_payments');

        Schema::table('class_schedules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
        });

        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn('job_function');
        });
    }
};
