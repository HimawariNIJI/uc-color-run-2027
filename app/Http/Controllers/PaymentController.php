<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Registration;
use App\Support\Midtrans;
use App\Support\QrisCheckout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Midtrans QRIS payment pages. A registration only counts as paid once Midtrans confirms it,
 * through a verified notification or a status check made from the server; nothing the browser
 * sends can mark it paid.
 */
class PaymentController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $registration = $request->user()->registration;
        $payment = $this->currentPayment($registration);

        if ($registration->isPaid()) {
            return to_route('payment.success');
        }

        if ($registration->hasFailedPayment() || $registration->paymentHasExpired()) {
            return to_route('payment.failed');
        }

        $expiresAt = $payment?->expires_at ?? $registration->payment_expires_at;

        return view('payment.show', [
            'registration' => $registration,
            'payment' => $payment,
            'qrSvg' => $payment?->isActive() ? $payment->qrSvg(260) : null,
            'expiresAt' => $expiresAt,
            'secondsLeft' => max(0, (int) now()->diffInSeconds($expiresAt, false)),
            'canCreateQris' => Midtrans::isConfigured() && ! $payment?->isActive(),
            'canSimulatePayment' => app()->isLocal() && ! Midtrans::isConfigured(),
        ]);
    }

    /**
     * "Cek Status Pembayaran": ask Midtrans directly instead of waiting for its notification.
     */
    public function check(Request $request): RedirectResponse
    {
        $registration = $this->checkedRegistration($request);

        if ($registration->isPaid()) {
            return to_route('payment.success');
        }

        if ($registration->hasFailedPayment() || $registration->paymentHasExpired()) {
            return to_route('payment.failed');
        }

        return to_route('payment.show')
            ->with('info', 'Pembayaran belum kami terima. Selesaikan pembayaran, lalu cek kembali statusnya.');
    }

    /**
     * Polled by the payment page, so it moves on as soon as Midtrans confirms the payment.
     */
    public function status(Request $request): JsonResponse
    {
        $registration = $this->checkedRegistration($request);

        [$status, $redirect] = match (true) {
            $registration->isPaid() => ['paid', route('payment.success')],
            $registration->hasFailedPayment(), $registration->paymentHasExpired() => ['failed', route('payment.failed')],
            default => ['pending', null],
        };

        return response()->json(['status' => $status, 'redirect' => $redirect]);
    }

    /**
     * "Pay Again" from My Registration or the failed page, or "Buat Kode QRIS" on the payment page:
     * a new QRIS for the same package, only while that package has a free slot. Never creates a
     * second QRIS while one can still be paid (see QrisCheckout::retry).
     */
    public function retry(Request $request): RedirectResponse
    {
        $registration = $request->user()->registration;

        if ($registration->isPaid()) {
            return to_route('payment.success');
        }

        if ($error = QrisCheckout::retry($registration)) {
            return to_route('payment.failed')->with('error', $error);
        }

        // Checking an expired QRIS with Midtrans can turn up a payment made at the last moment.
        return to_route($registration->refresh()->isPaid() ? 'payment.success' : 'payment.show');
    }

    public function success(Request $request): View|RedirectResponse
    {
        $registration = $request->user()->registration;

        if (! $registration->isPaid()) {
            return to_route('payment.show');
        }

        return view('payment.success', [
            'registration' => $registration->load('members'),
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
            'retryBlockedReason' => QrisCheckout::retryBlockedReason($registration),
        ]);
    }

    /**
     * The latest payment attempt, refreshed with Midtrans once its QRIS has expired.
     */
    private function currentPayment(Registration $registration): ?Payment
    {
        return QrisCheckout::latestPayment($registration);
    }

    private function checkedRegistration(Request $request): Registration
    {
        $registration = $request->user()->registration;
        $payment = $registration->latestPayment;

        if (! $registration->isPaid() && $payment?->isPending()) {
            QrisCheckout::refresh($payment);
            $registration->refresh();
        }

        return $registration;
    }
}
