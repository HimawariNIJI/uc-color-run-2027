<?php

use App\Enums\FeePeriod;
use App\Enums\PaymentStatus;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

it('asks guests to sign in', function () {
    $this->get(route('payment.show'))->assertRedirect(route('login'));
});

it('sends participants without a registration to My Registration', function (string $method, string $routeName) {
    $this->actingAs(User::factory()->create())
        ->call($method, route($routeName))
        ->assertRedirect(route('my-registration.show'));
})->with([
    ['GET', 'payment.show'],
    ['POST', 'payment.check'],
    ['POST', 'payment.retry'],
    ['GET', 'payment.success'],
    ['GET', 'payment.failed'],
]);

it('shows the QRIS payment page with the registration summary', function () {
    $registration = Registration::factory()->create(['full_name' => 'Budi Santoso', 'amount' => 150000]);

    $this->actingAs($registration->user)
        ->get(route('payment.show'))
        ->assertOk()
        ->assertSee(['Selesaikan Pembayaran', 'Budi Santoso', $registration->raceLabel(), 'Rp150.000', 'Bayar dengan QRIS', 'Cek Status Pembayaran'])
        ->assertDontSee('Simulasi pembayaran');
});

it('opens a new payment window when the previous one expired', function () {
    $this->freezeSecond();
    $registration = Registration::factory()->expired()->create();

    $this->actingAs($registration->user)->get(route('payment.show'))->assertOk();

    expect($registration->fresh()->payment_expires_at->equalTo(now()->addMinutes(60)))->toBeTrue();
});

it('keeps the participant on the payment page while the payment is pending', function () {
    $registration = Registration::factory()->create();

    $this->actingAs($registration->user)
        ->post(route('payment.check'))
        ->assertRedirect(route('payment.show'))
        ->assertSessionHas('info');

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('marks an expired payment as failed when checking its status', function () {
    $registration = Registration::factory()->expired()->create();

    $this->actingAs($registration->user)
        ->post(route('payment.check'))
        ->assertRedirect(route('payment.failed'));

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Failed);
});

it('sends paid participants to the success page', function (string $method, string $routeName) {
    $registration = Registration::factory()->paid()->create();

    $this->actingAs($registration->user)
        ->call($method, route($routeName))
        ->assertRedirect(route('payment.success'));
})->with([
    ['GET', 'payment.show'],
    ['POST', 'payment.check'],
    ['POST', 'payment.retry'],
    ['GET', 'payment.failed'],
]);

it('shows the success page once paid', function () {
    $registration = Registration::factory()->paid()->create();

    $this->actingAs($registration->user)
        ->get(route('payment.success'))
        ->assertOk()
        ->assertSee(['Pembayaran Berhasil!', 'LUNAS', 'Registrasi Terkonfirmasi']);
});

it('keeps unpaid participants away from the success page', function () {
    $registration = Registration::factory()->create();

    $this->actingAs($registration->user)
        ->get(route('payment.success'))
        ->assertRedirect(route('payment.show'));
});

it('shows the failed page for a failed payment', function () {
    $registration = Registration::factory()->failed()->create();

    $this->actingAs($registration->user)
        ->get(route('payment.failed'))
        ->assertOk()
        ->assertSee(['Pembayaran Gagal', 'BELUM LUNAS', 'Coba Bayar Lagi']);

    $this->actingAs($registration->user)
        ->get(route('payment.show'))
        ->assertRedirect(route('payment.failed'));
});

it('keeps participants with a pending payment away from the failed page', function () {
    $registration = Registration::factory()->create();

    $this->actingAs($registration->user)
        ->get(route('payment.failed'))
        ->assertRedirect(route('payment.show'));
});

it('restarts a failed payment', function () {
    $this->freezeSecond();
    $registration = Registration::factory()->failed()->create();

    $this->actingAs($registration->user)
        ->post(route('payment.retry'))
        ->assertRedirect(route('payment.show'));

    expect($registration->fresh())
        ->payment_status->toBe(PaymentStatus::Unpaid)
        ->and($registration->fresh()->payment_expires_at->equalTo(now()->addMinutes(60)))->toBeTrue();
});

it('keeps the early bird price when retrying while a slot is free', function () {
    config(['event.early_bird_slots.5k' => 1, 'event.pricing.external.5k.early_bird' => 150000]);
    $registration = Registration::factory()->failed()->create(['type' => 'external', 'category' => '5k', 'amount' => 150000]);

    $this->actingAs($registration->user)
        ->post(route('payment.retry'))
        ->assertRedirect(route('payment.show'))
        ->assertSessionMissing('info');

    expect($registration->fresh())
        ->fee_period->toBe(FeePeriod::EarlyBird)
        ->amount->toBe(150000)
        ->payment_status->toBe(PaymentStatus::Unpaid);
});

it('moves a failed early bird registration to the normal price when the slots ran out', function () {
    config(['event.early_bird_slots.5k' => 1, 'event.pricing.external.5k' => ['early_bird' => 150000, 'regular' => 175000]]);
    $registration = Registration::factory()->failed()->create(['type' => 'external', 'category' => '5k', 'amount' => 150000]);
    Registration::factory()->paid()->create(['category' => '5k']);

    $this->actingAs($registration->user)
        ->post(route('payment.retry'))
        ->assertRedirect(route('payment.show'))
        ->assertSessionHas('info', 'Slot Early Bird untuk 5 KM sudah habis, sehingga biaya registrasimu disesuaikan ke Normal Price (Rp175.000).');

    expect($registration->fresh())
        ->fee_period->toBe(FeePeriod::Regular)
        ->amount->toBe(175000)
        ->payment_status->toBe(PaymentStatus::Unpaid);
});

describe('local payment simulation', function () {
    it('confirms the payment and issues a BIB number', function () {
        $this->app['env'] = 'local';
        $this->withoutMiddleware(PreventRequestForgery::class);
        $registration = Registration::factory()->create(['category' => '5k']);

        $this->actingAs($registration->user)
            ->post(route('payment.simulate', 'paid'))
            ->assertRedirect(route('payment.success'));

        expect($registration->fresh())
            ->payment_status->toBe(PaymentStatus::Paid)
            ->paid_at->not->toBeNull()
            ->bib_number->toBe('5'.str_pad((string) $registration->id, 4, '0', STR_PAD_LEFT));
    });

    it('records a transaction ID for the confirmed payment', function () {
        $this->app['env'] = 'local';
        $this->withoutMiddleware(PreventRequestForgery::class);
        $registration = Registration::factory()->create();

        $this->actingAs($registration->user)->post(route('payment.simulate', 'paid'));

        expect($registration->fresh()->transaction_id)->toBe('PAY-'.$registration->code);
    });

    it('fails the payment', function () {
        $this->app['env'] = 'local';
        $this->withoutMiddleware(PreventRequestForgery::class);
        $registration = Registration::factory()->create();

        $this->actingAs($registration->user)
            ->post(route('payment.simulate', 'failed'))
            ->assertRedirect(route('payment.failed'));

        expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Failed);
    });

    it('is offered on the payment page in the local environment', function () {
        $this->app['env'] = 'local';
        $this->withoutMiddleware(PreventRequestForgery::class);
        $registration = Registration::factory()->create();

        $this->actingAs($registration->user)
            ->get(route('payment.show'))
            ->assertSee('Simulasi pembayaran');
    });

    it('returns 404 outside the local environment', function () {
        $registration = Registration::factory()->create();

        $this->actingAs($registration->user)
            ->post(route('payment.simulate', 'paid'))
            ->assertNotFound();

        expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
    });
});
