<?php

namespace App\Support;

use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\TicketPrice;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Creates and refreshes Midtrans QRIS payments for registrations. Problems are reported to the
 * log; participants only ever see a short message without server details.
 */
final class QrisCheckout
{
    public const UNAVAILABLE_MESSAGE = 'Maaf, kode QRIS belum bisa dibuat saat ini. Silakan coba lagi beberapa saat lagi atau hubungi panitia.';

    public const PACKAGE_GONE_MESSAGE = 'Paket registrasimu sudah tidak tersedia. Silakan hubungi panitia.';

    /**
     * Create the QRIS for a registration whose slot is held. Returns an error message for the
     * participant, or null when the QRIS was created. Without Midtrans keys the local environment
     * keeps the registration open so the payment pages can still be tried with the simulation.
     */
    public static function start(Registration $registration): ?string
    {
        if (! Midtrans::isConfigured()) {
            if (app()->isLocal()) {
                return null;
            }

            report(new RuntimeException('MIDTRANS_SERVER_KEY is not set, so no QRIS payment could be created.'));
            $registration->markAsFailed();

            return self::UNAVAILABLE_MESSAGE;
        }

        try {
            $registration->startQrisPayment();
        } catch (Throwable $exception) {
            report($exception);

            return self::UNAVAILABLE_MESSAGE;
        }

        return null;
    }

    /**
     * Start a new payment attempt: "Pay Again" after a payment expired, was cancelled or failed,
     * or "Buat Kode QRIS" for a registration without a QRIS. The attempt is always for the package
     * the participant registered for, never another one, at that package's current price.
     *
     * An expired or lost attempt is verified with Midtrans first, so a payment made at the last
     * moment is confirmed instead of replaced. Then one transaction locks the package row and the
     * registration row, so simultaneous requests (a double click, two tabs, two participants after
     * the last slot) are decided one at a time:
     *  - a paid registration, or one whose attempt can still be paid, gets no new attempt;
     *  - a registration without a slot claims one only while its package has a free slot;
     *  - the new attempt gets its own order ID, and earlier attempts stay as payment history.
     * Midtrans creates the QRIS after the transaction, so no lock is held while waiting for it.
     *
     * Returns an error message for the participant, or null when the payment page can be shown:
     * with the new QRIS, with the attempt that can still be paid, or as the confirmed payment.
     */
    public static function retry(Registration $registration): ?string
    {
        if (! Midtrans::isConfigured() && ! app()->isLocal()) {
            report(new RuntimeException('MIDTRANS_SERVER_KEY is not set, so no QRIS payment could be created.'));
            $registration->markAsFailed();

            return self::UNAVAILABLE_MESSAGE;
        }

        self::latestPayment($registration);
        $package = $registration->package();

        if ($package === null) {
            return self::PACKAGE_GONE_MESSAGE;
        }

        /** @var Payment|string|null $attempt */
        $attempt = DB::transaction(function () use ($registration, $package): Payment|string|null {
            $locked = $package->lockForRegistration();
            $current = Registration::query()->lockForUpdate()->findOrFail($registration->getKey());

            if ($current->isPaid() || $current->latestPayment()->first()?->isPayable()) {
                return null;
            }

            // A registration still inside its payment window already holds its slot.
            $message = $current->isAwaitingPayment()
                ? ($locked->price === null ? self::retryUnavailableMessage($locked) : null)
                : self::retryUnavailableMessage($locked);

            if ($message !== null) {
                return $message;
            }

            $current->forceFill(['amount' => $locked->price])->save();
            $current->openPaymentWindow();

            return Midtrans::isConfigured() ? $current->newPaymentAttempt() : null;
        });

        if (! $attempt instanceof Payment) {
            return $attempt;
        }

        try {
            $registration->refresh()->chargeQris($attempt);
        } catch (Throwable $exception) {
            report($exception);

            return self::UNAVAILABLE_MESSAGE;
        }

        return null;
    }

    /**
     * Why "Pay Again" is not offered for this registration, or null when it is (or no new attempt
     * is needed). Only a hint for the page; retry() checks again under lock.
     */
    public static function retryBlockedReason(Registration $registration): ?string
    {
        if ($registration->isPaid() || $registration->isAwaitingPayment()) {
            return null;
        }

        $package = $registration->package();

        return $package === null ? self::PACKAGE_GONE_MESSAGE : self::retryUnavailableMessage($package);
    }

    /**
     * Why the registration's own package cannot take a new payment now, or null when it can.
     * A sold-out package is never swapped for another one.
     */
    public static function retryUnavailableMessage(TicketPrice $package): ?string
    {
        $name = "{$package->name()} ({$package->eligibility()})";

        return match (true) {
            $package->isSoldOut() => "Kuota paket {$name} sudah habis, jadi pembayaran ulang tidak dapat dilakukan. Pembayaran ulang hanya berlaku untuk paket yang sama; hubungi panitia jika ingin mengikuti paket lain.",
            ! $package->is_active => "Paket {$name} sedang tidak tersedia, jadi pembayaran ulang belum dapat dilakukan. Silakan hubungi panitia.",
            $package->price === null => "Harga paket {$name} belum diumumkan, jadi pembayaran belum dapat dilakukan.",
            default => null,
        };
    }

    /**
     * The latest payment attempt, verified with Midtrans first when its QRIS has expired or was
     * lost, so the decision shown to the participant matches what Midtrans knows.
     */
    public static function latestPayment(Registration $registration): ?Payment
    {
        $payment = $registration->latestPayment;

        if ($payment?->isPending() && ($payment->hasExpired() || $payment->lostItsQris())) {
            self::refresh($payment);
            $registration->refresh();
        }

        return $payment?->fresh();
    }

    /**
     * Ask Midtrans for the current status of a pending payment and apply it. A payment still pending
     * after its expiry time is treated as expired here (which gives the slot back); if Midtrans later
     * confirms that it was paid after all, that success still wins. An attempt whose QRIS was never
     * saved and that Midtrans does not know is closed as failed, so a new one can be made.
     */
    public static function refresh(Payment $payment): void
    {
        if (! $payment->isPending()) {
            return;
        }

        self::apply($payment, Midtrans::isConfigured() ? self::fetchStatus($payment) : null);
    }

    /**
     * Refresh many pending payments at once, as the scheduled payments:check-pending does. Every
     * status is asked from Midtrans first; then the confirmed payments are applied in the order
     * Midtrans settled them, so payments found in the same run get their BIBs in the order they
     * were really paid. A payment Midtrans cannot be asked about now is reported and left pending.
     *
     * @param  iterable<Payment>  $payments
     * @return array{confirmed: int, ended: int, pending: int, unreachable: int}
     */
    public static function refreshMany(iterable $payments): array
    {
        $checks = [];

        foreach ($payments as $payment) {
            if ($payment->isPending()) {
                $checks[] = ['payment' => $payment, 'status' => self::fetchStatus($payment)];
            }
        }

        $settlementOrder = fn (array $check): string => is_array($check['status']) ? (string) ($check['status']['settlement_time'] ?? '') : '';
        $totals = ['confirmed' => 0, 'ended' => 0, 'pending' => 0, 'unreachable' => 0];

        foreach (collect($checks)->sortBy($settlementOrder) as ['payment' => $payment, 'status' => $status]) {
            self::apply($payment, $status);

            $totals[match (true) {
                $payment->status === TransactionStatus::Success => 'confirmed',
                ! $payment->isPending() => 'ended',
                $status instanceof Throwable => 'unreachable',
                default => 'pending',
            }]++;
        }

        return $totals;
    }

    /**
     * What Midtrans reports for the payment, or why it could not be asked.
     *
     * @return array<string, mixed>|Throwable
     */
    private static function fetchStatus(Payment $payment): array|Throwable
    {
        try {
            return Midtrans::status($payment->order_id);
        } catch (Throwable $exception) {
            return $exception;
        }
    }

    /**
     * Apply what Midtrans reported (null when Midtrans is not configured). The payment is read
     * again first, so a notification that confirmed it in the meantime is never undone.
     *
     * @param  array<string, mixed>|Throwable|null  $status
     */
    private static function apply(Payment $payment, array|Throwable|null $status): void
    {
        $payment->refresh();

        if (! $payment->isPending()) {
            return;
        }

        if ($status instanceof Throwable) {
            if ($status->getCode() === Midtrans::UNKNOWN_ORDER && $payment->lostItsQris()) {
                self::close($payment, TransactionStatus::Failed);

                return;
            }

            report($status);
        } elseif ($status !== null) {
            try {
                $payment->applyGatewayStatus($status);
            } catch (Throwable $exception) {
                report($exception);
            }

            $payment->refresh();
        }

        if ($payment->isPending() && $payment->hasExpired()) {
            self::close($payment, TransactionStatus::Expired);
        }
    }

    /**
     * End a pending attempt that can no longer be paid. When it is the latest attempt of an unpaid
     * registration, the registration's slot is given back.
     */
    private static function close(Payment $payment, TransactionStatus $status): void
    {
        $payment->update(['status' => $status]);
        $registration = $payment->registration;

        if (! $registration->isPaid() && $registration->latestPayment()->first()?->is($payment)) {
            $registration->markAsFailed();
        }
    }
}
