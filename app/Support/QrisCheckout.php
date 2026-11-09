<?php

namespace App\Support;

use App\Enums\FeePeriod;
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
     * Ask Midtrans for the current status of a pending payment and apply it. A payment still pending
     * after its expiry time is treated as expired here (which gives the slot back); if Midtrans later
     * confirms that it was paid after all, that success still wins.
     */
    public static function refresh(Payment $payment): void
    {
        if (! $payment->isPending()) {
            return;
        }

        if (Midtrans::isConfigured()) {
            try {
                $payment->applyGatewayStatus(Midtrans::status($payment->order_id));
            } catch (Throwable $exception) {
                report($exception);
            }

            $payment->refresh();
        }

        if ($payment->isPending() && $payment->hasExpired()) {
            $payment->update(['status' => TransactionStatus::Expired]);

            if (! $payment->registration->isPaid()) {
                $payment->registration->markAsFailed();
            }
        }
    }

    /**
     * Before a new payment attempt, claim a slot of the registration's package again (a failed or
     * expired payment gave it back) at the package's current price. When an Early Bid package filled
     * up meanwhile, the registration moves to the Normal Bid package if that is open.
     *
     * @return array{opened: bool, moved: bool, message: ?string}
     */
    public static function reopen(Registration $registration): array
    {
        $package = $registration->package();

        if ($package === null) {
            return ['opened' => false, 'moved' => false, 'message' => 'Paket registrasimu sudah tidak tersedia. Silakan hubungi panitia.'];
        }

        if (self::claim($registration, $package)) {
            return ['opened' => true, 'moved' => false, 'message' => null];
        }

        $normalBid = $package->price_type === FeePeriod::EarlyBird
            ? TicketPrice::findOption($package->registration_type, FeePeriod::Regular, $package->race_category)
            : null;

        if ($normalBid !== null && self::claim($registration, $normalBid)) {
            return ['opened' => true, 'moved' => true, 'message' => null];
        }

        return ['opened' => false, 'moved' => false, 'message' => $package->fresh()->unavailableMessage() ?? 'Paket registrasimu sedang tidak tersedia. Silakan hubungi panitia.'];
    }

    private static function claim(Registration $registration, TicketPrice $package): bool
    {
        return DB::transaction(function () use ($registration, $package): bool {
            $locked = $package->lockForRegistration();

            if (! $locked->isOpen()) {
                return false;
            }

            $registration->forceFill([
                'ticket_price_id' => $locked->getKey(),
                'fee_period' => $locked->price_type,
                'amount' => $locked->price,
            ])->save();
            $registration->setRelation('ticketPrice', $locked);
            $registration->openPaymentWindow();

            return true;
        });
    }
}
