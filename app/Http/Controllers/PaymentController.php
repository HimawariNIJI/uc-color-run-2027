<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Registration;
use App\Support\Midtrans;
use App\Support\QrisCheckout;
use App\Support\Rupiah;
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
     * Start a new QRIS after a failed or expired one. Never creates a second QRIS while one can
     * still be scanned, and claims a slot again first, at the package's current price.
     */
    public function retry(Request $request): RedirectResponse
    {
        $registration = $request->user()->registration;
        $payment = $this->currentPayment($registration);

        if ($registration->isPaid()) {
            return to_route('payment.success');
        }

        if ($payment?->isActive()) {
            return to_route('payment.show');
        }

        $moved = false;

        if ($registration->hasFailedPayment() || $registration->paymentHasExpired()) {
            ['opened' => $opened, 'moved' => $moved, 'message' => $message] = QrisCheckout::reopen($registration);

            if (! $opened) {
                return to_route('payment.failed')->with('error', $message);
            }
        }

        if ($error = QrisCheckout::start($registration)) {
            return to_route('payment.failed')->with('error', $error);
        }

        return to_route('payment.show')->with('info', $moved
            ? "Kuota Early Bid sudah habis, sehingga registrasimu dipindahkan ke {$registration->packageName()} (".Rupiah::format($registration->amount).').'
            : null);
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
        ]);
    }

    /**
     * The latest payment attempt, refreshed with Midtrans once its QRIS has expired.
     */
    private function currentPayment(Registration $registration): ?Payment
    {
        $payment = $registration->latestPayment;

        if ($payment?->isPending() && $payment->hasExpired()) {
            QrisCheckout::refresh($payment);
            $registration->refresh();
        }

        return $payment?->fresh();
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
