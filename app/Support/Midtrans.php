<?php

namespace App\Support;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Midtrans Core API for QRIS payments, called with Laravel's HTTP client (sandbox unless
 * MIDTRANS_IS_PRODUCTION=true). The server key stays on the backend and is never logged.
 */
class Midtrans
{
    public static function isConfigured(): bool
    {
        return filled(config('services.midtrans.server_key'));
    }

    /**
     * Create a QRIS transaction. Only "qris" is requested, so no other payment method exists for it.
     *
     * @param  array{first_name: string, email: string, phone: string}  $customer
     * @return array<string, mixed> the Midtrans response, including qr_string, actions and expiry_time
     *
     * @throws RuntimeException when Midtrans does not create the transaction
     */
    public static function chargeQris(string $orderId, int $amount, string $itemId, string $itemName, array $customer, int $expiryMinutes): array
    {
        $response = self::client()->post('/v2/charge', [
            'payment_type' => 'qris',
            'transaction_details' => ['order_id' => $orderId, 'gross_amount' => $amount],
            'item_details' => [['id' => $itemId, 'price' => $amount, 'quantity' => 1, 'name' => mb_substr($itemName, 0, 50)]],
            'customer_details' => $customer,
            'qris' => ['acquirer' => config('services.midtrans.qris_acquirer')],
            'custom_expiry' => ['expiry_duration' => $expiryMinutes, 'unit' => 'minute'],
        ]);

        $body = (array) $response->json();

        if ($response->failed() || ($body['status_code'] ?? null) !== '201' || blank($body['qr_string'] ?? null)) {
            throw new RuntimeException(sprintf(
                'Midtrans did not create the QRIS transaction for %s (HTTP %d, status %s: %s).',
                $orderId, $response->status(), $body['status_code'] ?? '-', $body['status_message'] ?? 'no message',
            ));
        }

        return $body;
    }

    /**
     * The current status of a transaction, straight from Midtrans.
     *
     * @return array<string, mixed>
     *
     * @throws RuntimeException when Midtrans does not know the order or cannot be reached
     */
    public static function status(string $orderId): array
    {
        $response = self::client()->get('/v2/'.rawurlencode($orderId).'/status');
        $body = (array) $response->json();

        if ($response->failed() || blank($body['transaction_status'] ?? null)) {
            throw new RuntimeException(sprintf(
                'Midtrans status for %s is unavailable (HTTP %d, status %s: %s).',
                $orderId, $response->status(), $body['status_code'] ?? '-', $body['status_message'] ?? 'no message',
            ));
        }

        return $body;
    }

    /**
     * Midtrans signs every notification with SHA-512 of order_id + status_code + gross_amount + server key.
     *
     * @param  array<string, mixed>  $notification
     */
    public static function hasValidSignature(array $notification): bool
    {
        $signature = $notification['signature_key'] ?? null;

        if (! self::isConfigured() || ! is_string($signature)) {
            return false;
        }

        $expected = hash('sha512', implode('', [
            (string) ($notification['order_id'] ?? ''),
            (string) ($notification['status_code'] ?? ''),
            (string) ($notification['gross_amount'] ?? ''),
            (string) config('services.midtrans.server_key'),
        ]));

        return hash_equals($expected, $signature);
    }

    private static function client(): PendingRequest
    {
        return Http::baseUrl(config('services.midtrans.is_production') ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com')
            ->withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(20);
    }
}
