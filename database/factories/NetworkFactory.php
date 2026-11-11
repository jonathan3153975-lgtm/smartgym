<?php

namespace Database\Factories;

use App\Models\Network;
use App\Models\SaasPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class NetworkFactory extends Factory
{
    protected $model = Network::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'slug' => fake()->unique()->slug(3),
            'document' => fake()->unique()->numerify('##.###.###/####-##'),
            'status' => 'active',
            'plan_id' => SaasPlan::inRandomOrder()->first()?->id ?? SaasPlan::factory(),
            'settings' => json_encode([
                'primary_color' => fake()->hexColor(),
                'secondary_color' => fake()->hexColor(),
            ]),
        ];
    }
}
