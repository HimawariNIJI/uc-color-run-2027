<?php

use App\Models\Payment;
use App\Models\Registration;
use App\Models\TicketPrice;
use App\Models\User;
use App\Support\IdentityPhoto;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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
    ->beforeEach(function () {
        fakeMidtrans();
        Storage::fake(IdentityPhoto::DISK);
    })
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

            if (app()->bound('midtrans.fake.unknown')) {
                return Http::response(['status_code' => '404', 'status_message' => "Transaction doesn't exist."], 404);
            }

            $status = app()->bound('midtrans.fake.orders') ? (app('midtrans.fake.orders')[$orderId] ?? null) : null;

            if ($status === false) {
                return Http::response(['status_code' => '503', 'status_message' => 'Service unavailable.'], 503);
            }

            return Http::response([
                'status_code' => '200',
                'order_id' => $orderId,
                'gross_amount' => number_format($payment->amount ?? 0, 2, '.', ''),
                'transaction_id' => $payment?->gateway_transaction_id,
                ...($status ?? app('midtrans.fake.status')),
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
 * Make the fake Midtrans answer every status check with "Transaction doesn't exist" (HTTP 404).
 */
function midtransDoesNotKnowOrders(): void
{
    app()->instance('midtrans.fake.unknown', true);
}

/**
 * How many QRIS transactions were requested from the fake Midtrans.
 */
function qrisChargesSent(): int
{
    return Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), '/v2/charge'))->count();
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
 * What the fake Midtrans reports for one order only, overriding midtransReports(). Pass null as
 * the status to make Midtrans unreachable for that order (HTTP 503).
 *
 * @param  array<string, mixed>  $extra  more response fields, such as settlement_time
 */
function midtransReportsFor(string $orderId, ?string $transactionStatus, array $extra = []): void
{
    $orders = app()->bound('midtrans.fake.orders') ? app('midtrans.fake.orders') : [];
    $orders[$orderId] = $transactionStatus === null ? false : ['transaction_status' => $transactionStatus, 'fraud_status' => 'accept', ...$extra];

    app()->instance('midtrans.fake.orders', $orders);
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
        'ktp_photo' => UploadedFile::fake()->image('ktp.jpg', 1200, 760),
        'tshirt_size' => 'M',
        'emergency_contact_name' => 'Siti Santoso',
        'emergency_contact_phone' => '081298765432',
        'medical_history' => 'Tidak Ada',
        'drug_allergies' => 'Tidak Ada',
        'province' => 'Jawa Timur',
        'city' => 'Kota Surabaya',
        'address' => 'Jl. Raya CitraLand No. 1, Surabaya',
        'agreement' => '1',
        ...$overrides,
    ];
}

/**
 * A valid bundle member row for a Couple Bundle submission (with a KTP/Passport number).
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
        'medical_history' => 'Tidak Ada',
        'drug_allergies' => 'Tidak Ada',
        ...$overrides,
    ];
}

/**
 * Register through the real form for one package and return the registration.
 * $package is "family", "couple" or "{type}/{price type slug}/{distance}".
 */
function registerForBib(TestCase $test, string $package): Registration
{
    if (in_array($package, ['family', 'couple'], true)) {
        $user = User::factory()->create();
        $payload = $package === 'family' ? familyBundlePayload() : registrationPayload(['members' => [bundleMember()]]);

        $test->actingAs($user)->post(route('registration.bundle.store', $package), $payload)->assertRedirect(route('payment.show'));
    } else {
        [$type, $priceType, $category] = explode('/', $package);
        $user = $type === 'internal' ? User::factory()->ucEmail()->create() : User::factory()->create();

        $test->actingAs($user)->post(route('registration.store', [$type, $priceType, $category]), registrationPayload())->assertRedirect(route('payment.show'));
    }

    return $user->registration()->sole();
}

/**
 * A valid Family Bundle submission: the payer with a Kartu Keluarga photo instead of a KTP photo,
 * and exactly 3 more family members without KTP/Passport numbers.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function familyBundlePayload(array $overrides = []): array
{
    $payload = registrationPayload([
        'family_card_photo' => UploadedFile::fake()->image('kk.jpg', 1100, 780),
        'members' => [
            familyMember(['full_name' => 'Indah Wijaya']),
            familyMember(['full_name' => 'Joko Wijaya', 'gender' => 'male']),
            familyMember(['full_name' => 'Kartika Wijaya']),
        ],
        ...$overrides,
    ]);
    unset($payload['ktp_photo']);

    return $payload;
}

/**
 * A valid Family Bundle member row: no KTP/Passport number.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function familyMember(array $overrides = []): array
{
    return [
        'full_name' => 'Indah Wijaya',
        'phone' => '',
        'birth_date' => '2015-02-14',
        'gender' => 'female',
        'tshirt_size' => 'S',
        'medical_history' => 'Tidak Ada',
        'drug_allergies' => 'Tidak Ada',
        ...$overrides,
    ];
}

/**
 * The Google Sheets value of a Jakarta date or date and time: days since 30 December 1899.
 */
function sheetDate(string $local, bool $withTime = true): int|float
{
    $days = Carbon::parse($local, 'UTC')->getTimestamp() / 86400 + 25569;

    return $withTime ? round($days, 8) : (int) $days;
}

/**
 * An uploaded file with real content, so its type is read from the content like in production.
 * (UploadedFile::fake() guesses the type from the file name instead.)
 */
function uploadedFile(string $name, string $contents, int $error = UPLOAD_ERR_OK): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'upload');
    file_put_contents($path, $contents);

    return new UploadedFile($path, $name, null, $error, test: true);
}

/**
 * An uploaded image drawn with GD in the given format: "jpeg", "png" or "gif".
 */
function uploadedImage(string $name, string $format = 'jpeg', int $width = 856, int $height = 540): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    ob_start();
    match ($format) {
        'png' => imagepng($image),
        'gif' => imagegif($image),
        default => imagejpeg($image),
    };

    return uploadedFile($name, (string) ob_get_clean());
}
