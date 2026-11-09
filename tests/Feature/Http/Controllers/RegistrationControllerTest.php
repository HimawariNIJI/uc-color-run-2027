<?php

use App\Enums\FeePeriod;
use App\Enums\PaymentStatus;
use App\Enums\RaceCategory;
use App\Enums\RegistrationType;
use App\Enums\TransactionStatus;
use App\Models\Registration;
use App\Models\TicketPrice;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;

it('lets participants choose a registration type, price type and distance', function () {
    $this->actingAs(User::factory()->ucEmail()->create());

    $this->get(route('registration.type'))
        ->assertOk()
        ->assertSee(['Internal', 'External', 'Bundling', route('registration.price', 'external')]);

    $this->get(route('registration.price', 'external'))
        ->assertOk()
        ->assertSeeInOrder(['Pilih Tipe Harga', 'Early Bid', 'Rp100.000', 'Kuota terbatas', 'Normal Bid', 'Rp100.000'])
        ->assertSee([route('registration.category', ['external', 'early-bird']), route('registration.category', ['external', 'normal'])]);

    $this->get(route('registration.category', ['external', 'early-bird']))
        ->assertOk()
        ->assertSeeInOrder(['3 KM', '3K Early Bid External · Rp100.000', 'Kuota 50 slot', '5 KM', 'Kuota 100 slot', '10 KM', 'Kuota 100 slot'])
        ->assertSee(route('registration.create', ['external', 'early-bird', '5k']));
});

it('keeps the External Normal Bid packages closed until their quota is announced', function () {
    $this->actingAs(User::factory()->create(['email' => 'rina@gmail.com']));

    $this->get(route('registration.category', ['external', 'normal']))
        ->assertOk()
        ->assertSeeInOrder(['3 KM', 'Kuota belum diumumkan', '5 KM', 'Kuota belum diumumkan', '10 KM', 'Kuota belum diumumkan'])
        ->assertDontSee(route('registration.create', ['external', 'normal', '5k']));

    $this->post(route('registration.store', ['external', 'normal', '5k']), registrationPayload())
        ->assertRedirect(route('registration.category', ['external', 'normal']))
        ->assertSessionHas('error', 'Kuota paket 5K Normal Bid (External) belum diumumkan, jadi pendaftarannya belum dibuka.');

    expect(Registration::count())->toBe(0);
});

it('shows Price To Be Announced and refuses a package whose price is not in the database', function () {
    setPackage('external', 'early_bird', '5k', ['price' => null]);
    $this->actingAs(User::factory()->create(['email' => 'rina@gmail.com']));

    $this->get(route('registration.category', ['external', 'early-bird']))
        ->assertSeeInOrder(['5 KM', '5K Early Bid External · Price To Be Announced'])
        ->assertDontSee(route('registration.create', ['external', 'early-bird', '5k']));

    $this->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload())
        ->assertRedirect(route('registration.category', ['external', 'early-bird']))
        ->assertSessionHas('error', 'Harga paket 5K Early Bid (External) belum diumumkan, jadi pendaftarannya belum dibuka.');

    expect(Registration::count())->toBe(0);
    Http::assertNothingSent();
});

it('shows the slots left in each package', function () {
    setPackage('internal', 'early_bird', '3k', ['quota' => 2]);
    setPackage('internal', 'early_bird', '10k', ['quota' => 1]);
    Registration::factory()->create(['type' => 'internal', 'category' => '3k']);
    Registration::factory()->paid()->create(['type' => 'internal', 'category' => '10k']);
    Registration::factory()->paid()->create(['type' => 'external', 'category' => '5k']);

    $this->actingAs(User::factory()->ucEmail()->create())
        ->get(route('registration.category', ['internal', 'early-bird']))
        ->assertOk()
        ->assertSeeInOrder(['3 KM', 'Sisa 1 dari 2 slot', '5 KM', 'Kuota 100 slot', '10 KM', 'Sold out'])
        ->assertSee(route('registration.create', ['internal', 'early-bird', '3k']))
        ->assertDontSee(route('registration.create', ['internal', 'early-bird', '10k']));
});

it('gives back the slot of a failed or expired payment', function () {
    setPackage('internal', 'early_bird', '5k', ['quota' => 1]);
    Registration::factory()->failed()->create(['type' => 'internal', 'category' => '5k']);
    Registration::factory()->expired()->create(['type' => 'internal', 'category' => '5k']);
    Registration::factory()->normalPrice()->create(['type' => 'internal', 'category' => '5k']);

    $this->actingAs(User::factory()->ucEmail()->create())
        ->get(route('registration.category', ['internal', 'early-bird']))
        ->assertSeeInOrder(['5 KM', 'Kuota 1 slot']);
});

it('marks a price type as sold out once every distance is full', function () {
    foreach (['3k', '5k', '10k'] as $category) {
        setPackage('internal', 'early_bird', $category, ['quota' => 0]);
    }
    $this->actingAs(User::factory()->ucEmail()->create());

    $this->get(route('registration.price', 'internal'))
        ->assertOk()
        ->assertSee('Sold out')
        ->assertDontSee(route('registration.category', ['internal', 'early-bird']))
        ->assertSee(route('registration.category', ['internal', 'normal']));

    $this->get(route('registration.category', ['internal', 'early-bird']))
        ->assertRedirect(route('registration.price', 'internal'))
        ->assertSessionHas('error', 'Kuota Early Bid Internal sudah habis untuk semua jarak lari. Silakan pilih tipe harga lain.');
});

it('returns 404 for an unknown registration type, price type, category or bundle', function (string $url) {
    $this->actingAs(User::factory()->create())->get($url)->assertNotFound();
})->with([
    '/registration/vip',
    '/registration/external/regular',
    '/registration/external/early_bird',
    '/registration/external/early-bird/21k',
    '/registration/bundling/early-bird',
    '/registration/bundling/normal/3k',
    '/registration/bundling/trio',
]);

it('asks guests to sign in before starting a registration', function () {
    $this->get(route('registration.type'))->assertRedirect(route('login'));
    $this->get(route('registration.price', 'external'))->assertRedirect(route('login'));
    $this->get(route('registration.category', ['external', 'normal']))->assertRedirect(route('login'));
    $this->get(route('registration.create', ['external', 'early-bird', '5k']))->assertRedirect(route('login'));
    $this->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload())->assertRedirect(route('login'));
    $this->get(route('registration.bundle.create', 'family'))->assertRedirect(route('login'));
    $this->post(route('registration.bundle.store', 'family'), registrationPayload(['members' => [bundleMember()]]))->assertRedirect(route('login'));

    expect(Registration::count())->toBe(0);
});

it('offers Internal only to participants with a Universitas Ciputra email', function (string $email, bool $seesInternal) {
    $response = $this->actingAs(User::factory()->create(['email' => $email]))
        ->get(route('registration.type'))
        ->assertOk()
        ->assertSee(route('registration.price', 'external'));

    if ($seesInternal) {
        $response->assertSee(route('registration.price', 'internal'))
            ->assertDontSee('Kategori Internal khusus akun Google');
    } else {
        $response->assertDontSee(route('registration.price', 'internal'))
            ->assertDontSee('Untuk mahasiswa, dosen, staf, dan alumni Universitas Ciputra.')
            ->assertSee(['Kategori Internal khusus akun Google dengan email Universitas Ciputra (ciputra.ac.id).', $email]);
    }
})->with([
    'student subdomain' => ['rina@student.ciputra.ac.id', true],
    'main domain' => ['rina@ciputra.ac.id', true],
    'gmail' => ['rina@gmail.com', false],
]);

it('lets a Universitas Ciputra participant register for an Internal or External package', function (string $type) {
    $user = User::factory()->create(['email' => 'rina@magister.ciputra.ac.id']);

    $this->actingAs($user)
        ->post(route('registration.store', [$type, 'early-bird', '5k']), registrationPayload())
        ->assertRedirect(route('payment.show'));

    expect($user->registration()->sole()->type)->toBe(RegistrationType::from($type));
})->with(['internal', 'external']);

it('rejects an Internal registration sent by a participant without a Universitas Ciputra email', function () {
    $user = User::factory()->create(['email' => 'rina@gmail.com']);

    $this->actingAs($user)
        ->post(route('registration.store', ['internal', 'normal', '5k']), registrationPayload(['type' => 'external', 'email' => 'rina@student.ciputra.ac.id']))
        ->assertForbidden()
        ->assertSee('Kategori Internal hanya untuk akun Google dengan email Universitas Ciputra (ciputra.ac.id).');

    expect(Registration::count())->toBe(0)
        ->and($user->fresh()->email)->toBe('rina@gmail.com');
});

it('blocks every Internal step for participants without a Universitas Ciputra email', function (string $url) {
    $this->actingAs(User::factory()->create(['email' => 'rina@company.com']))
        ->get($url)
        ->assertForbidden();
})->with([
    'price type page' => ['/registration/internal'],
    'category page' => ['/registration/internal/normal'],
    'form' => ['/registration/internal/normal/5k'],
]);

it('saves the registration with the database price and shows its Midtrans QRIS straight away', function () {
    $this->freezeSecond();
    $user = User::factory()->create(['email' => 'budi@example.com']);

    $this->actingAs($user)
        ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload(['phone' => '0812-3456-7890']))
        ->assertRedirect(route('payment.show'))
        ->assertSessionHas('success', 'Registrasi berhasil dikirim. Scan QRIS di bawah untuk menyelesaikan pembayaran.');

    $registration = $user->registration()->sole();
    $payment = $registration->payments()->sole();
    expect($registration)
        ->type->toBe(RegistrationType::External)
        ->category->toBe(RaceCategory::FiveK)
        ->fee_period->toBe(FeePeriod::EarlyBird)
        ->ticket_price_id->toBe(TicketPrice::findOption(RegistrationType::External, FeePeriod::EarlyBird, RaceCategory::FiveK)->id)
        ->code->toBe('UCCR-0501020001')
        ->email->toBe('budi@example.com')
        ->phone->toBe('081234567890')
        ->amount->toBe(100000)
        ->payment_status->toBe(PaymentStatus::Unpaid)
        ->bib_number->toBeNull()
        ->identity_number->toBe('3578011205990001')
        ->and($payment)
        ->order_id->toBe('UCCR-0501020001-1')
        ->amount->toBe(100000)
        ->status->toBe(TransactionStatus::Pending)
        ->qr_string->toStartWith('000201')
        ->and($registration->payment_expires_at->equalTo($payment->expires_at))->toBeTrue();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v2/charge')
        && $request['payment_type'] === 'qris'
        && ! isset($request['enabled_payments'])
        && $request['transaction_details'] === ['order_id' => 'UCCR-0501020001-1', 'gross_amount' => 100000]
        && $request['customer_details']['email'] === 'budi@example.com'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('SB-Mid-server-TEST:')));

    $this->actingAs($user->fresh())
        ->get(route('payment.show'))
        ->assertOk()
        ->assertSee(['UCCR-0501020001', 'Rp100.000', 'Menunggu pembayaran', 'UCCR-0501020001-1'])
        ->assertSee('<svg', false);
});

it('charges the price stored in the database, whatever the browser sends', function () {
    setPackage('internal', 'normal', '10k', ['price' => 125000]);
    $user = User::factory()->ucEmail()->create();

    $this->actingAs($user)->post(route('registration.store', ['internal', 'normal', '10k']), registrationPayload([
        'amount' => 1000, 'price' => 1000, 'total_amount' => 1000, 'ticket_price_id' => 1, 'type' => 'external', 'email' => 'other@example.com',
    ]));

    expect($user->registration()->sole())
        ->amount->toBe(125000)
        ->fee_period->toBe(FeePeriod::Regular)
        ->type->toBe(RegistrationType::Internal)
        ->email->toBe($user->email);
    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v2/charge') && $request['transaction_details']['gross_amount'] === 125000);
});

it('numbers the registrations of each package in order', function () {
    foreach (range(1, 2) as $attempt) {
        $this->actingAs(User::factory()->create())
            ->post(route('registration.store', ['external', 'early-bird', '3k']), registrationPayload());
    }
    $this->actingAs(User::factory()->ucEmail()->create())
        ->post(route('registration.store', ['internal', 'early-bird', '3k']), registrationPayload());

    expect(Registration::query()->orderBy('id')->pluck('code')->all())->toBe(['UCCR-0301020001', 'UCCR-0301020002', 'UCCR-0301010001']);
});

it('prefills the form with the participant name, email and package price', function () {
    $user = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.com']);

    $this->actingAs($user)
        ->get(route('registration.create', ['external', 'early-bird', '5k']))
        ->assertOk()
        ->assertSee('value="Budi Santoso"', false)
        ->assertSee('value="budi@example.com"', false)
        ->assertSee('value="5K External"', false)
        ->assertSee('Biaya registrasi Rp100.000 (5K Early Bid)')
        ->assertSee(route('registration.store', ['external', 'early-bird', '5k']))
        ->assertDontSee('Bundle Members');
});

it('sends participants back to the distances when a package is sold out', function () {
    setPackage('external', 'early_bird', '5k', ['quota' => 1]);
    Registration::factory()->create(['type' => 'external', 'category' => '5k']);

    $this->actingAs(User::factory()->create())
        ->get(route('registration.create', ['external', 'early-bird', '5k']))
        ->assertRedirect(route('registration.category', ['external', 'early-bird']))
        ->assertSessionHas('error', 'Kuota paket 5K Early Bid (External) sudah habis. Silakan pilih paket lain.');
});

it('never registers more participants than a confirmed quota', function () {
    setPackage('external', 'early_bird', '10k', ['quota' => 1]);

    $this->actingAs(User::factory()->create())->post(route('registration.store', ['external', 'early-bird', '10k']), registrationPayload());
    $this->actingAs(User::factory()->create())->post(route('registration.store', ['external', 'early-bird', '10k']), registrationPayload())
        ->assertRedirect(route('registration.category', ['external', 'early-bird']))
        ->assertSessionHas('error', 'Kuota paket 10K Early Bid (External) sudah habis. Silakan pilih paket lain.');

    expect(Registration::count())->toBe(1);
});

it('keeps the form data on the Normal Bid form when the last Early Bid slot is taken during sign-up', function () {
    openAllPackages();
    setPackage('external', 'early_bird', '5k', ['quota' => 1]);
    Registration::factory()->create(['type' => 'external', 'category' => '5k']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload(['full_name' => 'Rina Wijaya']))
        ->assertRedirect(route('registration.create', ['external', 'normal', '5k']))
        ->assertSessionHas('error', 'Maaf, kuota 5K Early Bid External baru saja habis. Datamu masih tersimpan di formulir ini dengan Normal Bid. Periksa kembali lalu kirim ulang.')
        ->assertSessionHasInput('full_name', 'Rina Wijaya');

    expect($user->registration()->exists())->toBeFalse();
});

it('gives the slot back and explains when Midtrans cannot create the QRIS', function () {
    Exceptions::fake();
    midtransRefusesCharges();
    setPackage('external', 'early_bird', '3k', ['quota' => 1]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('registration.store', ['external', 'early-bird', '3k']), registrationPayload())
        ->assertRedirect(route('payment.failed'))
        ->assertSessionHas('error', 'Maaf, kode QRIS belum bisa dibuat saat ini. Silakan coba lagi beberapa saat lagi atau hubungi panitia.')
        ->assertSessionMissing('success');

    expect($user->registration()->sole()->payment_status)->toBe(PaymentStatus::Failed)
        ->and($user->registration()->sole()->payments()->sole()->status)->toBe(TransactionStatus::Failed)
        ->and(TicketPrice::findOption(RegistrationType::External, FeePeriod::EarlyBird, RaceCategory::ThreeK)->remaining())->toBe(1);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => str_contains($exception->getMessage(), 'Payment channel is not activated.'));
});

it('stores the identity number encrypted', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('registration.store', ['external', 'early-bird', '3k']), registrationPayload());

    expect($user->registration()->exists())->toBeTrue();
    $this->assertDatabaseMissing('registrations', ['identity_number' => '3578011205990001']);
});

it('requires every participant field', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('registration.store', ['external', 'early-bird', '5k']), [])
        ->assertSessionHasErrors([
            'full_name' => 'Full Name wajib diisi.',
            'phone' => 'Phone Number wajib diisi.',
            'birth_date' => 'Date of Birth wajib diisi.',
            'gender' => 'Pilih gender.',
            'identity_number' => 'KTP/Passport wajib diisi.',
            'tshirt_size' => 'Pilih ukuran T-shirt.',
            'emergency_contact_name' => 'Emergency Contact Name wajib diisi.',
            'emergency_contact_phone' => 'Emergency Contact Phone wajib diisi.',
            'province' => 'Pilih provinsi.',
            'city' => 'Pilih kota atau kabupaten.',
            'address' => 'Full Address wajib diisi.',
            'agreement' => 'Kamu harus menyetujui Race Regulations dan Privacy Policy.',
        ]);

    expect(Registration::count())->toBe(0);
});

it('rejects invalid participant details', function (array $overrides, string $field, string $message) {
    $this->actingAs(User::factory()->create())
        ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload($overrides))
        ->assertSessionHasErrors([$field => $message]);

    expect(Registration::count())->toBe(0);
})->with([
    'phone not Indonesian' => [['phone' => '12345'], 'phone', 'Format Phone Number tidak valid. Gunakan format 08xxxxxxxxxx.'],
    'emergency phone equals phone' => [['emergency_contact_phone' => '081234567890'], 'emergency_contact_phone', 'Nomor kontak darurat harus berbeda dengan nomor telepon peserta.'],
    'birth date in the future' => [['birth_date' => '2099-01-01'], 'birth_date', 'Date of Birth harus sebelum hari ini.'],
    'identity number with symbols' => [['identity_number' => '35-78/01'], 'identity_number', 'KTP/Passport harus terdiri dari 6–20 huruf atau angka.'],
    'unknown gender' => [['gender' => 'other'], 'gender', 'Gender yang dipilih tidak valid.'],
    'unknown t-shirt size' => [['tshirt_size' => 'XXXXL'], 'tshirt_size', 'T-shirt Size yang dipilih tidak valid.'],
    'unknown province' => [['province' => 'Atlantis'], 'province', 'Province yang dipilih tidak valid.'],
    'city outside the province' => [['province' => 'Bali', 'city' => 'Kota Surabaya'], 'city', 'City yang dipilih tidak valid.'],
]);

it('sends participants who already registered to My Registration', function (string $method, string $url) {
    $registration = Registration::factory()->create();

    $this->actingAs($registration->user)
        ->call($method, $url, registrationPayload(['members' => [bundleMember()]]))
        ->assertRedirect(route('my-registration.show'))
        ->assertSessionHas('info');

    expect(Registration::count())->toBe(1);
    Http::assertNothingSent();
})->with([
    'type page' => ['GET', '/registration'],
    'price type page' => ['GET', '/registration/external'],
    'category page' => ['GET', '/registration/external/early-bird'],
    'form' => ['GET', '/registration/external/early-bird/5k'],
    'submit' => ['POST', '/registration/external/early-bird/5k'],
    'bundle form' => ['GET', '/registration/bundling/family'],
    'bundle submit' => ['POST', '/registration/bundling/family'],
]);

describe('bundles', function () {
    it('lists the Family and Couple Bundle, counting one registration as one package', function () {
        Registration::factory()->bundle('family')->create();

        $this->actingAs(User::factory()->create(['email' => 'rina@gmail.com']))
            ->get(route('registration.price', 'bundling'))
            ->assertOk()
            ->assertSeeInOrder([
                'Pilih Paket Bundle',
                '3K Family Bundle', '3 KM · Maks. 4 peserta', 'Rp100.000 per paket', 'Sisa 19 dari 20 paket',
                '5K Couple Bundle', '5 KM · Maks. 2 peserta', 'Rp100.000 per paket', 'Kuota 50 paket',
            ])
            ->assertSee([route('registration.bundle.create', 'family'), route('registration.bundle.create', 'couple')]);
    });

    it('asks for the other members of the bundle', function (string $bundle, int $memberSlots) {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('registration.bundle.create', $bundle))
            ->assertOk()
            ->assertSee(['Bundle Members', 'Biaya registrasi Rp100.000'])
            ->assertSee('name="members['.($memberSlots - 1).'][full_name]"', false)
            ->assertDontSee('name="members['.$memberSlots.'][full_name]"', false);

        expect(substr_count($response->getContent(), '][identity_number]"'))->toBe($memberSlots);
    })->with([
        'family: 3 more people' => ['family', 3],
        'couple: 1 more person' => ['couple', 1],
    ]);

    it('registers a Family Bundle as one package for the whole family at one price', function () {
        $user = User::factory()->create(['email' => 'hendra@gmail.com']);

        $this->actingAs($user)
            ->post(route('registration.bundle.store', 'family'), registrationPayload([
                'members' => [
                    bundleMember(['full_name' => 'Indah Wijaya']),
                    bundleMember(['full_name' => 'Joko Wijaya', 'gender' => 'male', 'phone' => '0812-0000-1111', 'identity_number' => 'A1234567']),
                    bundleMember(['full_name' => 'Kartika Wijaya', 'identity_number' => '3578011402000003']),
                ],
            ]))
            ->assertRedirect(route('payment.show'));

        $registration = $user->registration()->sole();
        $family = TicketPrice::findBundle('family');
        expect($registration)
            ->type->toBe(RegistrationType::Bundling)
            ->category->toBe(RaceCategory::ThreeK)
            ->fee_period->toBeNull()
            ->ticket_price_id->toBe($family->id)
            ->code->toBe('UCCR-0300040001')
            ->amount->toBe(100000)
            ->and($registration->members->map->only(['position', 'full_name', 'phone'])->all())->toBe([
                ['position' => 2, 'full_name' => 'Indah Wijaya', 'phone' => null],
                ['position' => 3, 'full_name' => 'Joko Wijaya', 'phone' => '081200001111'],
                ['position' => 4, 'full_name' => 'Kartika Wijaya', 'phone' => null],
            ])
            ->and($registration->members->get(1)->identity_number)->toBe('A1234567')
            ->and($family->remaining())->toBe(19);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v2/charge') && $request['transaction_details']['gross_amount'] === 100000);
    });

    it('ignores empty optional member rows', function () {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('registration.bundle.store', 'family'), registrationPayload([
            'members' => [bundleMember(), ['full_name' => '', 'birth_date' => '', 'identity_number' => ''], []],
        ]))->assertRedirect(route('payment.show'));

        expect($user->registration()->sole()->members)->toHaveCount(1);
    });

    it('rejects more people than the bundle allows, or a bundle without other members', function (string $bundle, array $members, string $message) {
        $this->actingAs(User::factory()->create())
            ->post(route('registration.bundle.store', $bundle), registrationPayload(['members' => $members]))
            ->assertSessionHasErrors(['members' => $message]);

        expect(Registration::count())->toBe(0);
    })->with([
        'family of 5' => ['family', array_fill(0, 4, bundleMember()), 'Paket ini hanya untuk maksimal 3 anggota lain selain kamu.'],
        'couple of 3' => ['couple', array_fill(0, 2, bundleMember()), 'Paket ini hanya untuk maksimal 1 anggota lain selain kamu.'],
        'couple alone' => ['couple', [], 'Isi data minimal 1 anggota bundle lainnya.'],
    ]);

    it('validates every member', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('registration.bundle.store', 'couple'), registrationPayload([
                'members' => [bundleMember(['full_name' => '', 'identity_number' => '12-3', 'phone' => '999', 'gender' => 'other', 'birth_date' => '2099-01-01'])],
            ]))
            ->assertSessionHasErrors([
                'members.0.full_name' => 'Nama anggota wajib diisi.',
                'members.0.identity_number' => 'KTP/Passport anggota harus terdiri dari 6–20 huruf atau angka.',
                'members.0.phone' => 'Format Nomor telepon anggota tidak valid. Gunakan format 08xxxxxxxxxx.',
                'members.0.gender' => 'Gender anggota yang dipilih tidak valid.',
                'members.0.birth_date' => 'Tanggal lahir anggota harus sebelum hari ini.',
            ]);

        expect(Registration::count())->toBe(0);
    });

    it('keeps a full bundle closed', function () {
        setPackage('bundling', null, '5k', ['quota' => 1]);
        Registration::factory()->bundle('couple')->paid()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('registration.bundle.create', 'couple'))
            ->assertRedirect(route('registration.price', 'bundling'))
            ->assertSessionHas('error', 'Kuota paket 5K Couple Bundle (Maks. 2 peserta) sudah habis. Silakan pilih paket lain.');

        $this->post(route('registration.bundle.store', 'couple'), registrationPayload(['members' => [bundleMember()]]))
            ->assertRedirect(route('registration.price', 'bundling'));

        expect(Registration::count())->toBe(1);
    });
});
