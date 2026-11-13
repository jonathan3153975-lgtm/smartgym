<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MAP = [
        'nenhum' => 'none',
        'peso corporal' => 'none',
        'anilha' => 'plate',
        'barra' => 'barbell',
        'faixa elástica' => 'resistance_band',
        'faixa elastica' => 'resistance_band',
        'fita de suspensão' => 'suspension_strap',
        'fita de suspensao' => 'suspension_strap',
        'halter' => 'dumbbell',
        'halteres' => 'dumbbell',
        'kettlebell' => 'kettlebell',
        'máquina' => 'machine',
        'maquina' => 'machine',
        'pulley' => 'machine',
        'esteira' => 'machine',
        'outro' => 'other',
    ];

    public function up(): void
    {
        $allowed = [
            'none',
            'plate',
            'barbell',
            'resistance_band',
            'suspension_strap',
            'dumbbell',
            'kettlebell',
            'machine',
            'other',
        ];

        DB::table('exercises')
            ->select('id', 'equipment')
            ->orderBy('id')
            ->get()
            ->each(function ($exercise) use ($allowed) {
                $value = trim((string) $exercise->equipment);

                if ($value === '' || in_array($value, $allowed, true)) {
                    return;
                }

                $normalized = self::MAP[mb_strtolower($value)] ?? 'other';

                DB::table('exercises')
                    ->where('id', $exercise->id)
                    ->update(['equipment' => $normalized]);
            });
    }

    public function down(): void
    {
        // Equipment normalization is not reversible for custom values.
    }
};
