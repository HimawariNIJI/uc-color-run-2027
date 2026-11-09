<?php

namespace Database\Seeders\Foundation;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The 14 official registration packages (Executive Chair, 8 November 2026), read from
 * config("event.packages") so the website and the database always list the same packages.
 *
 * Prices stay NULL until they are announced, and quotas that were not announced stay NULL.
 * Re-running updates the official rows in place (never duplicates them), switches off any
 * package that is no longer official instead of deleting it (registrations may still refer to
 * it), and never touches sold_quantity or reserved_quantity.
 */
class TicketPriceSeeder extends Seeder
{
    public function run(): void
    {
        if (! FoundationSeeder::schemaIsReady()) {
            $this->command?->error(FoundationSeeder::MISSING_SCHEMA_MESSAGE);

            return;
        }

        $officialIds = [];

        foreach (self::packages() as ['key' => $key, 'values' => $values]) {
            DB::table('ticket_prices')->updateOrInsert(
                $key,
                fn (bool $exists): array => $values + ['updated_at' => now()] + ($exists ? [] : ['is_active' => true, 'created_at' => now()]),
            );

            $officialIds[] = DB::table('ticket_prices')->where($key)->value('id');
        }

        DB::table('ticket_prices')
            ->whereNotIn('id', $officialIds)
            ->where('is_active', true)
            ->update(['is_active' => false, 'updated_at' => now()]);
    }

    /**
     * Individual packages are sold per person; bundles per package, for up to max_participants people.
     *
     * @return list<array{key: array{registration_type: string, bundle_type: ?string, race_category: string, price_type: ?string}, values: array{price_basis: string, max_participants: int, price: ?int, quota: ?int}}>
     */
    private static function packages(): array
    {
        return collect(config('event.packages'))->map(function (array $package): array {
            $bundleType = $package['bundle_type'] ?? null;

            return [
                'key' => [
                    'registration_type' => $package['registration_type'],
                    'bundle_type' => $bundleType,
                    'race_category' => $package['race_category'],
                    'price_type' => $package['price_type'] ?? null,
                ],
                'values' => [
                    'price_basis' => $bundleType === null ? 'per_person' : 'per_package',
                    'max_participants' => $package['max_participants'] ?? 1,
                    'price' => $package['price'],
                    'quota' => $package['quota'],
                ],
            ];
        })->all();
    }
}
