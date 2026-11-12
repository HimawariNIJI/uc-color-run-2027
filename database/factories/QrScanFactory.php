<?php

namespace Database\Factories;

use App\Enums\QrScanRejection;
use App\Enums\QrScanSource;
use App\Enums\QrScanStatus;
use App\Models\Admin;
use App\Models\QrScan;
use App\Models\Registration;
use App\Support\RacepackQr;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QrScan>
 */
class QrScanFactory extends Factory
{
    /**
     * An accepted camera scan of a paid registration that handed over its BIB.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory()->paid(),
            'admin_id' => Admin::factory(),
            'status' => QrScanStatus::Accepted,
            'source' => QrScanSource::Camera,
            'bib_handed_over' => true,
            'scanned_at' => now(),
        ];
    }

    /**
     * Use the registration's real racepack QR payload.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (QrScan $scan): void {
            if ($scan->payload === null && $scan->registration !== null) {
                $scan->forceFill(['payload' => RacepackQr::payloadFor($scan->registration)])->save();
            }
        });
    }

    public function rejected(QrScanRejection $reason = QrScanRejection::Malformed): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_id' => null,
            'status' => QrScanStatus::Rejected,
            'rejection_reason' => $reason,
            'bib_handed_over' => false,
        ]);
    }
}
