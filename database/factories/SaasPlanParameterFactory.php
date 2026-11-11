<?php

namespace Database\Factories;

use App\Models\SaasPlan;
use App\Models\SaasPlanParameter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SaasPlanParameter>
 */
class SaasPlanParameterFactory extends Factory
{
    protected $model = SaasPlanParameter::class;

    public function definition(): array
    {
        return [
            'saas_plan_id' => SaasPlan::factory(),
            'description' => fake()->words(3, true),
            'value' => fake()->randomFloat(2, 0, 5000),
        ];
    }
}
