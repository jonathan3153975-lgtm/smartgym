<?php

namespace Database\Factories;

use App\Models\Network;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

class RegionFactory extends Factory
{
    protected $model = Region::class;

    public function definition(): array
    {
        return [
            'network_id' => Network::inRandomOrder()->first()?->id ?? Network::factory(),
            'name' => fake()->unique()->state(),
            'description' => fake()->sentence(),
            'status' => 'active',
        ];
    }
}
