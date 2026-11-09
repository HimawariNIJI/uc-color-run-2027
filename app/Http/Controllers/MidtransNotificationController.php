<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Support\Midtrans;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * Midtrans HTTP notifications (set as the Payment Notification URL in the Midtrans dashboard).
 * A notification is only trusted after its SHA-512 signature checks out, and even then the status
 * is fetched from Midtrans again before anything changes. Repeated notifications are harmless.
 */
class MidtransNotificationController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $notification = $request->all();

        if (! Midtrans::hasValidSignature($notification)) {
            return response('Invalid signature.', 403);
        }

        $payment = Payment::query()->where('order_id', (string) $notification['order_id'])->first();

        if ($payment === null) {
            return response('Unknown order.', 404);
        }

        try {
            $payment->applyGatewayStatus(Midtrans::status($payment->order_id));
        } catch (Throwable $exception) {
            report($exception);

            // Midtrans retries notifications that do not get a 2xx response.
            return response('Could not verify the transaction yet.', 503);
        }

        return response('OK');
    }
}
