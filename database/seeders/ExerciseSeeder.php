<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Network;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $network = Network::where('slug', 'smartgym-demo')->first();

        if (! $network) {
            return;
        }

        $exercises = [
            ['name' => 'Supino reto', 'muscle_group' => 'chest', 'equipment' => 'barbell'],
            ['name' => 'Supino inclinado com halteres', 'muscle_group' => 'chest', 'equipment' => 'dumbbell'],
            ['name' => 'Puxada frente', 'muscle_group' => 'back', 'equipment' => 'machine'],
            ['name' => 'Remada curvada', 'muscle_group' => 'back', 'equipment' => 'barbell'],
            ['name' => 'Agachamento livre', 'muscle_group' => 'legs', 'equipment' => 'barbell'],
            ['name' => 'Leg press', 'muscle_group' => 'legs', 'equipment' => 'machine'],
            ['name' => 'Levantamento terra', 'muscle_group' => 'back', 'equipment' => 'barbell'],
            ['name' => 'Stiff', 'muscle_group' => 'legs', 'equipment' => 'barbell'],
            ['name' => 'Desenvolvimento militar', 'muscle_group' => 'shoulders', 'equipment' => 'barbell'],
            ['name' => 'Elevação lateral', 'muscle_group' => 'shoulders', 'equipment' => 'dumbbell'],
            ['name' => 'Rosca direta', 'muscle_group' => 'biceps', 'equipment' => 'barbell'],
            ['name' => 'Tríceps na corda', 'muscle_group' => 'triceps', 'equipment' => 'machine'],
            ['name' => 'Prancha', 'muscle_group' => 'core', 'equipment' => 'none'],
            ['name' => 'Abdominal remador', 'muscle_group' => 'core', 'equipment' => 'none'],
            ['name' => 'Esteira', 'muscle_group' => 'cardio', 'equipment' => 'machine'],
            ['name' => 'Cadeira extensora', 'muscle_group' => 'legs', 'equipment' => 'machine'],
        ];

        foreach ($exercises as $exercise) {
            Exercise::firstOrCreate(
                [
                    'network_id' => $network->id,
                    'name' => $exercise['name'],
                ],
                array_merge($exercise, [
                    'network_id' => $network->id,
                    'status' => 'active',
                ])
            );
        }
    }
}
