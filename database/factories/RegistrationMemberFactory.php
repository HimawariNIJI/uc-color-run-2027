<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\TshirtSize;
use App\Models\Registration;
use App\Models\RegistrationMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationMember>
 */
class RegistrationMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory()->bundle(),
            'position' => 2,
            'full_name' => fake()->name(),
            'phone' => null,
            'birth_date' => fake()->dateTimeBetween('-60 years', '-8 years'),
            'gender' => fake()->randomElement(Gender::cases()),
            'identity_number' => fake()->numerify('################'),
            'tshirt_size' => fake()->randomElement(TshirtSize::cases()),
            'medical_history' => 'Tidak Ada',
            'drug_allergies' => 'Tidak Ada',
        ];
    }
}
