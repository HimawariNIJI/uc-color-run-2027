<?php

use App\Models\Registration;
use App\Models\User;

it('renders every public page', function (string $routeName, string $expectedText) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSee($expectedText);
})->with([
    'home' => ['home', 'Siap Berlari dan Bermandikan Warna?'],
    'about' => ['about', 'Apa itu UC Color Run?'],
    'faq' => ['faq', 'Frequently Asked Questions'],
    'privacy policy' => ['privacy', 'Privacy Policy'],
    'race category' => ['race.category', 'Race Category'],
    'race schedule' => ['race.schedule', 'See You on Race Day!'],
    'race regulation' => ['race.regulation', 'Race Regulation'],
    'contact' => ['contact', 'More Ways to Connect'],
    'sign in' => ['login', 'Sign In'],
]);

it('promotes early bird pricing while early bird slots are left', function () {
    config(['event.early_bird_slots' => ['3k' => 0, '5k' => 1, '10k' => 0]]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Slot Early Bird masih tersedia, amankan harga spesialmu sekarang.');
});

it('drops the early bird message once every early bird slot is taken', function () {
    config(['event.early_bird_slots' => ['3k' => 0, '5k' => 1, '10k' => 0]]);
    Registration::factory()->paid()->create(['category' => '5k']);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Slot Early Bird masih tersedia')
        ->assertSee('Daftar sekarang dan amankan slotmu di garis start.');
});

it('marks the running timeline milestone as current', function () {
    $this->travelTo('2027-01-05 10:00:00');

    $this->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Registration Period', 'Sedang berlangsung', 'Racepack Collection']);
});

it('shows the sign in button to guests and the account menu to participants', function () {
    $this->get(route('home'))
        ->assertSee(route('login'))
        ->assertDontSee('My Registration');

    $this->actingAs(User::factory()->create(['name' => 'Rina Wijaya']))
        ->get(route('home'))
        ->assertSee('Rina Wijaya')
        ->assertSee('My Registration')
        ->assertSee('Sign Out');
});
