<?php

namespace Database\Factories;

use App\Models\SaasPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaasPlanFactory extends Factory
{
    protected $model = SaasPlan::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'description' => fake()->sentence(),
            'limits' => json_encode([
                'max_networks' => fake()->numberBetween(1, 10),
                'max_regions' => fake()->numberBetween(1, 50),
                'max_units' => fake()->numberBetween(1, 200),
                'max_users' => fake()->numberBetween(10, 1000),
                'max_students' => fake()->numberBetween(100, 50000),
            ]),
            'features' => json_encode([
                'students' => true,
                'plans' => fake()->boolean(),
                'classes' => fake()->boolean(),
                'reservations' => fake()->boolean(),
                'workouts' => fake()->boolean(),
                'finance' => fake()->boolean(),
                'access_control' => fake()->boolean(),
                'hr' => fake()->boolean(),
                'expenses' => fake()->boolean(),
                'crm' => fake()->boolean(),
                'reports' => true,
                'integrations' => fake()->boolean(),
                'white_label' => fake()->boolean(),
            ]),
            'price' => fake()->randomFloat(2, 0, 1000),
            'billing_cycle' => fake()->randomElement(['monthly', 'yearly']),
            'status' => 'active',
        ];
    }
}
