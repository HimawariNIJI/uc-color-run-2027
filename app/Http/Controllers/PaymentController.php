<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PaymentController extends Controller
{
    /**
     * Display the payment view.
     */
    public function index(Request $request): View
    {
        $participantName = $request->query('name', 'Nama Lengkap');
        $raceCategory = $request->query('category', '5K');
        $tshirtSize = $request->query('tshirt', 'M');
        $amount = (int) $request->query('amount', 150000);

        return view('payment', compact('participantName', 'raceCategory', 'tshirtSize', 'amount'));
    }

    /**
     * Create dynamic QRIS order via Midtrans API.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'participant_name' => 'nullable|string|max:100',
            'race_category' => 'nullable|string|max:20',
            'tshirt_size' => 'nullable|string|max:10',
            'amount' => 'nullable|integer|min:1000',
        ]);

        $participantName = trim($validated['participant_name'] ?? 'Nama Lengkap') ?: 'Nama Lengkap';
        $raceCategory = trim($validated['race_category'] ?? '5K') ?: '5K';
        $tshirtSize = trim($validated['tshirt_size'] ?? 'M') ?: 'M';
        $amount = (int) ($validated['amount'] ?? 150000);
        if ($amount <= 0) {
            $amount = 150000;
        }

        $orderId = 'UCRUN-'.date('YmdHis').'-'.rand(100, 999);
        $itemName = "UC Color Run {$raceCategory}";

        $serverKey = config('midtrans.server_key');
        $apiUrl = config('midtrans.api_url');
        $isMock = empty($serverKey) || str_contains($serverKey, 'YOUR_SERVER_KEY');

        // MOCK MODE FALLBACK (if credentials not provided)
        if ($isMock) {
            $mockQrString = '00020101021226620014COM.GO-JEK.WWW011993600914389103188240210M8910318820303UKE51440014ID.CO.QRIS.WWW0215AID3550710810630303UKE520431725303360540'.strlen((string) $amount).$amount.'5802ID5911UC-Run 20276006BANTUL61055575262395028A1'.$orderId.'6304461A';
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data='.urlencode($mockQrString);

            $order = Order::create([
                'order_id' => $orderId,
                'participant_name' => $participantName,
                'race_category' => $raceCategory,
                'tshirt_size' => $tshirtSize,
                'item_name' => $itemName,
                'amount' => $amount,
                'status' => 'PENDING',
                'qr_url' => $qrUrl,
                'qr_string' => $mockQrString,
                'raw_midtrans_response' => ['mode' => 'mock'],
            ]);

            return response()->json([
                'success' => true,
                'mock_mode' => true,
                'order_id' => $orderId,
                'participant_name' => $participantName,
                'race_category' => $raceCategory,
                'tshirt_size' => $tshirtSize,
                'amount' => $amount,
                'qr_url' => $qrUrl,
                'qr_string' => $mockQrString,
                'message' => 'Demo QRIS generated',
            ]);
        }

        // REAL MIDTRANS INTEGRATION
        $payload = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $amount,
            ],
            'item_details' => [
                [
                    'id' => 'UCRUN-'.$raceCategory,
                    'price' => $amount,
                    'quantity' => 1,
                    'name' => "UC Color Run {$raceCategory} ({$tshirtSize})",
                ],
            ],
            'customer_details' => [
                'first_name' => $participantName,
            ],
            'qris' => [
                'acquirer' => 'gopay',
            ],
        ];

        try {
            $response = Http::withBasicAuth($serverKey, '')
                ->timeout(30)
                ->acceptJson()
                ->post($apiUrl.'/charge', $payload);

            if (! $response->successful()) {
                Log::error('Midtrans charge failed', ['response' => $response->json(), 'status' => $response->status()]);

                return response()->json([
                    'error' => $response->json('status_message') ?? 'Failed to create QRIS with Midtrans',
                    'details' => $response->json(),
                ], $response->status());
            }

            $resData = $response->json();
            $qrUrl = '';
            if (! empty($resData['actions'])) {
                foreach ($resData['actions'] as $action) {
                    if (($action['name'] ?? '') === 'generate-qr-code') {
                        $qrUrl = $action['url'];
                        break;
                    }
                }
            }

            $qrString = $resData['qr_string'] ?? '';

            $order = Order::create([
                'order_id' => $orderId,
                'participant_name' => $participantName,
                'race_category' => $raceCategory,
                'tshirt_size' => $tshirtSize,
                'item_name' => $itemName,
                'amount' => $amount,
                'status' => 'PENDING',
                'qr_url' => $qrUrl,
                'qr_string' => $qrString,
                'raw_midtrans_response' => $resData,
            ]);

            return response()->json([
                'success' => true,
                'mock_mode' => false,
                'order_id' => $orderId,
                'participant_name' => $participantName,
                'race_category' => $raceCategory,
                'tshirt_size' => $tshirtSize,
                'amount' => $amount,
                'qr_url' => $qrUrl,
                'qr_string' => $qrString,
            ]);
        } catch (\Throwable $e) {
            Log::error('Midtrans Exception: '.$e->getMessage());

            return response()->json([
                'error' => 'cURL/HTTP error: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check order status (frontend polling endpoint).
     */
    public function checkStatus(Request $request): JsonResponse
    {
        $orderId = trim($request->query('order_id', ''));

        if (! $orderId) {
            return response()->json(['error' => 'Missing order_id parameter'], 400);
        }

        $order = Order::where('order_id', $orderId)->first();

        if (! $order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // If order is still pending, check status with Midtrans API directly
        if ($order->status === 'PENDING') {
            $serverKey = config('midtrans.server_key');
            $apiUrl = config('midtrans.api_url');
            $isMock = empty($serverKey) || str_contains($serverKey, 'YOUR_SERVER_KEY');

            if (! $isMock) {
                try {
                    $response = Http::withBasicAuth($serverKey, '')
                        ->timeout(10)
                        ->acceptJson()
                        ->get($apiUrl.'/'.urlencode($orderId).'/status');

                    if ($response->successful()) {
                        $midtransData = $response->json();
                        $transactionStatus = $midtransData['transaction_status'] ?? '';
                        $fraudStatus = $midtransData['fraud_status'] ?? '';

                        if ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
                            $order->update(['status' => 'PAID']);
                            self::markRegistrationPaid($orderId);
                        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
                            $order->update(['status' => strtoupper($transactionStatus)]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("Direct Midtrans status query failed for {$orderId}: ".$e->getMessage());
                }
            }
        }

        return response()->json([
            'order_id' => $order->order_id,
            'status' => $order->status,
            'participant_name' => $order->participant_name,
            'race_category' => $order->race_category,
            'tshirt_size' => $order->tshirt_size,
            'item_name' => $order->item_name,
            'amount' => (int) $order->amount,
            'updated_at' => $order->updated_at?->toDateTimeString(),
        ]);
    }

    /**
     * Midtrans HTTP Webhook / Notification Callback.
     */
    public function webhook(Request $request): JsonResponse
    {
        $notification = $request->all();

        if (empty($notification)) {
            return response()->json(['error' => 'Empty notification'], 400);
        }

        $orderId = $notification['order_id'] ?? '';
        $statusCode = $notification['status_code'] ?? '';
        $grossAmount = $notification['gross_amount'] ?? '';
        $signatureKey = $notification['signature_key'] ?? '';
        $transactionStatus = $notification['transaction_status'] ?? '';
        $fraudStatus = $notification['fraud_status'] ?? '';

        $serverKey = config('midtrans.server_key');
        $isMock = empty($serverKey) || str_contains($serverKey, 'YOUR_SERVER_KEY');

        // Verify Signature
        if (! $isMock) {
            $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
            if ($signatureKey !== $expectedSignature) {
                Log::warning("Midtrans Webhook: Invalid signature for order {$orderId}");

                return response()->json(['error' => 'Invalid signature key! Request rejected.'], 403);
            }
        }

        $newStatus = 'PENDING';
        if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
            $newStatus = ($fraudStatus === 'challenge') ? 'CHALLENGED' : 'PAID';
        } elseif ($transactionStatus === 'pending') {
            $newStatus = 'PENDING';
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'])) {
            $newStatus = strtoupper($transactionStatus);
        }

        $order = Order::where('order_id', $orderId)->first();
        if ($order) {
            $order->update([
                'status' => $newStatus,
                'raw_midtrans_response' => $notification,
            ]);

            if ($newStatus === 'PAID') {
                self::markRegistrationPaid($orderId);
                Log::info("Order {$orderId} marked as PAID via webhook.");
            }
        }

        return response()->json(['status' => 'OK', 'order_status' => $newStatus]);
    }

    /**
     * Instant Sandbox payment simulator for local testing.
     */
    public function mockPay(Request $request): JsonResponse
    {
        $orderId = trim($request->input('order_id', ''));

        if (! $orderId) {
            return response()->json(['error' => 'Missing order_id'], 400);
        }

        $order = Order::where('order_id', $orderId)->first();
        if (! $order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        $order->update(['status' => 'PAID']);
        self::markRegistrationPaid($orderId);

        return response()->json([
            'success' => true,
            'message' => "Order {$orderId} has been simulated as PAID!",
        ]);
    }

    /**
     * Helper to mark associated registration as Paid.
     */
    public static function markRegistrationPaid(string $orderId): void
    {
        $registration = Registration::where('order_id', $orderId)->first();
        if ($registration && $registration->payment_status !== 'Paid') {
            $registration->update([
                'payment_status' => 'Paid',
                'bib_number' => $registration->bib_number ?: ('UC27'.str_pad((string) $registration->id, 3, '0', STR_PAD_LEFT)),
            ]);
        }
    }
}
