<?php

use App\Models\Registration;
use App\Support\BrandAsset;

it('selects the 3K category by default', function () {
    $this->get(route('race.category'))
        ->assertOk()
        ->assertSeeInOrder([
            'id="category-tab-3k"', 'aria-selected="true"',
            'id="category-tab-5k"', 'aria-selected="false"',
            'id="category-tab-10k"', 'aria-selected="false"',
        ], false);
});

it('selects the category given in the query string', function () {
    $this->get(route('race.category', ['category' => '10k']))
        ->assertOk()
        ->assertSeeInOrder([
            'id="category-tab-3k"', 'aria-selected="false"',
            'id="category-tab-5k"', 'aria-selected="false"',
            'id="category-tab-10k"', 'aria-selected="true"',
        ], false);
});

it('lists the official packages of each distance with their quotas and prices from the database', function () {
    $this->get(route('race.category'))
        ->assertOk()
        ->assertDontSee(['Price To Be Announced', 'Harga resmi belum diumumkan.'])
        ->assertSeeInOrder([
            'id="category-panel-3k"',
            'Early Bid', 'Internal', 'Kuota 75 slot', 'Rp100.000', 'External', 'Kuota 50 slot', 'Rp100.000',
            'Normal Bid', 'Internal', 'Kuota 75 slot', 'Rp100.000', 'External', 'Kuota belum diumumkan', 'Rp100.000',
            'Family Bundle', 'Maks. 4 peserta', 'Kuota 20 paket', 'Rp100.000',
            'id="category-panel-5k"',
            'Early Bid', 'Internal', 'Kuota 100 slot', 'External', 'Kuota 100 slot',
            'Normal Bid', 'Internal', 'Kuota 150 slot', 'External', 'Kuota belum diumumkan',
            'Couple Bundle', 'Maks. 2 peserta', 'Kuota 50 paket',
            'id="category-panel-10k"',
            'Early Bid', 'Internal', 'Kuota 50 slot', 'External', 'Kuota 100 slot',
            'Normal Bid', 'Internal', 'Kuota 100 slot', 'External', 'Kuota belum diumumkan',
        ], false);
});

it('offers no bundle for 10K', function () {
    $response = $this->get(route('race.category'))->assertOk();
    $panel10k = str($response->getContent())->after('id="category-panel-10k"');

    expect((string) $panel10k)->not->toContain('Bundle');
});

it('shows the current database price, or Price To Be Announced when it is not set', function () {
    setPackage('external', 'early_bird', '5k', ['price' => 150000]);
    setPackage('bundling', null, '3k', ['price' => null]);

    $this->get(route('race.category'))
        ->assertOk()
        ->assertSeeInOrder(['id="category-panel-3k"', 'Family Bundle', 'Price To Be Announced', 'Harga resmi belum diumumkan.', 'id="category-panel-5k"', 'External', 'Rp150.000'], false);
});

it('counts taken slots per package and shows when one is sold out', function () {
    setPackage('internal', 'early_bird', '3k', ['quota' => 2]);
    setPackage('external', 'early_bird', '3k', ['quota' => 1]);
    Registration::factory()->create(['type' => 'internal', 'category' => '3k']);
    Registration::factory()->failed()->create(['type' => 'internal', 'category' => '3k']);
    Registration::factory()->paid()->create(['type' => 'external', 'category' => '3k']);

    $this->get(route('race.category'))
        ->assertOk()
        ->assertSeeInOrder(['id="category-panel-3k"', 'Early Bid', 'Internal', 'Sisa 1 dari 2 slot', 'External', 'Sold out'], false);
});

it('counts down to the configured race day', function () {
    $this->travelTo('2027-03-20 05:30:00');

    $this->get(route('race.schedule'))
        ->assertOk()
        ->assertSee('data-countdown="2027-03-21T05:30:00+07:00"', false)
        ->assertSee('data-countdown-unit="days">01</span>', false)
        ->assertSee('data-countdown-unit="hours">00</span>', false);
});

it('shows the official PDD photo on each category tab', function () {
    $photos = ['Race Category Page (3K).JPG', 'Race Category Page (5K).JPG', 'Race Category Page (10K).JPG'];

    foreach ($photos as $photo) {
        expect(public_path('assets/images/'.$photo))->toBeFile();
    }

    $this->get(route('race.category'))
        ->assertOk()
        ->assertSeeInOrder([
            'id="category-tab-3k"', 'src="'.BrandAsset::url($photos[0]).'"',
            'id="category-tab-5k"', 'src="'.BrandAsset::url($photos[1]).'"',
            'id="category-tab-10k"', 'src="'.BrandAsset::url($photos[2]).'"',
        ], false);
});
