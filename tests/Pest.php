<?php

use App\Models\Payment;
use App\Models\TicketPrice;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->beforeEach(fn () => fakeMidtrans())
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Stand in for the Midtrans sandbox. QRIS charges succeed and status checks answer with what
 * midtransReports() set (pending by default). Any other outgoing HTTP request fails the test.
 */
function fakeMidtrans(): void
{
    config(['services.midtrans.server_key' => 'SB-Mid-server-TEST', 'services.midtrans.is_production' => false]);
    midtransReports('pending');

    Http::preventStrayRequests();
    Http::fake([
        'api.sandbox.midtrans.com/v2/charge' => fn (Request $request) => app()->bound('midtrans.fake.refuse')
            // Midtrans answers some refusals with HTTP 200 and the real status code in the body.
            ? Http::response(['status_code' => '402', 'status_message' => 'Payment channel is not activated.'])
            : Http::response([
                'status_code' => '201',
                'status_message' => 'QRIS transaction is created',
                'transaction_id' => (string) Str::uuid(),
                'order_id' => $request['transaction_details']['order_id'],
                'gross_amount' => $request['transaction_details']['gross_amount'].'.00',
                'payment_type' => 'qris',
                'transaction_status' => 'pending',
                'actions' => [['name' => 'generate-qr-code', 'method' => 'GET', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/test/qr-code']],
                'qr_string' => '00020101021226620014COM.GO-JEK.WWW01189360091434'.$request['transaction_details']['order_id'],
                'expiry_time' => now('Asia/Jakarta')->addMinutes(60)->format('Y-m-d H:i:s'),
            ], 201),
        'api.sandbox.midtrans.com/v2/*/status' => function (Request $request) {
            $orderId = rawurldecode(Str::between($request->url(), '/v2/', '/status'));
            $payment = Payment::query()->firstWhere('order_id', $orderId);

            return Http::response([
                'status_code' => '200',
                'order_id' => $orderId,
                'gross_amount' => number_format($payment->amount ?? 0, 2, '.', ''),
                'transaction_id' => $payment?->gateway_transaction_id,
                ...app('midtrans.fake.status'),
            ]);
        },
    ]);
}

/**
 * Make the fake Midtrans refuse to create QRIS transactions.
 */
function midtransRefusesCharges(): void
{
    app()->instance('midtrans.fake.refuse', true);
}

/**
 * What the fake Midtrans reports for status checks, for example "settlement" or "expire".
 *
 * @param  array<string, mixed>  $extra  more response fields, such as a different gross_amount
 */
function midtransReports(string $transactionStatus, array $extra = []): void
{
    app()->instance('midtrans.fake.status', ['transaction_status' => $transactionStatus, 'fraud_status' => 'accept', ...$extra]);
}

/**
 * A notification signed the way Midtrans signs it, with the fake server key.
 *
 * @return array<string, string>
 */
function midtransNotification(Payment $payment, string $transactionStatus, string $statusCode = '200'): array
{
    $grossAmount = number_format($payment->amount, 2, '.', '');

    return [
        'order_id' => $payment->order_id,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => $transactionStatus,
        'payment_type' => 'qris',
        'signature_key' => hash('sha512', $payment->order_id.$statusCode.$grossAmount.config('services.midtrans.server_key')),
    ];
}

/**
 * Change one package in ticket_prices, found by registration type, price type (null for a bundle)
 * and distance, for example setPackage('external', 'early_bird', '5k', ['quota' => 1]).
 *
 * @param  array<string, mixed>  $overrides
 */
function setPackage(string $type, ?string $priceType, string $category, array $overrides): void
{
    TicketPrice::query()
        ->where('registration_type', $type)
        ->where('price_type', $priceType)
        ->where('race_category', $category)
        ->update($overrides);
}

/**
 * Give the packages whose quota was not announced a test quota, so every package can be registered.
 */
function openAllPackages(): void
{
    TicketPrice::query()->whereNull('quota')->update(['quota' => 100]);
}

/**
 * A valid participant form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function registrationPayload(array $overrides = []): array
{
    return [
        'full_name' => 'Budi Santoso',
        'phone' => '081234567890',
        'birth_date' => '1999-05-12',
        'gender' => 'male',
        'identity_number' => '3578011205990001',
        'tshirt_size' => 'M',
        'emergency_contact_name' => 'Siti Santoso',
        'emergency_contact_phone' => '081298765432',
        'province' => 'Jawa Timur',
        'city' => 'Kota Surabaya',
        'address' => 'Jl. Raya CitraLand No. 1, Surabaya',
        'agreement' => '1',
        ...$overrides,
    ];
}

/**
 * A valid bundle member row for a bundle form submission.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function bundleMember(array $overrides = []): array
{
    return [
        'full_name' => 'Siti Santoso',
        'phone' => '',
        'birth_date' => '2000-02-14',
        'gender' => 'female',
        'identity_number' => '3578011402000002',
        'tshirt_size' => 'S',
        ...$overrides,
    ];
}
