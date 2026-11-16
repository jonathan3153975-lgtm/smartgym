<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Network;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        Network::query()->each(function (Network $network): void {
            self::seedForNetwork($network);
        });
    }

    /**
     * @return Collection<int, Exercise>
     */
    public static function seedForNetwork(Network $network, ?int $createdBy = null): Collection
    {
        $exercises = collect();

        foreach (ExerciseCatalog::all() as $exercise) {
            $exercises->push(Exercise::firstOrCreate(
                [
                    'network_id' => $network->id,
                    'name' => $exercise['name'],
                ],
                [
                    'network_id' => $network->id,
                    'muscle_group' => $exercise['muscle_group'],
                    'equipment' => $exercise['equipment'],
                    'status' => 'active',
                    'created_by' => $createdBy,
                    'updated_by' => $createdBy,
                ]
            ));
        }

        return $exercises;
    }
}
