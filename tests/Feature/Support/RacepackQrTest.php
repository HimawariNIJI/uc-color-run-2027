<?php

use App\Models\Registration;
use App\Models\RegistrationMember;
use App\Support\QrCode;
use App\Support\RacepackQr;
use BaconQrCode\Common\ErrorCorrectionLevel;

it('builds the payload from the issued BIBs once the payment is confirmed', function (string $package, string $payload) {
    $registration = registerForBib($this, $package);

    expect(RacepackQr::payloadFor($registration))->toBeNull()
        ->and(RacepackQr::svgFor($registration))->toBeNull();

    $registration->markAsPaid();

    expect(RacepackQr::payloadFor($registration->fresh('members')))->toBe($payload);
})->with([
    '3K Early Bird External' => ['external/early-bird/3k', 'UCCR-0320001'],
    '10K Normal Internal' => ['internal/normal/10k', 'UCCR-1030001'],
    '5K Couple Bundle' => ['couple', 'UCCR-0550001;UCCR-0550002'],
    '3K Family Bundle' => ['family', 'UCCR-0350001;UCCR-0350002;UCCR-0350003;UCCR-0350004'],
]);

it('lists the payer first, then members by their position in the registration', function () {
    $registration = Registration::factory()->bundle('family')->paid()->create(['bib_number' => '0350011']);
    RegistrationMember::factory()->for($registration)->create(['position' => 4, 'bib_number' => '0350014']);
    RegistrationMember::factory()->for($registration)->create(['position' => 2, 'bib_number' => '0350012']);
    RegistrationMember::factory()->for($registration)->create(['position' => 3, 'bib_number' => '0350013']);

    expect(RacepackQr::payloadFor($registration->fresh('members')))->toBe('UCCR-0350011;UCCR-0350012;UCCR-0350013;UCCR-0350014');
});

it('is not active until the payment is confirmed, even with a BIB on the record', function (Registration $registration) {
    expect(RacepackQr::payloadFor($registration))->toBeNull()
        ->and(RacepackQr::svgFor($registration))->toBeNull();

    $this->actingAs($registration->user)
        ->get(route('my-registration.show'))
        ->assertSee('QR tersedia setelah pembayaran lunas.')
        ->assertDontSee('data-racepack-qr', false);
})->with([
    'pending' => fn () => Registration::factory()->create(['bib_number' => '0320001']),
    'expired' => fn () => Registration::factory()->expired()->create(['bib_number' => '0320001']),
    'failed' => fn () => Registration::factory()->failed()->create(['bib_number' => '0320001']),
]);

it('renders the QR of exactly that payload, the same image every time', function () {
    $registration = Registration::factory()->bundle('couple')->paid()->create(['bib_number' => '0550001']);
    RegistrationMember::factory()->for($registration)->create(['position' => 2, 'bib_number' => '0550002']);
    $registration->load('members');

    $first = RacepackQr::svgFor($registration);

    expect($first)->toStartWith('<svg')
        ->toBe(QrCode::svg('UCCR-0550001;UCCR-0550002', RacepackQr::SIZE, ErrorCorrectionLevel::M(), margin: 2))
        ->and(RacepackQr::svgFor($registration->fresh('members')))->toBe($first);

    // Confirming the same payment again changes nothing.
    $registration->markAsPaid();

    expect(RacepackQr::svgFor($registration->fresh('members')))->toBe($first);
});

it('shows an error instead of a QR when a paid registration is missing a BIB', function () {
    $registration = Registration::factory()->bundle('couple')->paid()->create(['bib_number' => '0550001']);
    RegistrationMember::factory()->for($registration)->create(['position' => 2, 'bib_number' => null]);

    expect(RacepackQr::svgFor($registration->fresh('members')))->toBeNull();

    $this->actingAs($registration->user)
        ->get(route('my-bib.show'))
        ->assertOk()
        ->assertSee('QR racepack belum dapat dibuat.')
        ->assertDontSee('data-racepack-qr', false);
});

it('activates the QR only after Midtrans reports the payment as settled', function () {
    $registration = Registration::factory()->create(['type' => 'external', 'category' => '5k', 'code' => 'UCCR-052-0003']);
    $registration->startQrisPayment();
    midtransReports('pending');

    $this->actingAs($registration->user)->post(route('payment.check'));

    expect(RacepackQr::payloadFor($registration->fresh()))->toBeNull();

    midtransReports('settlement');
    $this->postJson(route('payments.midtrans.notification'), midtransNotification($registration->latestPayment()->first(), 'settlement'))->assertOk();

    // The BIB is issued at payment: the first paid 5K Early Bird External participant gets 0520001.
    expect(RacepackQr::payloadFor($registration->fresh()))->toBe('UCCR-0520001');
});
