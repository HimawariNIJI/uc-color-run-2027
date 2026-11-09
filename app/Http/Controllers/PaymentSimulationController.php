<?php

namespace App\Http\Controllers;

use App\Support\Midtrans;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Local stand-in for Midtrans, used to try the payment pages before Midtrans keys are set.
 * As soon as MIDTRANS_SERVER_KEY is configured it is switched off: only Midtrans can confirm payments.
 */
class PaymentSimulationController extends Controller
{
    public function __invoke(Request $request, string $outcome): RedirectResponse
    {
        abort_unless(app()->isLocal() && ! Midtrans::isConfigured(), 404);

        $registration = $request->user()->registration;

        if ($outcome === 'paid') {
            $registration->markAsPaid();

            return to_route('payment.success');
        }

        $registration->markAsFailed();

        return to_route('payment.failed');
    }
}
