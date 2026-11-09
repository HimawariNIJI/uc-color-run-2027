<?php

use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Registration;
use App\Models\RegistrationMember;

/**
 * A registration (number 4 of 3K Early Bid Internal reserved) with its Midtrans QRIS created.
 */
function registrationAwaitingNotification(?Closure $configure = null): Registration
{
    $registration = Registration::factory()->create(['code' => 'UCCR-0301010004', 'type' => 'internal', 'category' => '3k']);

    if ($configure !== null) {
        $configure($registration);
    }

    $registration->startQrisPayment();

    return $registration->fresh();
}

it('confirms the payment and issues the BIB when Midtrans reports a settlement', function () {
    $registration = registrationAwaitingNotification();
    $payment = $registration->latestPayment;
    midtransReports('settlement', ['settlement_time' => '2026-11-09 11:05:00']);

    $this->postJson(route('payments.midtrans.notification'), midtransNotification($payment, 'settlement'))
        ->assertOk();

    expect($registration->fresh())
        ->payment_status->toBe(PaymentStatus::Paid)
        ->bib_number->toBe('0301010004')
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
        ->bib_number->toBe('0301010004')
        ->paid_at->equalTo($paidAt)->toBeTrue();
});

it('gives bundle members their own BIB numbers once the bundle is paid', function () {
    $registration = Registration::factory()->bundle('family')->create(['code' => 'UCCR-0300040001']);
    RegistrationMember::factory()->for($registration)->create(['position' => 2]);
    RegistrationMember::factory()->for($registration)->create(['position' => 3]);
    $registration->startQrisPayment();
    midtransReports('settlement');

    $this->postJson(route('payments.midtrans.notification'), midtransNotification($registration->latestPayment()->first(), 'settlement'))->assertOk();

    expect($registration->fresh()->bib_number)->toBe('0300040001')
        ->and($registration->members()->pluck('bib_number')->all())->toBe(['0300040002', '0300040003']);
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
    'cancelled' => ['cancel', TransactionStatus::Failed],
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
