<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state: a scannable QRIS waiting for payment.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'order_id' => 'UCCR-TEST-'.fake()->unique()->numerify('######'),
            'gateway_transaction_id' => fake()->uuid(),
            'amount' => 100000,
            'status' => TransactionStatus::Pending,
            'qr_string' => '00020101021226620014COM.GO-JEK.WWW011993600914'.fake()->numerify('##########'),
            'qr_url' => 'https://api.sandbox.midtrans.com/v2/qris/test/qr-code',
            'expires_at' => now()->addHour(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['expires_at' => now()->subMinute()]);
    }
}
