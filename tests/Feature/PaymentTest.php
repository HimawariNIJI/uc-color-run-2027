<?php

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('payment page can be rendered', function () {
    $response = $this->get('/payment');

    $response->assertStatus(200);
    $response->assertSee('Selesaikan Pembayaran');
    $response->assertSee('Bayar dengan QRIS');
});

test('order can be created via payment create endpoint', function () {
    Http::fake([
        'https://api.sandbox.midtrans.com/v2/charge' => Http::response([
            'status_code' => '201',
            'status_message' => 'Success, QRIS transaction is created',
            'transaction_id' => 'abc-123',
            'order_id' => 'UCRUN-TEST',
            'gross_amount' => '150000.00',
            'payment_type' => 'qris',
            'transaction_time' => '2026-10-31 01:28:00',
            'transaction_status' => 'pending',
            'qr_string' => '00020101021226620014COM.GO-JEK.WWW',
            'actions' => [
                [
                    'name' => 'generate-qr-code',
                    'method' => 'GET',
                    'url' => 'https://api.sandbox.midtrans.com/v2/qris/abc-123/qr-code',
                ],
            ],
        ], 201),
    ]);

    $response = $this->postJson('/payment/create-order', [
        'participant_name' => 'John Doe',
        'race_category' => '5K',
        'tshirt_size' => 'L',
        'amount' => 150000,
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'order_id',
        'participant_name',
        'race_category',
        'tshirt_size',
        'amount',
        'qr_url',
        'qr_string',
    ]);

    $orderId = $response->json('order_id');
    expect(Order::where('order_id', $orderId)->exists())->toBeTrue();
});

test('order status can be checked', function () {
    $order = Order::create([
        'order_id' => 'UCRUN-TEST-001',
        'participant_name' => 'Jane Doe',
        'race_category' => '10K',
        'tshirt_size' => 'M',
        'item_name' => 'UC Color Run 10K',
        'amount' => 200000,
        'status' => 'PENDING',
    ]);

    $response = $this->getJson('/payment/check-status?order_id='.$order->order_id);

    $response->assertStatus(200);
    $response->assertJson([
        'order_id' => 'UCRUN-TEST-001',
        'status' => 'PENDING',
        'participant_name' => 'Jane Doe',
        'race_category' => '10K',
        'amount' => 200000,
    ]);
});

test('instant mock payment updates status to PAID', function () {
    $order = Order::create([
        'order_id' => 'UCRUN-TEST-002',
        'participant_name' => 'Tester',
        'race_category' => '5K',
        'tshirt_size' => 'S',
        'item_name' => 'UC Color Run 5K',
        'amount' => 150000,
        'status' => 'PENDING',
    ]);

    $response = $this->postJson('/payment/mock-pay', [
        'order_id' => $order->order_id,
    ]);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $order->refresh();
    expect($order->status)->toBe('PAID');
});

test('webhook updates order status on settlement', function () {
    $order = Order::create([
        'order_id' => 'UCRUN-TEST-003',
        'participant_name' => 'Runner A',
        'race_category' => '5K',
        'tshirt_size' => 'XL',
        'item_name' => 'UC Color Run 5K',
        'amount' => 150000,
        'status' => 'PENDING',
    ]);

    $serverKey = config('midtrans.server_key');
    $statusCode = '200';
    $grossAmount = '150000.00';
    $signature = hash('sha512', $order->order_id.$statusCode.$grossAmount.$serverKey);

    $response = $this->postJson('/payment/webhook', [
        'order_id' => $order->order_id,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'signature_key' => $signature,
        'transaction_status' => 'settlement',
        'fraud_status' => 'accept',
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'status' => 'OK',
        'order_status' => 'PAID',
    ]);

    $order->refresh();
    expect($order->status)->toBe('PAID');
});
