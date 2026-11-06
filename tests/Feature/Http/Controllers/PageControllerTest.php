<?php

use App\Models\Registration;
use App\Models\User;
use App\Support\BrandAsset;

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

it('shows the official PDD photo in each page section', function (string $routeName, array $photos) {
    foreach ($photos as $photo) {
        expect(public_path('assets/images/'.$photo))->toBeFile();
    }

    $this->get(route($routeName))
        ->assertOk()
        ->assertSeeInOrder(array_map(fn (string $photo): string => 'src="'.BrandAsset::url($photo).'"', $photos), false);
})->with([
    'about' => ['about', [
        'About Page (Apa itu UC Color Run).JPG',
        'About Page (Latar Belakang).JPG',
        'About Page (Come & Join Us).JPG',
    ]],
    'contact' => ['contact', ['Contact Us Page.JPG']],
]);

it('embeds the recap video from YouTube without autoplay', function () {
    config(['event.recap_video_url' => 'https://youtu.be/HEm8BXHGUTE']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('src="https://www.youtube-nocookie.com/embed/HEm8BXHGUTE?rel=0"', false)
        ->assertSee('title="Video recap UC Color Run 2026"', false)
        ->assertDontSee('autoplay', false);
});

it('shows a placeholder when no recap video is configured', function () {
    config(['event.recap_video_url' => null]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('<iframe', false)
        ->assertSee('Video recap segera hadir');
});
