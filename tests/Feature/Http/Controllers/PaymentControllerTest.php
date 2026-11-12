<?php

use App\Enums\FeePeriod;
use App\Enums\PaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\RegistrationMember;
use App\Models\User;
use App\Support\QrisCheckout;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * An External 5K Early Bid registration (the package's 7th registration) with its Midtrans QRIS created.
 *
 * @param  array<string, mixed>  $attributes
 */
function registrationWithQris(array $attributes = []): Registration
{
    $registration = Registration::factory()->create(['code' => 'UCCR-052-0007', 'type' => 'external', 'category' => '5k', ...$attributes]);
    $registration->startQrisPayment();

    return $registration->fresh();
}

/**
 * Let a verified Midtrans notification end the registration's current QRIS with this status.
 */
function endCurrentQris(TestCase $test, Registration $registration, string $midtransStatus): void
{
    midtransReports($midtransStatus);
    $test->postJson(route('payments.midtrans.notification'), midtransNotification($registration->latestPayment()->first(), $midtransStatus, '202'))->assertOk();
    midtransReports('pending');
}

/**
 * The order ID and status of every payment attempt, oldest first.
 *
 * @return list<array{order_id: string, status: TransactionStatus}>
 */
function paymentAttempts(Registration $registration): array
{
    return $registration->payments()->orderBy('id')->get()->map->only(['order_id', 'status'])->all();
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
        ->assertSee(['Budi Santoso', '5K External', 'Rp100.000', 'UCCR-052-0007', 'Menunggu pembayaran', 'UCCR-052-0007-1', $payment->expires_at->translatedFormat('j F Y, H.i')])
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

it('confirms the registration and issues the first BIB of its package once Midtrans reports the payment as settled', function () {
    $registration = registrationWithQris();
    midtransReports('settlement', ['settlement_time' => '2026-11-09 10:15:00']);

    $this->actingAs($registration->user)
        ->post(route('payment.check'))
        ->assertRedirect(route('payment.success'));

    $registration->refresh();
    expect($registration)
        ->payment_status->toBe(PaymentStatus::Paid)
        ->bib_number->toBe('0520001')
        ->transaction_id->toBe('UCCR-052-0007-1')
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
    $registration = Registration::factory()->bundle('couple')->paid()->create(['bib_number' => '0550001']);
    RegistrationMember::factory()->for($registration)->create(['full_name' => 'Gita Permata', 'bib_number' => '0550002']);

    $this->actingAs($registration->user)
        ->get(route('payment.success'))
        ->assertOk()
        ->assertSee(['Pembayaran Berhasil!', 'LUNAS', 'Registrasi Terkonfirmasi', $registration->code, '0550001', 'Gita Permata', '0550002'])
        ->assertSeeInOrder(['QR Racepack', 'data-racepack-qr="UCCR-0550001;UCCR-0550002"', '<svg', 'BIB 0550001', 'BIB 0550002'], false);
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

it('keeps a paid registration at the amount it paid when the price changes', function () {
    $registration = Registration::factory()->paid()->create(['type' => 'external', 'category' => '5k']);
    setPackage('external', 'early_bird', '5k', ['price' => 150000]);

    expect($registration->fresh()->amount)->toBe(100000);
});

describe('paying again', function () {
    it('starts a new QRIS with a new order ID after the payment expired, was cancelled or failed, keeping everything else', function (string $midtransStatus, TransactionStatus $ended) {
        $registration = registrationWithQris();
        endCurrentQris($this, $registration, $midtransStatus);

        $this->actingAs($registration->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.show'));

        $registration->refresh();
        expect(Registration::count())->toBe(1)
            ->and($registration)
            ->code->toBe('UCCR-052-0007')
            ->fee_period->toBe(FeePeriod::EarlyBird)
            ->ticket_price_id->toBe($registration->ticketPrice->id)
            ->payment_status->toBe(PaymentStatus::Unpaid)
            ->transaction_id->toBe('UCCR-052-0007-2')
            ->bib_number->toBeNull()
            // The earlier attempt stays as payment history; Midtrans never sees an order ID twice.
            ->and(paymentAttempts($registration))->toBe([
                ['order_id' => 'UCCR-052-0007-1', 'status' => $ended],
                ['order_id' => 'UCCR-052-0007-2', 'status' => TransactionStatus::Pending],
            ])
            ->and($registration->latestPayment->isActive())->toBeTrue()
            ->and(qrisChargesSent())->toBe(2);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v2/charge') && $request['transaction_details']['order_id'] === 'UCCR-052-0007-2');

        $this->get(route('payment.show'))->assertOk()->assertSee(['Order ID:', 'UCCR-052-0007-2'])->assertSee('<svg', false);
    })->with([
        'expired' => ['expire', TransactionStatus::Expired],
        'cancelled' => ['cancel', TransactionStatus::Cancelled],
        'failed' => ['deny', TransactionStatus::Failed],
    ]);

    it('checks an expired QRIS with Midtrans first, also when no notification arrived', function () {
        $registration = registrationWithQris();
        $this->travel(61)->minutes();
        midtransReports('expire');

        $this->actingAs($registration->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.show'));

        expect(paymentAttempts($registration))->toBe([
            ['order_id' => 'UCCR-052-0007-1', 'status' => TransactionStatus::Expired],
            ['order_id' => 'UCCR-052-0007-2', 'status' => TransactionStatus::Pending],
        ]);
    });

    it('confirms the payment instead of starting a new one when Midtrans settled the expired QRIS at the last moment', function () {
        $registration = registrationWithQris();
        $this->travel(61)->minutes();
        midtransReports('settlement');

        $this->actingAs($registration->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.success'));

        expect($registration->fresh()->isPaid())->toBeTrue()
            ->and($registration->payments()->count())->toBe(1)
            ->and(qrisChargesSent())->toBe(1);
    });

    it('charges the package price now in the database', function () {
        setPackage('external', 'early_bird', '3k', ['price' => 120000]);
        $registration = Registration::factory()->failed()->create(['code' => 'UCCR-032-0003', 'type' => 'external', 'category' => '3k']);
        $registration->payments()->create(['order_id' => 'UCCR-032-0003-1', 'amount' => 100000, 'status' => TransactionStatus::Expired]);

        $this->actingAs($registration->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.show'));

        $registration->refresh();
        expect($registration)
            ->payment_status->toBe(PaymentStatus::Unpaid)
            ->amount->toBe(120000)
            ->and($registration->latestPayment)
            ->order_id->toBe('UCCR-032-0003-2')
            ->amount->toBe(120000)
            ->status->toBe(TransactionStatus::Pending);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v2/charge') && $request['transaction_details']['gross_amount'] === 120000);
    });

    it('lets a participant pay again while their package has a free slot', function () {
        setPackage('external', 'early_bird', '5k', ['quota' => 2]);
        Registration::factory()->paid()->create(['type' => 'external', 'category' => '5k']);
        $registration = Registration::factory()->failed()->create(['type' => 'external', 'category' => '5k']);

        $this->actingAs($registration->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.show'));

        expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Unpaid)
            ->and($registration->fresh()->package()->remaining())->toBe(0);
    });

    it('refuses once someone else paid for the last slot, and never moves the registration to another package', function () {
        setPackage('external', 'early_bird', '5k', ['quota' => 1]);
        setPackage('external', 'normal', '5k', ['price' => 175000]);
        $registration = registrationWithQris();
        endCurrentQris($this, $registration, 'expire');
        Registration::factory()->paid()->create(['type' => 'external', 'category' => '5k']);

        $this->actingAs($registration->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.failed'))
            ->assertSessionHas('error', 'Kuota paket 5K Early Bid (External) sudah habis, jadi pembayaran ulang tidak dapat dilakukan. Pembayaran ulang hanya berlaku untuk paket yang sama; hubungi panitia jika ingin mengikuti paket lain.');

        expect($registration->fresh())
            ->fee_period->toBe(FeePeriod::EarlyBird)
            ->ticket_price_id->toBe($registration->ticket_price_id)
            ->amount->toBe(100000)
            ->payment_status->toBe(PaymentStatus::Failed)
            ->and($registration->payments()->count())->toBe(1)
            ->and(qrisChargesSent())->toBe(1);
    });

    it('explains on the failed page why paying again is not possible, without a button that works', function () {
        setPackage('internal', 'early_bird', '10k', ['quota' => 1]);
        $registration = Registration::factory()->failed()->create(['type' => 'internal', 'category' => '10k']);
        Registration::factory()->paid()->create(['type' => 'internal', 'category' => '10k']);

        $this->actingAs($registration->user)
            ->get(route('payment.failed'))
            ->assertOk()
            ->assertSee(['Pembayaran ulang tidak tersedia', 'Kuota paket 10K Early Bid (Internal) sudah habis'])
            ->assertDontSee('action="'.route('payment.retry').'"', false);
    });

    it('always lets a Normal External registration pay again, because its quota is unlimited', function () {
        Registration::factory()->paid()->normalPrice()->count(3)->create(['type' => 'external', 'category' => '3k']);
        $registration = Registration::factory()->failed()->normalPrice()->create(['type' => 'external', 'category' => '3k']);

        expect($registration->package()->quota)->toBeNull();

        $this->actingAs($registration->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.show'));

        expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Unpaid);
    });

    it('counts a bundle as one package of its quota, not one slot per person', function (string $bundle, int $people) {
        setPackage('bundling', null, $bundle === 'family' ? '3k' : '5k', ['quota' => 2]);
        $paid = Registration::factory()->bundle($bundle)->paid()->create();
        $registration = Registration::factory()->bundle($bundle)->failed()->create();

        foreach ([$paid, $registration] as $owner) {
            foreach (range(2, $people) as $position) {
                RegistrationMember::factory()->for($owner)->create(['position' => $position]);
            }
        }

        $this->actingAs($registration->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.show'));

        expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Unpaid)
            ->and($registration->fresh()->package()->remaining())->toBe(0);

        // With both packages taken, a third bundle cannot pay again.
        $third = Registration::factory()->bundle($bundle)->failed()->create();

        $this->actingAs($third->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.failed'))
            ->assertSessionHas('error');

        expect($third->fresh()->payment_status)->toBe(PaymentStatus::Failed);
    })->with([
        'Couple Bundle' => ['couple', 2],
        'Family Bundle' => ['family', 4],
    ]);

    it('starts only one new QRIS when Pay Again is sent twice', function () {
        $registration = Registration::factory()->failed()->create(['type' => 'external', 'category' => '5k']);

        $this->actingAs($registration->user)->post(route('payment.retry'))->assertRedirect(route('payment.show'));
        $this->actingAs($registration->user)->post(route('payment.retry'))->assertRedirect(route('payment.show'));

        expect($registration->payments()->count())->toBe(1)
            ->and(qrisChargesSent())->toBe(1);
    });

    it('waits for a QRIS that is still being created instead of starting another one', function () {
        $registration = Registration::factory()->create(['type' => 'external', 'category' => '5k']);
        $registration->newPaymentAttempt();

        $this->actingAs($registration->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.show'));

        expect($registration->payments()->count())->toBe(1)
            ->and(qrisChargesSent())->toBe(0);
    });

    it('replaces an attempt whose QRIS was never saved, once Midtrans confirms it does not know it', function () {
        $registration = Registration::factory()->create(['code' => 'UCCR-052-0004', 'type' => 'external', 'category' => '5k']);
        $registration->newPaymentAttempt();
        $this->travel(3)->minutes();
        midtransDoesNotKnowOrders();

        $this->actingAs($registration->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.show'));

        expect(paymentAttempts($registration))->toBe([
            ['order_id' => 'UCCR-052-0004-1', 'status' => TransactionStatus::Failed],
            ['order_id' => 'UCCR-052-0004-2', 'status' => TransactionStatus::Pending],
        ])
            ->and($registration->latestPayment()->first()->isActive())->toBeTrue();
    });

    it('keeps a payment that was confirmed while the new QRIS was being saved', function () {
        $registration = Registration::factory()->failed()->create(['type' => 'external', 'category' => '5k']);

        // An older attempt's settlement is processed right between saving the new QRIS and the registration.
        Payment::updated(function (Payment $payment): void {
            if ($payment->wasChanged('qr_string')) {
                $payment->registration->markAsPaid();
            }
        });

        $this->actingAs($registration->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.success'));

        expect($registration->fresh())
            ->payment_status->toBe(PaymentStatus::Paid)
            ->bib_number->toBe('0520001');
    });

    it('never starts a payment for a registration that is already paid', function () {
        $registration = Registration::factory()->paid()->create(['type' => 'external', 'category' => '5k']);

        $this->actingAs($registration->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.success'));

        expect($registration->payments()->count())->toBe(0)
            ->and(qrisChargesSent())->toBe(0)
            ->and($registration->fresh()->isPaid())->toBeTrue();
    });

    it('explains when Midtrans refuses to create the new QRIS and gives the slot back', function () {
        setPackage('external', 'early_bird', '3k', ['quota' => 1]);
        midtransRefusesCharges();
        $registration = Registration::factory()->failed()->create(['type' => 'external', 'category' => '3k']);

        $this->actingAs($registration->user)
            ->post(route('payment.retry'))
            ->assertRedirect(route('payment.failed'))
            ->assertSessionHas('error', QrisCheckout::UNAVAILABLE_MESSAGE);

        expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Failed)
            ->and($registration->latestPayment()->first()->status)->toBe(TransactionStatus::Failed)
            ->and($registration->fresh()->package()->remaining())->toBe(1);
    });
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
            ->bib_number->toBe('0520001');
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
