<?php

namespace Database\Factories;

use App\Models\Network;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        $network = Network::inRandomOrder()->first() ?? Network::factory()->create();
        $cpf = fake()->unique()->numerify('###########');

        return [
            'network_id' => $network->id,
            'name' => fake()->name(),
            'social_name' => fake()->optional()->name(),
            'cpf' => preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf),
            'cpf_raw' => $cpf,
            'birth_date' => fake()->dateTimeBetween('-70 years', '-18 years'),
            'gender' => fake()->randomElement(['male', 'female', 'other', 'prefer_not_to_say']),
            'email' => fake()->optional()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'whatsapp' => fake()->optional()->phoneNumber(),
            'address' => fake()->optional()->streetAddress(),
            'zip_code' => fake()->optional()->numerify('#####-###'),
            'street' => fake()->optional()->streetName(),
            'number' => fake()->optional()->buildingNumber(),
            'complement' => fake()->optional()->secondaryAddress(),
            'neighborhood' => fake()->optional()->citySuffix(),
            'city' => fake()->optional()->city(),
            'state' => fake()->optional()->stateAbbr(),
            'emergency_contact_name' => fake()->optional()->name(),
            'emergency_contact_phone' => fake()->optional()->phoneNumber(),
            'emergency_contact_relationship' => fake()->optional()->randomElement(['Mãe', 'Pai', 'Cônjuge', 'Responsável', 'Irmão', 'Outro']),
            'avatar' => null,
            'admin_notes' => fake()->optional()->paragraph(),
            'source' => fake()->optional()->randomElement(['walk_in', 'online', 'referral', 'campaign', 'partnership']),
            'status' => fake()->randomElement(['active', 'inactive', 'pending']),
            'created_by' => User::inRandomOrder()->first()?->id ?? User::factory(),
            'updated_by' => null,
        ];
    }
}
