<?php

namespace App\Http\Controllers;

use App\Enums\FeePeriod;
use App\Support\EarlyBirdQuota;
use App\Support\Rupiah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * QRIS payment flow. The payment gateway is not connected yet, so the QR code is a
 * placeholder and payments are confirmed through the local simulation endpoint.
 */
class PaymentController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $registration = $request->user()->registration;

        if ($registration->isPaid()) {
            return to_route('payment.success');
        }

        if ($registration->hasFailedPayment()) {
            return to_route('payment.failed');
        }

        if ($registration->paymentHasExpired()) {
            $registration->openPaymentWindow();
        }

        return view('payment.show', [
            'registration' => $registration,
            'secondsLeft' => max(0, (int) now()->diffInSeconds($registration->payment_expires_at, false)),
            'canSimulatePayment' => app()->isLocal(),
        ]);
    }

    public function check(Request $request): RedirectResponse
    {
        $registration = $request->user()->registration;

        if ($registration->isPaid()) {
            return to_route('payment.success');
        }

        if ($registration->paymentHasExpired()) {
            $registration->markAsFailed();
        }

        if ($registration->hasFailedPayment()) {
            return to_route('payment.failed');
        }

        return to_route('payment.show')
            ->with('info', 'Pembayaran belum kami terima. Selesaikan pembayaran, lalu cek kembali statusnya.');
    }

    public function retry(Request $request): RedirectResponse
    {
        $registration = $request->user()->registration;

        if ($registration->isPaid()) {
            return to_route('payment.success');
        }

        // A failed payment gave its Early Bird slot back; claim it again or fall back to Normal Price.
        if ($registration->hasFailedPayment() && $registration->fee_period === FeePeriod::EarlyBird) {
            $keptEarlyBird = EarlyBirdQuota::whileLocked($registration->category, function () use ($registration): bool {
                if (EarlyBirdQuota::isAvailable($registration->category)) {
                    $registration->openPaymentWindow();

                    return true;
                }

                $registration->forceFill([
                    'fee_period' => FeePeriod::Regular,
                    'amount' => $registration->category->feeFor($registration->type, FeePeriod::Regular),
                ])->save();
                $registration->openPaymentWindow();

                return false;
            });

            if (! $keptEarlyBird) {
                return to_route('payment.show')->with('info', "Slot Early Bird untuk {$registration->category->distance()} sudah habis, sehingga biaya registrasimu disesuaikan ke Normal Price (".Rupiah::format($registration->amount).').');
            }

            return to_route('payment.show');
        }

        $registration->openPaymentWindow();

        return to_route('payment.show');
    }

    public function success(Request $request): View|RedirectResponse
    {
        $registration = $request->user()->registration;

        if (! $registration->isPaid()) {
            return to_route('payment.show');
        }

        return view('payment.success', [
            'registration' => $registration,
        ]);
    }

    public function failed(Request $request): View|RedirectResponse
    {
        $registration = $request->user()->registration;

        if ($registration->isPaid()) {
            return to_route('payment.success');
        }

        if (! $registration->hasFailedPayment() && ! $registration->paymentHasExpired()) {
            return to_route('payment.show');
        }

        return view('payment.failed', [
            'registration' => $registration,
        ]);
    }
}
