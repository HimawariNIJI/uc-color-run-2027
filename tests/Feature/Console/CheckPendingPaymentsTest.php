<?php

use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Registration;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;

/**
 * A 3K Early Bid Internal registration with its QRIS created.
 */
function pendingQris(string $code): Registration
{
    $registration = Registration::factory()->create(['code' => $code, 'type' => 'internal', 'category' => '3k']);
    $registration->startQrisPayment();

    return $registration->fresh();
}

/**
 * How many status checks were sent to the fake Midtrans.
 */
function statusChecksSent(): int
{
    return Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), '/status'))->count();
}

it('confirms a payment and issues its BIB without the participant opening any page', function () {
    $registration = pendingQris('UCCR-031-0001');
    midtransReports('settlement', ['settlement_time' => '2026-11-09 10:15:00']);

    $this->artisan('payments:check-pending')
        ->expectsOutputToContain('Checked 1 pending payment(s): 1 confirmed, 0 expired or failed, 0 still waiting.')
        ->assertSuccessful();

    expect($registration->fresh())
        ->payment_status->toBe(PaymentStatus::Paid)
        ->bib_number->toBe('0310001')
        ->paid_at->format('Y-m-d H:i')->toBe('2026-11-09 10:15')
        ->and($registration->latestPayment()->first()->status)->toBe(TransactionStatus::Success);
});

it('issues the BIBs of payments found in the same run in the order Midtrans settled them', function () {
    $registeredFirst = pendingQris('UCCR-031-0001');
    $registeredSecond = pendingQris('UCCR-031-0002');
    midtransReportsFor('UCCR-031-0001-1', 'settlement', ['settlement_time' => '2026-11-09 10:20:00']);
    midtransReportsFor('UCCR-031-0002-1', 'settlement', ['settlement_time' => '2026-11-09 10:10:00']);

    $this->artisan('payments:check-pending')->assertSuccessful();

    expect($registeredSecond->fresh()->bib_number)->toBe('0310001')
        ->and($registeredFirst->fresh()->bib_number)->toBe('0310002');
});

it('leaves a payment that can still be paid waiting', function () {
    $registration = pendingQris('UCCR-031-0001');

    $this->artisan('payments:check-pending')
        ->expectsOutputToContain('0 confirmed, 0 expired or failed, 1 still waiting.')
        ->assertSuccessful();

    expect($registration->fresh())
        ->payment_status->toBe(PaymentStatus::Unpaid)
        ->bib_number->toBeNull();
});

it('ends a QRIS that expired, was cancelled or failed, and gives its slot back', function (string $midtransStatus, TransactionStatus $ended, int $minutesLater) {
    setPackage('internal', 'early_bird', '3k', ['quota' => 1]);
    $registration = pendingQris('UCCR-031-0001');
    $this->travel($minutesLater)->minutes();
    midtransReports($midtransStatus);

    $this->artisan('payments:check-pending')
        ->expectsOutputToContain('0 confirmed, 1 expired or failed, 0 still waiting.')
        ->assertSuccessful();

    expect($registration->fresh())
        ->payment_status->toBe(PaymentStatus::Failed)
        ->bib_number->toBeNull()
        ->and($registration->latestPayment()->first()->status)->toBe($ended)
        ->and($registration->fresh()->package()->remaining())->toBe(1);
})->with([
    'expired at Midtrans' => ['expire', TransactionStatus::Expired, 61],
    'still pending at Midtrans after its expiry time' => ['pending', TransactionStatus::Expired, 61],
    'cancelled' => ['cancel', TransactionStatus::Cancelled, 5],
    'denied' => ['deny', TransactionStatus::Failed, 5],
]);

it('leaves a QRIS that is still being created to its own request, and closes one that was lost', function () {
    $beingCreated = Registration::factory()->create(['code' => 'UCCR-031-0001', 'type' => 'internal', 'category' => '3k']);
    $lost = Registration::factory()->create(['code' => 'UCCR-031-0002', 'type' => 'internal', 'category' => '3k']);
    $lost->newPaymentAttempt();
    $this->travel(3)->minutes();
    $beingCreated->newPaymentAttempt();
    midtransDoesNotKnowOrders();

    $this->artisan('payments:check-pending')
        ->expectsOutputToContain('Checked 1 pending payment(s): 0 confirmed, 1 expired or failed, 0 still waiting.')
        ->assertSuccessful();

    expect($lost->latestPayment()->first()->status)->toBe(TransactionStatus::Failed)
        ->and($beingCreated->latestPayment()->first()->status)->toBe(TransactionStatus::Pending)
        ->and(statusChecksSent())->toBe(1);
});

it('keeps checking the other payments when Midtrans cannot be reached for one', function () {
    Exceptions::fake();
    $unreachable = pendingQris('UCCR-031-0001');
    $paid = pendingQris('UCCR-031-0002');
    midtransReportsFor('UCCR-031-0001-1', null);
    midtransReportsFor('UCCR-031-0002-1', 'settlement');

    $this->artisan('payments:check-pending')
        ->expectsOutputToContain('1 confirmed, 0 expired or failed, 0 still waiting.')
        ->expectsOutputToContain('Midtrans could not be asked about 1 payment(s); they are checked again in the next run.')
        ->assertSuccessful();

    expect($unreachable->fresh()->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and($paid->fresh()->isPaid())->toBeTrue();
    Exceptions::assertReported(fn (RuntimeException $exception): bool => str_contains($exception->getMessage(), 'Midtrans status for UCCR-031-0001-1 is unavailable (HTTP 503'));
});

it('never changes a payment that is already confirmed or ended', function () {
    $paid = pendingQris('UCCR-031-0001');
    midtransReports('settlement');
    $this->artisan('payments:check-pending')->assertSuccessful();
    midtransReports('cancel');

    $this->artisan('payments:check-pending')
        ->expectsOutputToContain('No pending payments.')
        ->assertSuccessful();

    expect($paid->fresh()->isPaid())->toBeTrue()
        ->and(statusChecksSent())->toBe(1);
});

it('does nothing while Midtrans is not configured', function () {
    pendingQris('UCCR-031-0001');
    config(['services.midtrans.server_key' => null]);

    $this->artisan('payments:check-pending')
        ->expectsOutputToContain('Midtrans is not configured, so there are no payments to check.')
        ->assertSuccessful();

    expect(statusChecksSent())->toBe(0);
});

it('is scheduled every minute, never twice at the same time', function () {
    $this->app->make(Kernel::class)->all();

    $event = collect($this->app->make(Schedule::class)->events())
        ->first(fn (Event $event): bool => str_contains((string) $event->command, 'payments:check-pending'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *')
        ->and($event->withoutOverlapping)->toBeTrue()
        ->and($event->expiresAt)->toBe(5);
});
