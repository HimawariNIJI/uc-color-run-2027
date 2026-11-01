<?php

namespace Database\Factories;

use App\Enums\FeePeriod;
use App\Enums\Gender;
use App\Enums\PaymentStatus;
use App\Enums\RaceCategory;
use App\Enums\RegistrationType;
use App\Enums\TshirtSize;
use App\Models\Admin;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Registration>
 */
class RegistrationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(RegistrationType::cases());
        $category = fake()->randomElement(RaceCategory::cases());

        return [
            'user_id' => User::factory(),
            'type' => $type,
            'category' => $category,
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '08'.fake()->numerify('##########'),
            'birth_date' => fake()->dateTimeBetween('-50 years', '-17 years'),
            'gender' => fake()->randomElement(Gender::cases()),
            'identity_number' => fake()->numerify('################'),
            'tshirt_size' => fake()->randomElement(TshirtSize::cases()),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => '08'.fake()->numerify('##########'),
            'province' => 'Jawa Timur',
            'city' => 'Kota Surabaya',
            'address' => fake()->streetAddress(),
            'amount' => $category->feeFor($type, FeePeriod::EarlyBird),
            'fee_period' => FeePeriod::EarlyBird,
            'payment_status' => PaymentStatus::Unpaid,
            'payment_expires_at' => now()->addHour(),
        ];
    }

    /**
     * Registered at the Normal Price instead of Early Bird.
     */
    public function normalPrice(): static
    {
        return $this->state(function (array $attributes): array {
            $category = $attributes['category'] instanceof RaceCategory ? $attributes['category'] : RaceCategory::from($attributes['category']);
            $type = $attributes['type'] instanceof RegistrationType ? $attributes['type'] : RegistrationType::from($attributes['type']);

            return [
                'fee_period' => FeePeriod::Regular,
                'amount' => $category->feeFor($type, FeePeriod::Regular),
            ];
        });
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::Paid,
            'payment_expires_at' => null,
            'paid_at' => now(),
            'transaction_id' => 'PAY-'.fake()->unique()->bothify('UCR-2027-?????'),
            'bib_number' => fake()->unique()->numerify('5####'),
        ]);
    }

    /**
     * Paid, with the BIB already handed over at racepack collection.
     */
    public function bibCollected(?Admin $admin = null): static
    {
        return $this->paid()->state(fn (array $attributes) => [
            'bib_collected_at' => now(),
            'bib_collected_by' => $admin ?? Admin::factory(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::Failed,
            'payment_expires_at' => null,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::Unpaid,
            'payment_expires_at' => now()->subMinute(),
        ]);
    }
}
