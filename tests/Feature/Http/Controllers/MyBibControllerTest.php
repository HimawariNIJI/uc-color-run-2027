<?php

use App\Models\Registration;
use App\Models\RegistrationMember;
use App\Models\User;
use App\Support\RacepackQr;

it('asks guests to sign in', function () {
    $this->get(route('my-bib.show'))->assertRedirect(route('login'));
});

it('explains the BIB is not available before payment', function (?string $state) {
    $user = $state === null
        ? User::factory()->create()
        : Registration::factory()->{$state}()->create()->user;

    $this->actingAs($user)
        ->get(route('my-bib.show'))
        ->assertOk()
        ->assertSee('Your BIB Is Not Available Yet')
        ->assertDontSee('Your BIB Number');
})->with([
    'not registered' => [null],
    'payment expired' => ['expired'],
    'payment failed' => ['failed'],
]);

it('shows the BIB number and the real racepack QR once paid, the same on every visit', function () {
    $registration = Registration::factory()->paid()->create(['bib_number' => '0520012']);

    $first = $this->actingAs($registration->user)
        ->get(route('my-bib.show'))
        ->assertOk()
        ->assertSee(['Your BIB Number', '0520012', 'Racepack QR-Code', 'BIB 0520012', $registration->code])
        ->assertSee(['data-racepack-qr="UCCR-0520012"', RacepackQr::svgFor($registration)], false)
        ->assertDontSee(['Coming Soon', 'BIB Pickup QR']);

    $again = $this->get(route('my-bib.show'));

    expect(str($again->getContent())->between('data-racepack-qr', '</svg>')->toString())
        ->toBe(str($first->getContent())->between('data-racepack-qr', '</svg>')->toString());
});

it('shows one racepack QR with every bundle participant', function () {
    $registration = Registration::factory()->bundle('family')->paid()->create(['bib_number' => '0350001']);

    foreach ([2 => '0350002', 3 => '0350003', 4 => '0350004'] as $position => $bib) {
        RegistrationMember::factory()->for($registration)->create(['position' => $position, 'bib_number' => $bib]);
    }

    $response = $this->actingAs($registration->user)
        ->get(route('my-bib.show'))
        ->assertSee('data-racepack-qr="UCCR-0350001;UCCR-0350002;UCCR-0350003;UCCR-0350004"', false)
        ->assertSeeInOrder(['BIB 0350001', 'BIB 0350002', 'BIB 0350003', 'BIB 0350004', 'satu QR untuk semua peserta']);

    expect(substr_count($response->getContent(), 'data-racepack-qr='))->toBe(1);
});

it('lists the BIB number of every bundle member', function () {
    $registration = Registration::factory()->bundle('family')->paid()->create(['bib_number' => '0350001']);
    RegistrationMember::factory()->for($registration)->create(['position' => 2, 'full_name' => 'Indah Wijaya', 'bib_number' => '0350002']);

    $this->actingAs($registration->user)
        ->get(route('my-bib.show'))
        ->assertSeeInOrder(['0350001', '0350002', 'Indah Wijaya']);
});
