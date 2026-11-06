<?php

use App\Enums\TshirtSize;
use App\Models\Registration;
use App\Models\User;

it('asks guests to sign in', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with(['my-registration.show', 'my-registration.edit']);

it('shows the empty state when the participant has not registered', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('my-registration.show'))
        ->assertOk()
        ->assertSee('No Registration Yet')
        ->assertSee(route('registration.type'));
});

it('shows a pending registration with payment actions', function () {
    $registration = Registration::factory()->create(['amount' => 150000]);

    $this->actingAs($registration->user)
        ->get(route('my-registration.show'))
        ->assertOk()
        ->assertSee(['Pending', 'Registration Submitted', $registration->code, 'Unpaid', 'Rp150.000', 'Pay Now', 'Edit Registration']);
});

it('shows a confirmed registration with a link to the BIB', function () {
    $registration = Registration::factory()->paid()->create();

    $this->actingAs($registration->user)
        ->get(route('my-registration.show'))
        ->assertOk()
        ->assertSee(['Registration Confirmed', 'View My BIB'])
        ->assertDontSee('Edit Registration');
});

it('updates an unpaid registration', function () {
    $registration = Registration::factory()->create(['tshirt_size' => TshirtSize::Small]);

    $this->actingAs($registration->user)
        ->put(route('my-registration.update'), registrationPayload(['tshirt_size' => 'XL', 'full_name' => 'Budi S.']))
        ->assertRedirect(route('my-registration.show'))
        ->assertSessionHas('success');

    expect($registration->fresh())
        ->tshirt_size->toBe(TshirtSize::ExtraLarge)
        ->full_name->toBe('Budi S.');
});

it('does not let participants change a paid registration', function () {
    $registration = Registration::factory()->paid()->create(['full_name' => 'Budi Santoso']);

    $this->actingAs($registration->user)
        ->get(route('my-registration.edit'))
        ->assertRedirect(route('my-registration.show'))
        ->assertSessionHas('error');

    $this->actingAs($registration->user)
        ->put(route('my-registration.update'), registrationPayload(['full_name' => 'Someone Else']))
        ->assertRedirect(route('my-registration.show'));

    expect($registration->fresh()->full_name)->toBe('Budi Santoso');
});

it('sends participants without a registration back to My Registration when editing', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('my-registration.edit'))
        ->assertRedirect(route('my-registration.show'));
});
