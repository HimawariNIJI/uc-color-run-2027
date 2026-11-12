<?php

use App\Models\TicketPrice;
use App\Models\User;
use App\Support\BrandAsset;

it('renders every public page', function (string $routeName, string $expectedText) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSee($expectedText);
})->with([
    'home' => ['home', 'Siap Berlari, Berwarna, dan Berpesta Bersama?'],
    'about' => ['about', 'Apa itu UC Color Run?'],
    'faq' => ['faq', 'Frequently Asked Questions'],
    'privacy policy' => ['privacy', 'Privacy Policy'],
    'race category' => ['race.category', 'Race Category'],
    'race schedule' => ['race.schedule', 'See You on Race Day!'],
    'race regulation' => ['race.regulation', 'Race Regulation'],
    'contact' => ['contact', 'More Ways to Connect'],
    'sign in' => ['login', 'Sign In'],
]);

it('says prices are coming while no package has a price', function () {
    TicketPrice::query()->update(['price' => null]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Paket dan kuota resmi sudah diumumkan. Harga akan segera diumumkan, pantau terus!')
        ->assertDontSee('Slot Early Bid masih tersedia');
});

it('promotes Early Bid while an Early Bid package is open', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Slot Early Bid masih tersedia, amankan harga spesialmu sekarang.');
});

it('drops the Early Bid message once every Early Bid package is full', function () {
    TicketPrice::query()->where('price_type', 'early_bird')->update(['quota' => 0]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Slot Early Bid masih tersedia')
        ->assertSee('Daftar sekarang dan amankan slotmu di garis start.');
});

it('shows the official event timeline and Race Day', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Minggu, 11 April 2027')
        ->assertSeeInOrder([
            '5 Desember 2026 – 28 Maret 2027', 'Registration Period',
            '8–10 April 2027', 'Racepack Collection',
            '11 April 2027', 'Race Day',
        ]);
});

it('marks the running timeline milestone as current', function (string $now, ?string $current) {
    $this->travelTo($now);

    $response = $this->get(route('home'))->assertOk();

    $current === null
        ? $response->assertDontSee('Sedang berlangsung')
        : $response->assertSeeInOrder([$current, 'Sedang berlangsung']);
})->with([
    'before registration opens' => ['2026-12-04 23:59:00', null],
    'first day of registration' => ['2026-12-05 00:00:00', 'Registration Period'],
    'last day of registration' => ['2027-03-28 23:59:00', 'Registration Period'],
    'between registration and racepack collection' => ['2027-04-01 10:00:00', null],
    'racepack collection' => ['2027-04-10 18:00:00', 'Racepack Collection'],
    'race day' => ['2027-04-11 07:00:00', 'Race Day'],
]);

it('shows the official contact channels in the footer of every public page', function (string $routeName) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSeeInOrder([
            '<footer',
            'href="mailto:uccolorrun@gmail.com"', 'uccolorrun@gmail.com',
            'href="https://line.me/R/ti/p/@971xdxwx" target="_blank" rel="noopener noreferrer"', '@971xdxwx',
            'href="https://www.instagram.com/uc.colorun/" target="_blank" rel="noopener noreferrer"', '@uc.colorun',
        ], false)
        ->assertDontSee(['wa.me', '812-3456-7890', 'uccolorrun@ciputra.ac.id'], false);
})->with(['home', 'about', 'faq', 'contact']);

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
