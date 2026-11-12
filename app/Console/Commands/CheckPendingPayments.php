<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Support\Midtrans;
use App\Support\QrisCheckout;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Runs every minute (routes/console.php). Confirms payments, issues BIBs and closes expired QRIS
 * codes without waiting for a Midtrans notification or for the participant's payment page, so it
 * also works on localhost, where Midtrans cannot send notifications, and catches any notification
 * that never arrived in production.
 */
#[Signature('payments:check-pending')]
#[Description('Ask Midtrans for the status of every unpaid QRIS payment and apply it (confirms payments and issues BIBs)')]
class CheckPendingPayments extends Command
{
    public function handle(): int
    {
        if (! Midtrans::isConfigured()) {
            $this->components->info('Midtrans is not configured, so there are no payments to check.');

            return self::SUCCESS;
        }

        $payments = Payment::query()->awaitingMidtrans()->orderBy('id')->get();

        if ($payments->isEmpty()) {
            $this->components->info('No pending payments.');

            return self::SUCCESS;
        }

        $totals = QrisCheckout::refreshMany($payments);

        $this->components->info("Checked {$payments->count()} pending payment(s): {$totals['confirmed']} confirmed, {$totals['ended']} expired or failed, {$totals['pending']} still waiting.");

        if ($totals['unreachable'] > 0) {
            $this->components->warn("Midtrans could not be asked about {$totals['unreachable']} payment(s); they are checked again in the next run.");
        }

        return self::SUCCESS;
    }
}
