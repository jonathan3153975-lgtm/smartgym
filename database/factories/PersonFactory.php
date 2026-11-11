<?php

namespace Database\Factories;

use App\Models\Person;
use App\Support\BrazilianDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $document = fake()->numerify('###########');

        return [
            'network_id' => null,
            'unit_id' => null,
            'person_type' => 'individual',
            'category' => 'client',
            'name' => fake()->name(),
            'trade_name' => null,
            'document' => BrazilianDocument::formatCpf($document),
            'document_raw' => $document,
            'state_registration' => null,
            'birth_date' => fake()->dateTimeBetween('-65 years', '-18 years'),
            'legal_representative_name' => null,
            'legal_representative_document' => null,
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'whatsapp' => null,
            'zip_code' => null,
            'street' => null,
            'number' => null,
            'complement' => null,
            'neighborhood' => null,
            'city' => null,
            'state' => null,
            'notes' => null,
            'status' => 'active',
        ];
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'person_type' => 'company',
            'name' => fake()->company().' LTDA',
            'trade_name' => fake()->company(),
            'document' => '11.222.333/0001-81',
            'document_raw' => '11222333000181',
            'birth_date' => null,
            'state_registration' => fake()->numerify('#########'),
            'legal_representative_name' => fake()->name(),
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'staff',
        ]);
    }
}
