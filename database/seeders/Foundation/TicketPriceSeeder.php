<?php

namespace Database\Seeders\Foundation;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Every registration option with its price and quota.
 *
 * PLACEHOLDER VALUES: the committee has not confirmed prices or quotas yet. Per-person prices are
 * the website's current placeholder prices (config/event.php); a bundling package costs the
 * bundling per-person price times the package size. Replace them before launch.
 *
 * Re-running updates price and quota, but never sold_quantity, reserved_quantity or is_active,
 * which change with real registrations and admin decisions.
 */
class TicketPriceSeeder extends Seeder
{
    /**
     * PLACEHOLDER per-person prices in Rupiah, by registration type, race category and price type.
     *
     * @var array<string, array<string, array{early_bird: int, normal: int}>>
     */
    private const PRICES_PER_PERSON = [
        'internal' => [
            '3k' => ['early_bird' => 100000, 'normal' => 125000],
            '5k' => ['early_bird' => 125000, 'normal' => 150000],
            '10k' => ['early_bird' => 175000, 'normal' => 200000],
        ],
        'external' => [
            '3k' => ['early_bird' => 125000, 'normal' => 150000],
            '5k' => ['early_bird' => 150000, 'normal' => 175000],
            '10k' => ['early_bird' => 200000, 'normal' => 225000],
        ],
        'bundling' => [
            '3k' => ['early_bird' => 115000, 'normal' => 140000],
            '5k' => ['early_bird' => 140000, 'normal' => 165000],
            '10k' => ['early_bird' => 190000, 'normal' => 215000],
        ],
    ];

    /**
     * PLACEHOLDER number of people in each bundling package.
     *
     * @var array<string, int>
     */
    public const PACKAGE_SIZES = ['couple' => 2, 'family' => 4];

    /**
     * PLACEHOLDER quotas: people for per-person options, packages for per-package options.
     *
     * @var array<string, array{early_bird: int, normal: int}>
     */
    private const QUOTAS = [
        'per_person' => ['early_bird' => 100, 'normal' => 500],
        'per_package' => ['early_bird' => 25, 'normal' => 100],
    ];

    public function run(): void
    {
        if (! FoundationSeeder::schemaIsReady()) {
            $this->command?->error(FoundationSeeder::MISSING_SCHEMA_MESSAGE);

            return;
        }

        foreach (self::options() as ['key' => $key, 'values' => $values]) {
            DB::table('ticket_prices')->updateOrInsert(
                $key,
                fn (bool $exists): array => $values + ['updated_at' => now()] + ($exists ? [] : ['is_active' => true, 'created_at' => now()]),
            );
        }
    }

    /**
     * Internal and external options are sold per person, bundling options per package.
     *
     * @return list<array{key: array{registration_type: string, bundle_type: ?string, race_category: string, price_type: string}, values: array{price_basis: string, price: int, quota: int}}>
     */
    private static function options(): array
    {
        $options = [];

        foreach (self::PRICES_PER_PERSON as $registrationType => $categories) {
            $bundleTypes = $registrationType === 'bundling' ? array_keys(self::PACKAGE_SIZES) : [null];

            foreach ($bundleTypes as $bundleType) {
                $priceBasis = $bundleType === null ? 'per_person' : 'per_package';
                $peoplePerUnit = $bundleType === null ? 1 : self::PACKAGE_SIZES[$bundleType];

                foreach ($categories as $raceCategory => $prices) {
                    foreach ($prices as $priceType => $pricePerPerson) {
                        $options[] = [
                            'key' => [
                                'registration_type' => $registrationType,
                                'bundle_type' => $bundleType,
                                'race_category' => $raceCategory,
                                'price_type' => $priceType,
                            ],
                            'values' => [
                                'price_basis' => $priceBasis,
                                'price' => $pricePerPerson * $peoplePerUnit,
                                'quota' => self::QUOTAS[$priceBasis][$priceType],
                            ],
                        ];
                    }
                }
            }
        }

        return $options;
    }
}
