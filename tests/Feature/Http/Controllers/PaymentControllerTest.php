<?php

use App\Enums\FeePeriod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Registration;
use App\Models\RegistrationMember;
use App\Models\User;
use App\Support\QrisCheckout;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * An External 5K Early Bid registration (number 7 reserved) with its Midtrans QRIS created.
 *
 * @param  array<string, mixed>  $attributes
 */
function registrationWithQris(array $attributes = []): Registration
{
    $registration = Registration::factory()->create(['code' => 'UCCR-0501020007', 'type' => 'external', 'category' => '5k', ...$attributes]);
    $registration->startQrisPayment();

    return $registration->fresh();
}

/**
 * How many QRIS transactions were requested from the fake Midtrans.
 */
function qrisChargesSent(): int
{
    return Http::recorded(fn (Request $request): bool => str_ends_with($request->url(), '/v2/charge'))->count();
}

it('asks guests to sign in', function () {
    $this->get(route('payment.show'))->assertRedirect(route('login'));
});

it('sends participants without a registration to My Registration', function (string $method, string $routeName) {
    $this->actingAs(User::factory()->create())
        ->call($method, route($routeName))
        ->assertRedirect(route('my-registration.show'));
})->with([
    ['GET', 'payment.show'],
    ['POST', 'payment.check'],
    ['GET', 'payment.status'],
    ['POST', 'payment.retry'],
    ['GET', 'payment.success'],
    ['GET', 'payment.failed'],
]);

it('shows the Midtrans QRIS with the amount, registration code, status and expiry time', function () {
    $registration = registrationWithQris(['full_name' => 'Budi Santoso']);
    $payment = $registration->latestPayment;

    $this->actingAs($registration->user)
        ->get(route('payment.show'))
        ->assertOk()
        ->assertSee(['Budi Santoso', '5K External', 'Rp100.000', 'UCCR-0501020007', 'Menunggu pembayaran', 'UCCR-0501020007-1', $payment->expires_at->translatedFormat('j F Y, H.i')])
        ->assertSee(['<svg', 'data-payment-status="'.route('payment.status').'"'], false)
        ->assertDontSee(['Buat Kode QRIS', 'Simulasi pembayaran']);
});

it('offers to create the QRIS when a registration does not have one yet', function () {
    $registration = Registration::factory()->create(['type' => 'external', 'category' => '3k']);

    $this->actingAs($registration->user)->get(route('payment.show'))->assertOk()->assertSee('Buat Kode QRIS');
    $this->actingAs($registration->user)->post(route('payment.retry'))->assertRedirect(route('payment.show'));

    expect($registration->payments()->sole()->status)->toBe(TransactionStatus::Pending)
        ->and(qrisChargesSent())->toBe(1);
});

it('never creates a second QRIS while the current one can still be scanned', function () {
    $registration = registrationWithQris();

    $this->actingAs($registration->user)->post(route('payment.retry'))->assertRedirect(route('payment.show'));

    expect($registration->payments()->count())->toBe(1)
        ->and(qrisChargesSent())->toBe(1);
});

it('keeps the participant waiting while Midtrans reports the payment as pending', function () {
    $registration = registrationWithQris();

    $this->actingAs($registration->user)
        ->post(route('payment.check'))
        ->assertRedirect(route('payment.show'))
        ->assertSessionHas('info', 'Pembayaran belum kami terima. Selesaikan pembayaran, lalu cek kembali statusnya.');

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('confirms the registration and issues the reserved BIB once Midtrans reports the payment as settled', function () {
    $registration = registrationWithQris();
    midtransReports('settlement', ['settlement_time' => '2026-11-09 10:15:00']);

    $this->actingAs($registration->user)
        ->post(route('payment.check'))
        ->assertRedirect(route('payment.success'));

    $registration->refresh();
    expect($registration)
        ->payment_status->toBe(PaymentStatus::Paid)
        ->bib_number->toBe('0501020007')
        ->transaction_id->toBe('UCCR-0501020007-1')
        ->and($registration->latestPayment)
        ->status->toBe(TransactionStatus::Success)
        ->paid_at->format('Y-m-d H:i')->toBe('2026-11-09 10:15');
});

it('lets the payment page poll the status that Midtrans reports', function () {
    $registration = registrationWithQris();

    $this->actingAs($registration->user)
        ->getJson(route('payment.status'))
        ->assertOk()
        ->assertExactJson(['status' => 'pending', 'redirect' => null]);

    midtransReports('settlement');

    $this->getJson(route('payment.status'))
        ->assertExactJson(['status' => 'paid', 'redirect' => route('payment.success')]);
});

it('marks an expired QRIS as failed and gives the slot back', function () {
    setPackage('external', 'early_bird', '5k', ['quota' => 1]);
    $registration = registrationWithQris();
    $this->travel(61)->minutes();
    midtransReports('expire');

    $this->actingAs($registration->user)
        ->get(route('payment.show'))
        ->assertRedirect(route('payment.failed'));

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Failed)
        ->and($registration->latestPayment()->first()->status)->toBe(TransactionStatus::Expired)
        ->and($registration->fresh()->package()->remaining())->toBe(1);
});

it('still confirms a payment that Midtrans settled just before the QRIS expired', function () {
    $registration = registrationWithQris();
    $this->travel(61)->minutes();
    midtransReports('settlement');

    $this->actingAs($registration->user)
        ->get(route('payment.show'))
        ->assertRedirect(route('payment.success'));

    expect($registration->fresh()->isPaid())->toBeTrue();
});

it('sends paid participants to the success page', function (string $method, string $routeName) {
    $registration = Registration::factory()->paid()->create();

    $this->actingAs($registration->user)
        ->call($method, route($routeName))
        ->assertRedirect(route('payment.success'));
})->with([
    ['GET', 'payment.show'],
    ['POST', 'payment.check'],
    ['POST', 'payment.retry'],
    ['GET', 'payment.failed'],
]);

it('shows the success page with the BIB numbers once paid', function () {
    $registration = Registration::factory()->bundle('couple')->paid()->create(['bib_number' => '0500030001']);
    RegistrationMember::factory()->for($registration)->create(['full_name' => 'Gita Permata', 'bib_number' => '0500030002']);

    $this->actingAs($registration->user)
        ->get(route('payment.success'))
        ->assertOk()
        ->assertSee(['Pembayaran Berhasil!', 'LUNAS', 'Registrasi Terkonfirmasi', $registration->code, '0500030001', 'Gita Permata', '0500030002']);
});

it('never marks a registration paid because the participant opens the success page', function () {
    $registration = registrationWithQris();

    $this->actingAs($registration->user)
        ->get(route('payment.success', ['status' => 'paid', 'transaction_status' => 'settlement']))
        ->assertRedirect(route('payment.show'));

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
});

it('shows the failed page for a failed payment', function () {
    $registration = Registration::factory()->failed()->create();

    $this->actingAs($registration->user)
        ->get(route('payment.failed'))
        ->assertOk()
        ->assertSee(['Pembayaran Gagal', 'BELUM LUNAS', 'Coba Bayar Lagi']);

    $this->actingAs($registration->user)
        ->get(route('payment.show'))
        ->assertRedirect(route('payment.failed'));
});

it('keeps participants with a pending payment away from the failed page', function () {
    $registration = registrationWithQris();

    $this->actingAs($registration->user)
        ->get(route('payment.failed'))
        ->assertRedirect(route('payment.show'));
});

it('starts a new QRIS after a failed payment, at the package price now in the database', function () {
    setPackage('external', 'early_bird', '3k', ['price' => 120000]);
    $registration = Registration::factory()->failed()->create(['code' => 'UCCR-0301020003', 'type' => 'external', 'category' => '3k']);
    $registration->payments()->create(['order_id' => 'UCCR-0301020003-1', 'amount' => 100000, 'status' => TransactionStatus::Expired]);

    $this->actingAs($registration->user)
        ->post(route('payment.retry'))
        ->assertRedirect(route('payment.show'));

    $registration->refresh();
    expect($registration)
        ->payment_status->toBe(PaymentStatus::Unpaid)
        ->amount->toBe(120000)
        ->and($registration->latestPayment)
        ->order_id->toBe('UCCR-0301020003-2')
        ->amount->toBe(120000)
        ->status->toBe(TransactionStatus::Pending);
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v2/charge') && $request['transaction_details']['gross_amount'] === 120000);
});

it('keeps a paid registration at the amount it paid when the price changes', function () {
    $registration = Registration::factory()->paid()->create(['type' => 'external', 'category' => '5k']);
    setPackage('external', 'early_bird', '5k', ['price' => 150000]);

    expect($registration->fresh()->amount)->toBe(100000);
});

it('keeps the Early Bid package when retrying while a slot is free', function () {
    setPackage('external', 'early_bird', '5k', ['quota' => 1]);
    $registration = Registration::factory()->failed()->create(['type' => 'external', 'category' => '5k']);

    $this->actingAs($registration->user)
        ->post(route('payment.retry'))
        ->assertRedirect(route('payment.show'))
        ->assertSessionMissing('info');

    expect($registration->fresh())
        ->fee_period->toBe(FeePeriod::EarlyBird)
        ->payment_status->toBe(PaymentStatus::Unpaid);
});

it('moves a failed Early Bid registration to Normal Bid when the Early Bid slots ran out', function () {
    openAllPackages();
    setPackage('external', 'early_bird', '5k', ['quota' => 1]);
    setPackage('external', 'normal', '5k', ['price' => 175000]);
    $registration = Registration::factory()->failed()->create(['type' => 'external', 'category' => '5k']);
    Registration::factory()->paid()->create(['type' => 'external', 'category' => '5k']);

    $this->actingAs($registration->user)
        ->post(route('payment.retry'))
        ->assertRedirect(route('payment.show'))
        ->assertSessionHas('info', 'Kuota Early Bid sudah habis, sehingga registrasimu dipindahkan ke 5K Normal Bid (Rp175.000).');

    expect($registration->fresh())
        ->fee_period->toBe(FeePeriod::Regular)
        ->amount->toBe(175000)
        ->payment_status->toBe(PaymentStatus::Unpaid);
});

it('does not reopen a failed payment when its package and the Normal Bid package are full', function () {
    setPackage('internal', 'early_bird', '10k', ['quota' => 1]);
    setPackage('internal', 'normal', '10k', ['quota' => 1]);
    $registration = Registration::factory()->failed()->create(['type' => 'internal', 'category' => '10k']);
    Registration::factory()->paid()->create(['type' => 'internal', 'category' => '10k']);
    Registration::factory()->paid()->normalPrice()->create(['type' => 'internal', 'category' => '10k']);

    $this->actingAs($registration->user)
        ->post(route('payment.retry'))
        ->assertRedirect(route('payment.failed'))
        ->assertSessionHas('error', 'Kuota paket 10K Early Bid (Internal) sudah habis. Silakan pilih paket lain.');

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Failed)
        ->and(qrisChargesSent())->toBe(0);
});

it('explains when Midtrans refuses to create the new QRIS', function () {
    midtransRefusesCharges();
    $registration = Registration::factory()->failed()->create(['type' => 'external', 'category' => '3k']);

    $this->actingAs($registration->user)
        ->post(route('payment.retry'))
        ->assertRedirect(route('payment.failed'))
        ->assertSessionHas('error', QrisCheckout::UNAVAILABLE_MESSAGE);

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Failed);
});

it('keeps unpaid registrations away from QRIS payment while the package price is not set', function (string $method, string $routeName) {
    $this->app['env'] = 'local';
    $this->withoutMiddleware(PreventRequestForgery::class);
    setPackage('external', 'early_bird', '3k', ['price' => null]);
    $registration = Registration::factory()->create(['type' => 'external', 'category' => '3k']);

    $this->actingAs($registration->user)
        ->call($method, route($routeName, $routeName === 'payment.simulate' ? 'paid' : []))
        ->assertRedirect(route('my-registration.show'))
        ->assertSessionHas('info', 'Harga resmi paket registrasimu belum diumumkan, jadi pembayaran belum bisa dilakukan. Panitia akan memberi kabar setelah harga diumumkan.');

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and(qrisChargesSent())->toBe(0);
})->with([
    'payment page' => ['GET', 'payment.show'],
    'status check' => ['POST', 'payment.check'],
    'retry' => ['POST', 'payment.retry'],
    'simulated payment' => ['POST', 'payment.simulate'],
]);

describe('local payment simulation before Midtrans is configured', function () {
    beforeEach(function () {
        $this->app['env'] = 'local';
        $this->withoutMiddleware(PreventRequestForgery::class);
        config(['services.midtrans.server_key' => null]);
    });

    it('confirms the payment and issues a BIB number', function () {
        $registration = Registration::factory()->create(['type' => 'external', 'category' => '5k']);

        $this->actingAs($registration->user)
            ->post(route('payment.simulate', 'paid'))
            ->assertRedirect(route('payment.success'));

        expect($registration->fresh())
            ->payment_status->toBe(PaymentStatus::Paid)
            ->paid_at->not->toBeNull()
            ->bib_number->toBe('0501020001');
    });

    it('records a transaction ID for the confirmed payment', function () {
        $registration = Registration::factory()->create();

        $this->actingAs($registration->user)->post(route('payment.simulate', 'paid'));

        expect($registration->fresh()->transaction_id)->toBe('PAY-'.$registration->code);
    });

    it('fails the payment', function () {
        $registration = Registration::factory()->create();

        $this->actingAs($registration->user)
            ->post(route('payment.simulate', 'failed'))
            ->assertRedirect(route('payment.failed'));

        expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Failed);
    });

    it('is offered on the payment page', function () {
        $registration = Registration::factory()->create();

        $this->actingAs($registration->user)
            ->get(route('payment.show'))
            ->assertSee(['Simulasi pembayaran', 'payment gateway belum dikonfigurasi']);
    });
});

it('switches the simulation off once Midtrans is configured or outside the local environment', function (string $environment) {
    $this->app['env'] = $environment;
    $this->withoutMiddleware(PreventRequestForgery::class);
    $registration = Registration::factory()->create();

    $this->actingAs($registration->user)
        ->post(route('payment.simulate', 'paid'))
        ->assertNotFound();

    expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
})->with(['local', 'production']);
