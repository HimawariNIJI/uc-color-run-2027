<?php

use App\Models\Registration;

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

it('lists early bird and normal prices for every registration type', function () {
    config(['event.pricing.external.5k' => ['early_bird' => 150000, 'regular' => 175000]]);

    $this->get(route('race.category'))
        ->assertOk()
        ->assertSee(['Early Bird', 'Normal Price', 'Rp150.000', 'Rp175.000']);
});

it('shows the early bird slots left for each distance', function () {
    config(['event.early_bird_slots' => ['3k' => 300, '5k' => 1, '10k' => 200]]);
    Registration::factory()->create(['category' => '3k']);
    Registration::factory()->create(['category' => '5k']);

    $this->get(route('race.category'))
        ->assertOk()
        ->assertSeeInOrder(['Sisa 299 slot', 'Early Bird terbatas 300 slot untuk 3 KM. Setelah slot habis, berlaku Normal Price.'])
        ->assertSeeInOrder(['id="category-panel-5k"', 'Sold out'], false);
});

it('counts down to the configured race day', function () {
    $this->travelTo('2027-03-20 05:30:00');

    $this->get(route('race.schedule'))
        ->assertOk()
        ->assertSee('data-countdown="2027-03-21T05:30:00+07:00"', false)
        ->assertSee('data-countdown-unit="days">01</span>', false)
        ->assertSee('data-countdown-unit="hours">00</span>', false);
});
