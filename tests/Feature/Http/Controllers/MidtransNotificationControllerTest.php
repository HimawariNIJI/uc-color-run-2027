<?php

use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\BibSequence;
use App\Models\Registration;
use App\Models\RegistrationMember;
use Illuminate\Support\Facades\Exceptions;
use Tests\TestCase;

/**
 * A registration (the 4th 3K Early Bid Internal registration) with its Midtrans QRIS created.
 *
 * @param  array<string, mixed>  $attributes
 */
function registrationAwaitingNotification(?Closure $configure = null, array $attributes = []): Registration
{
    $registration = Registration::factory()->create(['code' => 'UCCR-031-0004', 'type' => 'internal', 'category' => '3k', ...$attributes]);

    if ($configure !== null) {
        $configure($registration);
    }

    $registration->startQrisPayment();

    return $registration->fresh();
}

/**
 * Send the verified Midtrans notification for the registration's current QRIS.
 *
 * @param  array<string, mixed>  $extra  more status fields, such as settlement_time
 */
function notifyMidtransFor(TestCase $test, Registration $registration, string $status, array $extra = []): void
{
    midtransReports($status, $extra);
    $test->postJson(route('payments.midtrans.notification'), midtransNotification($registration->latestPayment()->first(), $status, $status === 'settlement' ? '200' : '201'))->assertOk();
}

it('confirms the payment and issues the first BIB of the package when Midtrans reports a settlement', function () {
    $registration = registrationAwaitingNotification();
    $payment = $registration->latestPayment;
    midtransReports('settlement', ['settlement_time' => '2026-11-09 11:05:00']);

    $this->postJson(route('payments.midtrans.notification'), midtransNotification($payment, 'settlement'))
        ->assertOk();

    expect($registration->fresh())
        ->payment_status->toBe(PaymentStatus::Paid)
        ->bib_number->toBe('0310001')
        ->transaction_id->toBe('UCCR-031-0004-1')
        // The registration's payment date is Midtrans' settlement time, not when the notification arrived.
        ->paid_at->format('Y-m-d H:i')->toBe('2026-11-09 11:05')
        ->and($payment->fresh())
        ->status->toBe(TransactionStatus::Success)
        ->paid_at->format('Y-m-d H:i')->toBe('2026-11-09 11:05');
});

it('handles the same notification twice without changing anything', function () {
    $registration = registrationAwaitingNotification();
    $payment = $registration->latestPayment;
    midtransReports('settlement');
    $notification = midtransNotification($payment, 'settlement');

    $this->postJson(route('payments.midtrans.notification'), $notification)->assertOk();
    $paidAt = $registration->fresh()->paid_at;
    $this->travel(5)->minutes();
    $this->postJson(route('payments.midtrans.notification'), $notification)->assertOk();

    expect($registration->fresh())
        ->bib_number->toBe('0310001')
        ->paid_at->equalTo($paidAt)->toBeTrue()
        ->and(BibSequence::query()->find('031')->last_sequence)->toBe(1);
});

it('issues every bundle participant consecutive BIBs together once the bundle is paid', function () {
    $registration = Registration::factory()->bundle('family')->create(['code' => 'UCCR-035-0005']);

    foreach ([2, 3, 4] as $position) {
        RegistrationMember::factory()->for($registration)->create(['position' => $position]);
    }

    $registration->startQrisPayment();
    notifyMidtransFor($this, $registration, 'pending');

    expect($registration->fresh()->participantBibNumbers())->toBe([null, null, null, null]);

    notifyMidtransFor($this, $registration, 'settlement');

    expect($registration->fresh()->participantBibNumbers())->toBe(['0350001', '0350002', '0350003', '0350004']);
});

it('gives BIBs in the order payments are confirmed, not the order of registering', function () {
    $earlier = registrationAwaitingNotification(attributes: ['code' => 'UCCR-031-0001']);
    $this->travel(5)->minutes();
    $later = registrationAwaitingNotification(attributes: ['code' => 'UCCR-031-0002']);

    notifyMidtransFor($this, $later, 'settlement', ['settlement_time' => '2026-11-09 10:10:00']);
    notifyMidtransFor($this, $earlier, 'settlement', ['settlement_time' => '2026-11-09 10:20:00']);

    expect($later->fresh()->bib_number)->toBe('0310001')
        ->and($earlier->fresh()->bib_number)->toBe('0310002');
});

it('numbers a notification that arrives late after the ones already confirmed, and never renumbers an issued BIB', function () {
    $first = registrationAwaitingNotification(attributes: ['code' => 'UCCR-031-0001']);
    $second = registrationAwaitingNotification(attributes: ['code' => 'UCCR-031-0002']);

    // The first participant paid at 10:10, but Midtrans' notification for it only arrives after the
    // second participant's payment at 10:12 was confirmed: BIBs follow the verified confirmations.
    notifyMidtransFor($this, $second, 'settlement', ['settlement_time' => '2026-11-09 10:12:00']);
    notifyMidtransFor($this, $first, 'settlement', ['settlement_time' => '2026-11-09 10:10:00']);

    expect($second->fresh()->bib_number)->toBe('0310001')
        ->and($first->fresh())
        ->bib_number->toBe('0310002')
        ->paid_at->format('H:i')->toBe('10:10');
});

it('keeps the BIB when a delayed pending notification arrives after the settlement', function () {
    $registration = registrationAwaitingNotification();
    notifyMidtransFor($this, $registration, 'settlement');

    notifyMidtransFor($this, $registration, 'pending');

    expect($registration->fresh())
        ->payment_status->toBe(PaymentStatus::Paid)
        ->bib_number->toBe('0310001')
        ->and($registration->latestPayment()->first()->status)->toBe(TransactionStatus::Success);
});

it('gives no BIB for a pending, expired, cancelled or denied payment', function (string $status) {
    $registration = registrationAwaitingNotification();

    notifyMidtransFor($this, $registration, $status);

    expect($registration->fresh()->bib_number)->toBeNull()
        ->and(BibSequence::query()->find('031')->last_sequence)->toBe(0);
})->with(['pending', 'expire', 'cancel', 'deny']);

it('issues one BIB after a retry, and nothing more when Midtrans also settles the older attempt', function () {
    Exceptions::fake();
    $registration = registrationAwaitingNotification();
    $firstAttempt = $registration->latestPayment;
    notifyMidtransFor($this, $registration, 'expire');

    $this->actingAs($registration->user)->post(route('payment.retry'))->assertRedirect(route('payment.show'));
    notifyMidtransFor($this, $registration, 'settlement');

    expect($registration->fresh())
        ->bib_number->toBe('0310001')
        ->transaction_id->toBe('UCCR-031-0004-2');

    // Midtrans reports that the first QRIS was paid too: recorded and reported, but no second BIB.
    midtransReports('settlement');
    $this->postJson(route('payments.midtrans.notification'), midtransNotification($firstAttempt, 'settlement'))->assertOk();

    expect($registration->fresh())
        ->bib_number->toBe('0310001')
        ->transaction_id->toBe('UCCR-031-0004-2')
        ->and($firstAttempt->fresh()->status)->toBe(TransactionStatus::Success)
        ->and(BibSequence::query()->find('031')->last_sequence)->toBe(1);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Registration UCCR-031-0004 was already paid, and Midtrans settled order UCCR-031-0004-1 as well.');
});

it('issues the next numbers of the package to a registration made before the current code format', function () {
    $registration = Registration::factory()->bundle('couple')->create(['code' => 'UCR-2027-OLDER']);
    RegistrationMember::factory()->for($registration)->create(['position' => 2]);
    $registration->startQrisPayment();
    midtransReports('settlement');

    $this->postJson(route('payments.midtrans.notification'), midtransNotification($registration->latestPayment()->first(), 'settlement'))->assertOk();

    expect($registration->fresh()->bib_number)->toBe('0550001')
        ->and($registration->members()->pluck('bib_number')->all())->toBe(['0550002']);
});

it('rejects a notification whose signature does not match', function () {
    $registration = registrationAwaitingNotification();
    $notification = [...midtransNotification($registration->latestPayment, 'settlement'), 'signature_key' => str_repeat('a', 128)];
    midtransReports('settlement');

    $this->postJson(route('payments.midtrans.notification'), $notification)->assertForbidden();

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('rejects a notification signed for a different amount', function () {
    $registration = registrationAwaitingNotification();
    $notification = [...midtransNotification($registration->latestPayment, 'settlement'), 'gross_amount' => '1.00'];
    midtransReports('settlement');

    $this->postJson(route('payments.midtrans.notification'), $notification)->assertForbidden();

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('trusts only the status fetched from Midtrans, not the one in the notification', function () {
    $registration = registrationAwaitingNotification();
    midtransReports('pending');

    $this->postJson(route('payments.midtrans.notification'), midtransNotification($registration->latestPayment, 'settlement'))
        ->assertOk();

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('does not confirm a payment when Midtrans reports a different amount', function () {
    $registration = registrationAwaitingNotification();
    midtransReports('settlement', ['gross_amount' => '1000.00']);

    $this->postJson(route('payments.midtrans.notification'), midtransNotification($registration->latestPayment, 'settlement'))
        ->assertOk();

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('marks an expired or cancelled QRIS and gives the slot back', function (string $midtransStatus, TransactionStatus $expected) {
    setPackage('internal', 'early_bird', '3k', ['quota' => 1]);
    $registration = registrationAwaitingNotification();
    midtransReports($midtransStatus);

    $this->postJson(route('payments.midtrans.notification'), midtransNotification($registration->latestPayment, $midtransStatus, '202'))
        ->assertOk();

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Failed)
        ->and($registration->latestPayment()->first()->status)->toBe($expected)
        ->and($registration->fresh()->package()->remaining())->toBe(1);
})->with([
    'expired' => ['expire', TransactionStatus::Expired],
    'cancelled' => ['cancel', TransactionStatus::Cancelled],
    'denied' => ['deny', TransactionStatus::Failed],
]);

it('ignores a late failure for a payment that already succeeded', function () {
    $registration = registrationAwaitingNotification();
    $payment = $registration->latestPayment;
    midtransReports('settlement');
    $this->postJson(route('payments.midtrans.notification'), midtransNotification($payment, 'settlement'));
    midtransReports('cancel');

    $this->postJson(route('payments.midtrans.notification'), midtransNotification($payment, 'cancel', '202'))->assertOk();

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->status)->toBe(TransactionStatus::Success);
});

it('answers 404 for an order it does not know', function () {
    $registration = registrationAwaitingNotification();
    $notification = midtransNotification($registration->latestPayment, 'settlement');
    $notification['order_id'] = 'UCCR-9999999999-1';
    $notification['signature_key'] = hash('sha512', $notification['order_id'].'200'.$notification['gross_amount'].config('services.midtrans.server_key'));

    $this->postJson(route('payments.midtrans.notification'), $notification)->assertNotFound();
});
