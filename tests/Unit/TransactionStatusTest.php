<?php

use App\Enums\TransactionStatus;

it('maps every Midtrans transaction status to a payment status', function (string $midtrans, ?string $fraud, TransactionStatus $expected) {
    expect(TransactionStatus::fromMidtrans($midtrans, $fraud))->toBe($expected);
})->with([
    'QRIS paid' => ['settlement', null, TransactionStatus::Success],
    'card captured and accepted' => ['capture', 'accept', TransactionStatus::Success],
    'card captured but challenged' => ['capture', 'challenge', TransactionStatus::Pending],
    'waiting for payment' => ['pending', null, TransactionStatus::Pending],
    'QRIS expired' => ['expire', null, TransactionStatus::Expired],
    'cancelled' => ['cancel', null, TransactionStatus::Failed],
    'denied' => ['deny', null, TransactionStatus::Failed],
    'failed' => ['failure', null, TransactionStatus::Failed],
    'unknown status' => ['something-new', null, TransactionStatus::Failed],
]);
