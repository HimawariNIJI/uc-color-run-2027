<?php

use App\Models\Registration;
use App\Models\RegistrationMember;
use App\Models\User;

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

it('shows the BIB number and a coming-soon placeholder instead of the racepack QR code once paid', function () {
    $registration = Registration::factory()->paid()->create(['bib_number' => '50012']);

    $this->actingAs($registration->user)
        ->get(route('my-bib.show'))
        ->assertOk()
        ->assertSee(['Your BIB Number', '50012', 'Racepack QR-Code', 'BIB Pickup QR', 'Coming Soon', $registration->code])
        ->assertDontSee($registration->racepackPayload());
});

it('lists the BIB number of every bundle member', function () {
    $registration = Registration::factory()->bundle('family')->paid()->create(['bib_number' => '0300040001']);
    RegistrationMember::factory()->for($registration)->create(['position' => 2, 'full_name' => 'Indah Wijaya', 'bib_number' => '0300040002']);

    $this->actingAs($registration->user)
        ->get(route('my-bib.show'))
        ->assertSeeInOrder(['0300040001', '0300040002', 'Indah Wijaya']);
});
