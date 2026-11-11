<?php

namespace Database\Factories;

use App\Models\Network;
use App\Models\Region;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        $network = Network::inRandomOrder()->first() ?? Network::factory()->create();

        return [
            'network_id' => $network->id,
            'region_id' => Region::where('network_id', $network->id)->inRandomOrder()->first()?->id ?? Region::factory()->create(['network_id' => $network->id])->id,
            'name' => fake()->unique()->company().' '.fake()->citySuffix(),
            'identifier' => fake()->unique()->bothify('UNID###'),
            'address' => fake()->streetAddress(),
            'zip_code' => fake()->numerify('#####-###'),
            'street' => fake()->streetName(),
            'number' => fake()->buildingNumber(),
            'complement' => fake()->optional()->secondaryAddress(),
            'neighborhood' => fake()->citySuffix(),
            'city' => fake()->city(),
            'state' => fake()->stateAbbr(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'status' => 'active',
            'settings' => json_encode([]),
        ];
    }
}
