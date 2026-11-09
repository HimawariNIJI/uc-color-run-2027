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
        $orderId = $request->query('order_id');
        $participantName = $request->query('name');
        $raceCategory = $request->query('category');
        $tshirtSize = $request->query('tshirt');
        $amount = (int) $request->query('amount', 0);

        // Fallback to active session registration if parameters were not provided
        if ((! $orderId || ! $participantName) && session('registered_id')) {
            $reg = Registration::find(session('registered_id'));
            if ($reg) {
                $orderId = $orderId ?: $reg->order_id;
                $participantName = $participantName ?: $reg->full_name;
                $raceCategory = $raceCategory ?: $reg->race_category;
                $tshirtSize = $tshirtSize ?: $reg->tshirt_size;
                $amount = $amount ?: $reg->amount;
            }
        }

        $participantName = $participantName ?: 'Nama Lengkap';
        $raceCategory = $raceCategory ?: '5K';
        $tshirtSize = $tshirtSize ?: 'M';
        $amount = $amount ?: 150000;

        return view('payment', compact('orderId', 'participantName', 'raceCategory', 'tshirtSize', 'amount'));
    }

    /**
     * Create dynamic QRIS order via Midtrans API.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'nullable|string|max:100',
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

        // Reuse existing orderId if provided or linked to active registration
        $orderId = trim($validated['order_id'] ?? '');
        if (! $orderId && session('registered_id')) {
            $reg = Registration::find(session('registered_id'));
            if ($reg && $reg->order_id) {
                $orderId = $reg->order_id;
            }
        }
        if (! $orderId) {
            $orderId = 'UCRUN-'.date('YmdHis').'-'.rand(100, 999);
        }

        // Ensure session registration is linked to this order
        if (session('registered_id')) {
            $reg = Registration::find(session('registered_id'));
            if ($reg && (! $reg->order_id || $reg->order_id !== $orderId)) {
                $reg->update(['order_id' => $orderId]);
            }
        }
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
     * Helper to mark associated registration as Paid and assign a BIB number.
     *
     * BIB format: {km_prefix}{4 random digits}
     * - 3K  → starts with 3  (e.g. 31042)
     * - 5K  → starts with 5  (e.g. 50837)
     * - 10K → starts with 1  (e.g. 10294)
     */
    public static function markRegistrationPaid(string $orderId): void
    {
        $registration = Registration::where('order_id', $orderId)->first();

        // Fallback to active registration in session if not matched by order_id
        if (! $registration && session('registered_id')) {
            $registration = Registration::find(session('registered_id'));
            if ($registration && (! $registration->order_id || $registration->order_id !== $orderId)) {
                $registration->update(['order_id' => $orderId]);
            }
        }

        if (! $registration || $registration->isPaid()) {
            return;
        }

        $registration->markAsPaid();
    }

    /**
     * Generate a unique BIB number for a given race category.
     *
     * Prefix rules (matches RaceCategory::bibPrefix()):
     *   3k  → '3'   (5-digit BIB: 3XXXX)
     *   5k  → '5'   (5-digit BIB: 5XXXX)
     *   10k → '1'   (5-digit BIB: 1XXXX)
     */
    public static function generateBibNumber(string $raceCategory): string
    {
        // Normalise to lower-case short form
        $prefix = match (strtolower(trim($raceCategory))) {
            '3k', '3k run', '3 km' => '3',
            '5k', '5k run', '5 km' => '5',
            '10k', '10k run', '10 km' => '1',
            default => '5', // fallback to 5K prefix
        };

        // Try up to 20 times to find a unique BIB
        $attempts = 0;
        do {
            $bib = $prefix.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $exists = Registration::where('bib_number', $bib)->exists();
            $attempts++;
        } while ($exists && $attempts < 20);

        return $bib;
    }
}
