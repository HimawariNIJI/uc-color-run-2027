<?php

namespace Database\Seeders;

use App\Models\TicketPrice;
use Illuminate\Database\Seeder;

/**
 * Writes the official registration packages from config("event.packages") to ticket_prices.
 *
 * Re-running updates the official rows in place (never duplicates them) and switches off any
 * package that is no longer official instead of deleting it, because registrations keep their
 * link to the package they bought. A registration's recorded amount never changes.
 */
class TicketPriceSeeder extends Seeder
{
    public function run(): void
    {
        $officialIds = collect(config('event.packages'))->map(function (array $package): int {
            $bundleType = $package['bundle_type'] ?? null;

            return TicketPrice::query()->updateOrCreate(
                [
                    'registration_type' => $package['registration_type'],
                    'bundle_type' => $bundleType,
                    'race_category' => $package['race_category'],
                    'price_type' => $package['price_type'] ?? null,
                ],
                [
                    'price_basis' => $bundleType === null ? 'per_person' : 'per_package',
                    'max_participants' => $package['max_participants'] ?? 1,
                    'price' => $package['price'],
                    'quota' => $package['quota'],
                    'is_active' => true,
                ],
            )->getKey();
        });

        TicketPrice::query()->whereNotIn('id', $officialIds)->where('is_active', true)->update(['is_active' => false]);
    }
}
