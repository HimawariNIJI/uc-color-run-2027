<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Local-only stand-in for the payment gateway webhook, used to test the payment pages.
 */
class PaymentSimulationController extends Controller
{
    public function __invoke(Request $request, string $outcome): RedirectResponse
    {
        abort_unless(app()->isLocal(), 404);

        $registration = $request->user()->registration;

        if ($outcome === 'paid') {
            $registration->markAsPaid();

            return to_route('payment.success');
        }

        $registration->markAsFailed();

        return to_route('payment.failed');
    }
}
