<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('regions', 'settings')) {
            Schema::table('regions', function (Blueprint $table) {
                $table->json('settings')->nullable()->after('status');
            });
        }

        if (! Schema::hasColumn('payment_gateway_settings', 'region_id')) {
            Schema::table('payment_gateway_settings', function (Blueprint $table) {
                $table->foreignId('region_id')->nullable()->after('network_id')->constrained()->nullOnDelete();
            });
        }

        if (! Schema::hasTable('day_pass_price_histories')) {
            Schema::create('day_pass_price_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('network_id')->constrained()->cascadeOnDelete();
                $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('price', 10, 2)->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['network_id', 'region_id', 'unit_id']);
            });
        }

        if (! Schema::hasTable('class_schedules')) {
            Schema::create('class_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedTinyInteger('weekday');
                $table->time('start_time');
                $table->time('end_time')->nullable();
                $table->unsignedSmallInteger('vacancies')->default(10);
                $table->timestamps();

                $table->index(['class_id', 'weekday']);
                $table->index('teacher_id');
            });
        }

        if (! Schema::hasColumn('class_bookings', 'class_schedule_id')) {
            Schema::table('class_bookings', function (Blueprint $table) {
                $table->foreignId('class_schedule_id')->nullable()->after('class_id')->constrained('class_schedules')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('student_sale_items', 'class_schedule_id')) {
            Schema::table('student_sale_items', function (Blueprint $table) {
                $table->foreignId('class_schedule_id')->nullable()->after('class_id')->constrained('class_schedules')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('student_sale_item_slots', 'class_schedule_id')) {
            Schema::table('student_sale_item_slots', function (Blueprint $table) {
                $table->foreignId('class_schedule_id')->nullable()->after('class_id')->constrained('class_schedules')->nullOnDelete();
            });
        }

        // Copy the old class timetable into the new schedule table.
        if (Schema::hasColumn('classes', 'weekday')) {
            $classes = DB::table('classes')->orderBy('id')->get();

            foreach ($classes as $class) {
                if (! isset($class->weekday, $class->start_time)) {
                    continue;
                }

                $scheduleId = DB::table('class_schedules')
                    ->where('class_id', $class->id)
                    ->value('id');

                if (! $scheduleId) {
                    $scheduleId = DB::table('class_schedules')->insertGetId([
                        'class_id' => $class->id,
                        'teacher_id' => $class->teacher_id ?? null,
                        'weekday' => $class->weekday,
                        'start_time' => $class->start_time,
                        'end_time' => $class->end_time ?? null,
                        'vacancies' => $class->vacancies ?? 10,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('class_bookings')
                    ->where('class_id', $class->id)
                    ->whereNull('class_schedule_id')
                    ->update(['class_schedule_id' => $scheduleId]);

                DB::table('student_sale_items')
                    ->where('class_id', $class->id)
                    ->whereNull('class_schedule_id')
                    ->update(['class_schedule_id' => $scheduleId]);

                if (Schema::hasColumn('student_sale_item_slots', 'class_id')) {
                    DB::table('student_sale_item_slots')
                        ->where('class_id', $class->id)
                        ->whereNull('class_schedule_id')
                        ->update(['class_schedule_id' => $scheduleId]);
                }
            }
        }

        if (DB::getDriverName() === 'sqlite') {
            $this->rebuildClassesForSqlite();
            $this->rebuildSlotsForSqlite();

            return;
        }

        if (Schema::hasColumn('classes', 'weekday')) {
            if ($this->hasForeignKey('classes', 'teacher_id')) {
                Schema::table('classes', function (Blueprint $table) {
                    $table->dropForeign(['teacher_id']);
                });
            }

            if (! $this->hasIndex('classes', 'classes_unit_id_index')) {
                DB::statement('CREATE INDEX classes_unit_id_index ON classes (unit_id)');
            }

            Schema::table('classes', function (Blueprint $table) {
                $table->dropIndex('classes_unit_id_weekday_index');
                $table->dropIndex('classes_teacher_id_weekday_index');
                $table->dropColumn(['teacher_id', 'weekday', 'start_time', 'end_time', 'vacancies']);
            });
        }

        if (Schema::hasColumn('student_sale_item_slots', 'class_id')) {
            if ($this->hasForeignKey('student_sale_item_slots', 'class_id')) {
                Schema::table('student_sale_item_slots', function (Blueprint $table) {
                    $table->dropForeign(['class_id']);
                });
            }

            if ($this->hasIndex('student_sale_item_slots', 'student_sale_item_slots_student_sale_item_id_class_id_unique')) {
                if (! $this->hasIndex('student_sale_item_slots', 'student_sale_item_slots_student_sale_item_id_index')) {
                    DB::statement('CREATE INDEX student_sale_item_slots_student_sale_item_id_index ON student_sale_item_slots (student_sale_item_id)');
                }

                Schema::table('student_sale_item_slots', function (Blueprint $table) {
                    $table->dropUnique('student_sale_item_slots_student_sale_item_id_class_id_unique');
                });
            }

            if ($this->hasIndex('student_sale_item_slots', 'student_sale_item_slots_class_id_foreign')) {
                Schema::table('student_sale_item_slots', function (Blueprint $table) {
                    $table->dropIndex('student_sale_item_slots_class_id_foreign');
                });
            }

            Schema::table('student_sale_item_slots', function (Blueprint $table) {
                $table->dropColumn('class_id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('student_sale_item_slots', function (Blueprint $table) {
            $table->foreignId('class_id')->nullable()->after('student_sale_item_id')->constrained('classes')->nullOnDelete();
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('classes', function (Blueprint $table) {
                $table->foreignId('teacher_id')->nullable()->after('unit_id')->constrained('users')->nullOnDelete();
                $table->unsignedTinyInteger('weekday')->default(1)->after('name');
                $table->time('start_time')->default('07:00')->after('weekday');
                $table->time('end_time')->nullable()->after('start_time');
                $table->unsignedSmallInteger('vacancies')->default(10)->after('end_time');
            });
        }

        Schema::table('student_sale_item_slots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_schedule_id');
        });

        Schema::table('student_sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_schedule_id');
        });

        Schema::table('class_bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('class_schedule_id');
        });

        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('day_pass_price_histories');

        Schema::table('payment_gateway_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('region_id');
        });

        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn('settings');
        });
    }

    private function hasForeignKey(string $table, string $column): bool
    {
        return DB::table('information_schema.key_column_usage')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->whereNotNull('referenced_table_name')
            ->exists();
    }

    private function hasIndex(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('index_name', $index)
            ->exists();
    }

    private function rebuildClassesForSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('CREATE TABLE classes_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            network_id INTEGER NOT NULL,
            unit_id INTEGER NOT NULL,
            modality_id INTEGER NULL,
            name VARCHAR NOT NULL,
            drop_in_price NUMERIC NULL,
            status VARCHAR NOT NULL DEFAULT \'active\',
            created_by INTEGER NULL,
            updated_by INTEGER NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            deleted_at DATETIME NULL
        )');

        DB::statement('INSERT INTO classes_new (id, network_id, unit_id, modality_id, name, drop_in_price, status, created_by, updated_by, created_at, updated_at, deleted_at)
            SELECT id, network_id, unit_id, modality_id, name, drop_in_price, status, created_by, updated_by, created_at, updated_at, deleted_at FROM classes');

        DB::statement('DROP TABLE classes');
        DB::statement('ALTER TABLE classes_new RENAME TO classes');
        DB::statement('CREATE INDEX classes_network_id_unit_id_index ON classes (network_id, unit_id)');
        DB::statement('CREATE INDEX classes_unit_id_index ON classes (unit_id)');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function rebuildSlotsForSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        DB::statement('CREATE TABLE student_sale_item_slots_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            student_sale_item_id INTEGER NOT NULL,
            class_schedule_id INTEGER NULL,
            weekday INTEGER NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )');

        DB::statement('INSERT INTO student_sale_item_slots_new (id, student_sale_item_id, class_schedule_id, weekday, start_time, end_time, created_at, updated_at)
            SELECT id, student_sale_item_id, class_schedule_id, weekday, start_time, end_time, created_at, updated_at FROM student_sale_item_slots');

        DB::statement('DROP TABLE student_sale_item_slots');
        DB::statement('ALTER TABLE student_sale_item_slots_new RENAME TO student_sale_item_slots');
        DB::statement('CREATE UNIQUE INDEX student_sale_item_slots_item_schedule_unique ON student_sale_item_slots (student_sale_item_id, class_schedule_id)');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
