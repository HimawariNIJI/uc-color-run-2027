<?php

namespace Database\Factories;

use App\Enums\FeePeriod;
use App\Enums\Gender;
use App\Enums\IdentityDocument;
use App\Enums\PaymentStatus;
use App\Enums\RaceCategory;
use App\Enums\RegistrationType;
use App\Enums\TshirtSize;
use App\Models\Admin;
use App\Models\Registration;
use App\Models\TicketPrice;
use App\Models\User;
use App\Support\BibNumber;
use App\Support\IdentityPhoto;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

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
        $type = fake()->randomElement([RegistrationType::Internal, RegistrationType::External]);
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
            'tshirt_size' => fake()->randomElement(TshirtSize::cases()),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_phone' => '08'.fake()->numerify('##########'),
            'medical_history' => 'Tidak Ada',
            'drug_allergies' => 'Tidak Ada',
            'province' => 'Jawa Timur',
            'city' => 'Kota Surabaya',
            'address' => fake()->streetAddress(),
            'amount' => 100000,
            'fee_period' => FeePeriod::EarlyBird,
            'payment_status' => PaymentStatus::Unpaid,
            'payment_expires_at' => now()->addHour(),
        ];
    }

    /**
     * Link each registration to the seeded package matching its type, price type and distance, and
     * give paid ones a 7-digit BIB of that package. Factory BIBs use sequences from 5000 up, so they
     * never collide with numbers issued through the real payment flow in the same test.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Registration $registration): void {
            if ($registration->ticket_price_id === null) {
                $priceType = $registration->type === RegistrationType::Bundling ? null : $registration->fee_period;
                $registration->ticket_price_id = TicketPrice::findOption($registration->type, $priceType, $registration->category)?->getKey();
            }

            $package = $registration->package();

            if ($registration->isPaid() && $registration->bib_number === null && $package !== null) {
                $registration->bib_number = BibNumber::make($package->bibPrefix(), fake()->unique()->numberBetween(5000, BibNumber::MAX_SEQUENCE));
            }
        });
    }

    /**
     * A Family Bundle (3K) or Couple Bundle (5K) registration.
     */
    public function bundle(string $bundleType = 'family'): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => RegistrationType::Bundling,
            'category' => $bundleType === 'family' ? RaceCategory::ThreeK : RaceCategory::FiveK,
            'fee_period' => null,
        ]);
    }

    /**
     * With a KTP photo on the private identity photo disk (fake that disk in tests).
     */
    public function withKtpPhoto(): static
    {
        return $this->state(fn (array $attributes) => [
            'ktp_photo_path' => UploadedFile::fake()->image('ktp.jpg', 856, 540)->store(IdentityDocument::Ktp->value, IdentityPhoto::DISK),
        ]);
    }

    /**
     * A Family Bundle with a photo of the family's Kartu Keluarga (fake the photo disk in tests).
     */
    public function withFamilyCardPhoto(): static
    {
        return $this->bundle('family')->state(fn (array $attributes) => [
            'family_card_photo_path' => UploadedFile::fake()->image('kk.jpg', 1100, 780)->store(IdentityDocument::FamilyCard->value, IdentityPhoto::DISK),
        ]);
    }

    /**
     * Made before the KTP photo and medical fields existed: a typed KTP/Passport number instead.
     */
    public function beforeKtpPhoto(): static
    {
        return $this->state(fn (array $attributes) => [
            'identity_number' => fake()->numerify('################'),
            'ktp_photo_path' => null,
            'medical_history' => null,
            'drug_allergies' => null,
        ]);
    }

    /**
     * Registered for a Normal Bid package instead of Early Bid.
     */
    public function normalPrice(): static
    {
        return $this->state(fn (array $attributes) => [
            'fee_period' => FeePeriod::Regular,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_status' => PaymentStatus::Paid,
            'payment_expires_at' => null,
            'paid_at' => now(),
            'transaction_id' => 'PAY-'.fake()->unique()->bothify('UCR-2027-?????'),
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
