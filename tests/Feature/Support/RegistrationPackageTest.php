<?php

use App\Enums\FeePeriod;
use App\Enums\RaceCategory;
use App\Enums\RegistrationType;
use App\Models\Registration;
use App\Models\TicketPrice;
use Database\Seeders\TicketPriceSeeder;

it('stores exactly the 14 official packages, each at the temporary Rp100.000 per package', function () {
    $packages = TicketPrice::catalogue()->map(fn (TicketPrice $package): array => [
        $package->name(), $package->eligibility(), $package->quota, $package->max_participants, $package->price_basis, $package->price,
    ])->all();

    expect($packages)->toBe([
        ['3K Early Bid', 'Internal', 75, 1, 'per_person', 100000],
        ['3K Early Bid', 'External', 50, 1, 'per_person', 100000],
        ['3K Normal Bid', 'Internal', 75, 1, 'per_person', 100000],
        ['3K Normal Bid', 'External', null, 1, 'per_person', 100000],
        ['3K Family Bundle', 'Maks. 4 peserta', 20, 4, 'per_package', 100000],
        ['5K Couple Bundle', 'Maks. 2 peserta', 50, 2, 'per_package', 100000],
        ['5K Early Bid', 'Internal', 100, 1, 'per_person', 100000],
        ['5K Early Bid', 'External', 100, 1, 'per_person', 100000],
        ['5K Normal Bid', 'Internal', 150, 1, 'per_person', 100000],
        ['5K Normal Bid', 'External', null, 1, 'per_person', 100000],
        ['10K Early Bid', 'Internal', 50, 1, 'per_person', 100000],
        ['10K Early Bid', 'External', 100, 1, 'per_person', 100000],
        ['10K Normal Bid', 'Internal', 100, 1, 'per_person', 100000],
        ['10K Normal Bid', 'External', null, 1, 'per_person', 100000],
    ]);
});

it('never duplicates packages when the seeder runs again, and switches off outdated ones', function () {
    $outdated = TicketPrice::query()->create([
        'registration_type' => 'bundling', 'bundle_type' => 'family', 'race_category' => '10k', 'price_type' => null,
        'price_basis' => 'per_package', 'max_participants' => 4, 'price' => 90000, 'quota' => 10,
    ]);

    $this->seed(TicketPriceSeeder::class);
    $this->seed(TicketPriceSeeder::class);

    expect(TicketPrice::query()->where('is_active', true)->count())->toBe(14)
        ->and(TicketPrice::query()->count())->toBe(15)
        ->and($outdated->fresh()->is_active)->toBeFalse()
        ->and(TicketPrice::catalogue()->pluck('id'))->not->toContain($outdated->id);
});

it('never treats a missing quota as zero or as unlimited', function () {
    $package = TicketPrice::findOption(RegistrationType::External, FeePeriod::Regular, RaceCategory::ThreeK);

    expect($package->price)->toBe(100000)
        ->and($package->remaining())->toBeNull()
        ->and($package->isSoldOut())->toBeFalse()
        ->and($package->isOpen())->toBeFalse()
        ->and($package->quotaLabel())->toBe('Kuota belum diumumkan');
});

it('keeps a package without a price closed and shows Price To Be Announced', function () {
    setPackage('internal', 'early_bird', '3k', ['price' => null]);
    $package = TicketPrice::findOption(RegistrationType::Internal, FeePeriod::EarlyBird, RaceCategory::ThreeK);

    expect($package->isOpen())->toBeFalse()
        ->and($package->priceLabel())->toBe('Price To Be Announced');
});

it('counts one bundle registration as one package, and only while it holds a slot', function () {
    Registration::factory()->bundle('family')->create();
    Registration::factory()->bundle('family')->paid()->create();
    Registration::factory()->bundle('family')->failed()->create();
    Registration::factory()->bundle('family')->expired()->create();
    Registration::factory()->bundle('couple')->create();

    $family = TicketPrice::findBundle('family');

    expect($family->takenSlots())->toBe(2)
        ->and($family->remaining())->toBe(18);
});

it('finds no package for options that are not official', function () {
    expect(TicketPrice::findOption(RegistrationType::Bundling, null, RaceCategory::TenK))->toBeNull()
        ->and(TicketPrice::findOption(RegistrationType::Bundling, FeePeriod::EarlyBird, RaceCategory::ThreeK))->toBeNull()
        ->and(TicketPrice::findBundle('trio'))->toBeNull();
});

it('uses the price in the database, so a changed price applies to the next registration', function () {
    setPackage('external', 'early_bird', '5k', ['price' => 135000]);

    expect(TicketPrice::findOption(RegistrationType::External, FeePeriod::EarlyBird, RaceCategory::FiveK)->priceLabel())->toBe('Rp135.000');
});
