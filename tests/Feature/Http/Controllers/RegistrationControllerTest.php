<?php

use App\Enums\FeePeriod;
use App\Enums\PaymentStatus;
use App\Enums\RaceCategory;
use App\Enums\RegistrationType;
use App\Enums\TransactionStatus;
use App\Models\Registration;
use App\Models\TicketPrice;
use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('lets participants choose a registration type, price type and distance', function () {
    $this->actingAs(User::factory()->ucEmail()->create());

    $this->get(route('registration.type'))
        ->assertOk()
        ->assertSee(['Internal', 'External', 'Bundling', route('registration.price', 'external')]);

    $this->get(route('registration.price', 'external'))
        ->assertOk()
        ->assertSeeInOrder(['Pilih Tipe Harga', 'Early Bid', 'Rp100.000', 'Kuota terbatas', 'Normal Bid', 'Rp100.000', 'Kuota tidak terbatas'])
        ->assertSee([route('registration.category', ['external', 'early-bird']), route('registration.category', ['external', 'normal'])]);

    $this->get(route('registration.category', ['external', 'early-bird']))
        ->assertOk()
        ->assertSeeInOrder(['3 KM', '3K Early Bid External · Rp100.000', 'Kuota 50 slot', '5 KM', 'Kuota 100 slot', '10 KM', 'Kuota 100 slot'])
        ->assertSee(route('registration.create', ['external', 'early-bird', '5k']));
});

it('keeps the Normal External packages open with an unlimited quota', function () {
    Registration::factory()->count(3)->paid()->create(['type' => 'external', 'category' => '5k', 'fee_period' => 'normal']);
    $this->actingAs(User::factory()->create(['email' => 'rina@gmail.com']));

    $this->get(route('registration.category', ['external', 'normal']))
        ->assertOk()
        ->assertSeeInOrder(['3 KM', 'Kuota tidak terbatas', '5 KM', 'Kuota tidak terbatas', '10 KM', 'Kuota tidak terbatas'])
        ->assertSee(route('registration.create', ['external', 'normal', '5k']))
        ->assertDontSee(['Sold out', 'Kuota 0', 'Sisa']);

    $this->post(route('registration.store', ['external', 'normal', '5k']), registrationPayload())
        ->assertRedirect(route('payment.show'));

    expect(Registration::count())->toBe(4)
        ->and(TicketPrice::findOption(RegistrationType::External, FeePeriod::Regular, RaceCategory::FiveK))
        ->quota->toBeNull()
        ->remaining()->toBeNull()
        ->isOpen()->toBeTrue();
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
        // The registration code is fixed now; the BIB only comes once the payment is confirmed.
        ->code->toBe('UCCR-052-0001')
        ->email->toBe('budi@example.com')
        ->phone->toBe('081234567890')
        ->amount->toBe(100000)
        ->payment_status->toBe(PaymentStatus::Unpaid)
        ->bib_number->toBeNull()
        ->identity_number->toBeNull()
        ->medical_history->toBe('Tidak Ada')
        ->drug_allergies->toBe('Tidak Ada')
        ->ktp_photo_path->not->toBeNull()
        ->and(Storage::disk('identity_photos')->exists($registration->ktp_photo_path))->toBeTrue()
        ->and($payment)
        ->order_id->toBe('UCCR-052-0001-1')
        ->amount->toBe(100000)
        ->status->toBe(TransactionStatus::Pending)
        ->qr_string->toStartWith('000201')
        ->and($registration->payment_expires_at->equalTo($payment->expires_at))->toBeTrue();

    Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v2/charge')
        && $request['payment_type'] === 'qris'
        && ! isset($request['enabled_payments'])
        && $request['transaction_details'] === ['order_id' => 'UCCR-052-0001-1', 'gross_amount' => 100000]
        && $request['customer_details']['email'] === 'budi@example.com'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('SB-Mid-server-TEST:')));

    $this->actingAs($user->fresh())
        ->get(route('payment.show'))
        ->assertOk()
        ->assertSee(['UCCR-052-0001', 'Rp100.000', 'Menunggu pembayaran', 'UCCR-052-0001-1'])
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

    expect(Registration::query()->orderBy('id')->pluck('code')->all())->toBe(['UCCR-032-0001', 'UCCR-032-0002', 'UCCR-031-0001']);
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

it('never registers more participants than a numeric quota', function () {
    setPackage('internal', 'normal', '10k', ['quota' => 1]);

    $this->actingAs(User::factory()->ucEmail()->create())->post(route('registration.store', ['internal', 'normal', '10k']), registrationPayload())
        ->assertRedirect(route('payment.show'));
    $this->actingAs(User::factory()->ucEmail()->create())->post(route('registration.store', ['internal', 'normal', '10k']), registrationPayload())
        ->assertRedirect(route('registration.category', ['internal', 'normal']))
        ->assertSessionHas('error', 'Kuota paket 10K Normal Bid (Internal) sudah habis. Silakan pilih paket lain.');

    expect(Registration::count())->toBe(1);
});

it('keeps the form data on the Normal Bid form when the last Early Bid slot is taken during sign-up', function () {
    setPackage('external', 'early_bird', '5k', ['quota' => 1]);
    Registration::factory()->create(['type' => 'external', 'category' => '5k']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload(['full_name' => 'Rina Wijaya']))
        ->assertRedirect(route('registration.create', ['external', 'normal', '5k']))
        ->assertSessionHas('error', 'Maaf, kuota 5K Early Bid External baru saja habis. Datamu masih tersimpan di formulir ini dengan Normal Bid. Pilih ulang Foto KTP, periksa kembali, lalu kirim ulang.')
        ->assertSessionHasInput('full_name', 'Rina Wijaya')
        ->assertSessionMissing('_old_input.ktp_photo');

    expect($user->registration()->exists())->toBeFalse()
        ->and(Storage::disk('identity_photos')->allFiles())->toBeEmpty();
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

it('stores Riwayat Penyakit and Alergi Obat encrypted', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('registration.store', ['external', 'early-bird', '3k']), registrationPayload([
        'medical_history' => 'Asma sejak kecil',
        'drug_allergies' => 'Penisilin',
    ]));

    expect($user->registration()->sole())
        ->medical_history->toBe('Asma sejak kecil')
        ->drug_allergies->toBe('Penisilin');
    $this->assertDatabaseMissing('registrations', ['medical_history' => 'Asma sejak kecil']);
    $this->assertDatabaseMissing('registrations', ['drug_allergies' => 'Penisilin']);
});

it('requires every participant field', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('registration.store', ['external', 'early-bird', '5k']), [])
        ->assertSessionHasErrors([
            'full_name' => 'Full Nama (Nama Pembayar) wajib diisi.',
            'phone' => 'Phone Number wajib diisi.',
            'birth_date' => 'Date of Birth wajib diisi.',
            'gender' => 'Pilih gender.',
            'ktp_photo' => 'Foto KTP wajib diunggah.',
            'tshirt_size' => 'Pilih ukuran T-shirt.',
            'emergency_contact_name' => 'Emergency Contact Name wajib diisi.',
            'emergency_contact_phone' => 'Emergency Contact Phone wajib diisi.',
            'medical_history' => 'Riwayat Penyakit wajib diisi. Tulis “Tidak Ada” jika tidak memiliki riwayat penyakit.',
            'drug_allergies' => 'Alergi Obat wajib diisi. Tulis “Tidak Ada” jika tidak memiliki alergi obat.',
            'province' => 'Pilih provinsi.',
            'city' => 'Pilih kota atau kabupaten.',
            'address' => 'Full Address wajib diisi.',
            'agreement' => 'Kamu harus menyetujui Race Regulations dan Privacy Policy.',
        ])
        ->assertSessionDoesntHaveErrors('identity_number');

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
    'medical history only spaces' => [['medical_history' => '   '], 'medical_history', 'Riwayat Penyakit wajib diisi. Tulis “Tidak Ada” jika tidak memiliki riwayat penyakit.'],
    'medical history too long' => [['medical_history' => str_repeat('a', 1001)], 'medical_history', 'Riwayat Penyakit maksimal 1000 karakter.'],
    'drug allergies too long' => [['drug_allergies' => str_repeat('a', 1001)], 'drug_allergies', 'Alergi Obat maksimal 1000 karakter.'],
    'unknown gender' => [['gender' => 'other'], 'gender', 'Gender yang dipilih tidak valid.'],
    'unknown t-shirt size' => [['tshirt_size' => 'XXXXL'], 'tshirt_size', 'T-shirt Size yang dipilih tidak valid.'],
    'unknown province' => [['province' => 'Atlantis'], 'province', 'Province yang dipilih tidak valid.'],
    'city outside the province' => [['province' => 'Bali', 'city' => 'Kota Surabaya'], 'city', 'City yang dipilih tidak valid.'],
]);

it('sends participants who already registered to My Registration', function (string $method, string $url) {
    $registration = Registration::factory()->create();

    $this->actingAs($registration->user)
        ->call($method, $url, str_contains($url, '/bundling/') ? familyBundlePayload() : registrationPayload())
        ->assertRedirect(route('my-registration.show'))
        ->assertSessionHas('info');

    expect(Registration::count())->toBe(1)
        ->and(Storage::disk('identity_photos')->allFiles())->toBeEmpty();
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
                '3K Family Bundle', '3 KM · 4 peserta', 'Rp100.000 per paket', 'Sisa 19 dari 20 paket',
                '5K Couple Bundle', '5 KM · 2 peserta', 'Rp100.000 per paket', 'Kuota 50 paket',
            ])
            ->assertSee([route('registration.bundle.create', 'family'), route('registration.bundle.create', 'couple')]);
    });

    it('asks for every other member of the bundle and the right identity photo', function (string $bundle, int $memberSlots, int $identityNumberFields, string $photoLabel, string $photoField, string $intro) {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('registration.bundle.create', $bundle))
            ->assertOk()
            ->assertSee(['Bundle Members', 'Biaya registrasi Rp100.000', $intro])
            ->assertSee($photoLabel)
            ->assertSee('name="'.$photoField.'"', false)
            ->assertSee('name="members['.($memberSlots - 1).'][full_name]"', false)
            ->assertDontSee('name="members['.$memberSlots.'][full_name]"', false)
            ->assertDontSee(['maks. 4 peserta', 'maks. 2 peserta', 'maksimal 3', 'minimal 1', '(opsional)</span>'], false);

        // Every member gives medical information; only Couple Bundle members type a KTP/Passport number.
        expect(substr_count($response->getContent(), '][identity_number]"'))->toBe($identityNumberFields)
            ->and(substr_count($response->getContent(), '][medical_history]"'))->toBe($memberSlots)
            ->and(substr_count($response->getContent(), '][drug_allergies]"'))->toBe($memberSlots)
            ->and(substr_count($response->getContent(), 'type="file"'))->toBe(1);
    })->with([
        'family: 3 more people, Kartu Keluarga' => ['family', 3, 0, 'Foto Kartu Keluarga (KK)', 'family_card_photo', 'paket ini wajib diisi tepat 4 peserta'],
        'couple: 1 more person, KTP' => ['couple', 1, 1, 'Foto KTP', 'ktp_photo', 'Isi data pasanganmu.'],
    ]);

    it('registers a Family Bundle of 4 people with one Kartu Keluarga photo and no KTP', function () {
        $user = User::factory()->create(['email' => 'hendra@gmail.com']);

        $this->actingAs($user)
            ->post(route('registration.bundle.store', 'family'), familyBundlePayload([
                'members' => [
                    familyMember(['full_name' => 'Indah Wijaya']),
                    familyMember(['full_name' => 'Joko Wijaya', 'gender' => 'male', 'phone' => '0812-0000-1111', 'medical_history' => 'Asma', 'drug_allergies' => 'Amoksisilin']),
                    familyMember(['full_name' => 'Kartika Wijaya', 'identity_number' => '3578011402000003']),
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
            ->code->toBe('UCCR-035-0001')
            ->bib_number->toBeNull()
            ->amount->toBe(100000)
            // No family member has a BIB until the bundle's payment is confirmed.
            ->and($registration->members->map->only(['position', 'full_name', 'phone', 'bib_number'])->all())->toBe([
                ['position' => 2, 'full_name' => 'Indah Wijaya', 'phone' => null, 'bib_number' => null],
                ['position' => 3, 'full_name' => 'Joko Wijaya', 'phone' => '081200001111', 'bib_number' => null],
                ['position' => 4, 'full_name' => 'Kartika Wijaya', 'phone' => null, 'bib_number' => null],
            ])
            // The Kartu Keluarga covers the whole family: no member keeps a KTP/Passport number, even one the browser sent.
            ->and($registration->members->pluck('identity_number')->all())->toBe([null, null, null])
            ->and($registration->members->map->only(['medical_history', 'drug_allergies'])->all())->toBe([
                ['medical_history' => 'Tidak Ada', 'drug_allergies' => 'Tidak Ada'],
                ['medical_history' => 'Asma', 'drug_allergies' => 'Amoksisilin'],
                ['medical_history' => 'Tidak Ada', 'drug_allergies' => 'Tidak Ada'],
            ])
            ->and($registration->ktp_photo_path)->toBeNull()
            ->and($registration->family_card_photo_path)->toStartWith('kk/')
            ->and(Storage::disk('identity_photos')->allFiles())->toBe([$registration->family_card_photo_path])
            ->and($family->remaining())->toBe(19);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v2/charge') && $request['transaction_details']['gross_amount'] === 100000);
    });

    it('asks a Family Bundle for a Kartu Keluarga photo, not a KTP photo', function () {
        $payload = familyBundlePayload(['ktp_photo' => UploadedFile::fake()->image('ktp.jpg', 1200, 760)]);
        unset($payload['family_card_photo']);

        $this->actingAs(User::factory()->create())
            ->post(route('registration.bundle.store', 'family'), $payload)
            ->assertSessionHasErrors(['family_card_photo' => 'Foto Kartu Keluarga (KK) wajib diunggah.'])
            ->assertSessionDoesntHaveErrors(['ktp_photo', 'members.0.identity_number']);

        expect(Registration::count())->toBe(0)
            ->and(Storage::disk('identity_photos')->allFiles())->toBeEmpty();
    });

    it('checks the Kartu Keluarga photo like a KTP photo', function (UploadedFile $photo, string $message) {
        $this->actingAs(User::factory()->create())
            ->post(route('registration.bundle.store', 'family'), familyBundlePayload(['family_card_photo' => $photo]))
            ->assertSessionHasErrors(['family_card_photo' => $message]);

        expect(Registration::count())->toBe(0);
    })->with([
        'a PDF' => [fn () => uploadedFile('kk.pdf', "%PDF-1.4\n%%EOF"), 'Foto Kartu Keluarga (KK) harus berupa gambar JPG, JPEG, atau PNG.'],
        'larger than 5 MB' => [fn () => UploadedFile::fake()->image('kk.jpg', 1100, 780)->size(5 * 1024 + 1), 'Ukuran Foto Kartu Keluarga (KK) maksimal 5 MB.'],
    ]);

    it('still asks a Couple Bundle for a KTP photo and the partner’s KTP/Passport number', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('registration.bundle.store', 'couple'), registrationPayload([
                'ktp_photo' => null,
                'members' => [bundleMember(['identity_number' => ''])],
            ]))
            ->assertSessionHasErrors([
                'ktp_photo' => 'Foto KTP wajib diunggah.',
                'members.0.identity_number' => 'KTP/Passport anggota wajib diisi.',
            ])
            ->assertSessionDoesntHaveErrors('family_card_photo');
    });

    it('requires exactly the number of people in the bundle', function (string $bundle, array $members, string $message) {
        $payload = $bundle === 'family' ? familyBundlePayload(['members' => $members]) : registrationPayload(['members' => $members]);

        $this->actingAs(User::factory()->create())
            ->post(route('registration.bundle.store', $bundle), $payload)
            ->assertSessionHasErrors(['members' => $message]);

        expect(Registration::count())->toBe(0)
            ->and(Storage::disk('identity_photos')->allFiles())->toBeEmpty();
    })->with([
        'family of 5' => ['family', array_fill(0, 4, familyMember()), '3K Family Bundle harus diisi tepat 4 peserta: kamu dan 3 anggota lainnya.'],
        'family of 3' => ['family', array_fill(0, 2, familyMember()), '3K Family Bundle harus diisi tepat 4 peserta: kamu dan 3 anggota lainnya.'],
        'family of 2' => ['family', [familyMember()], '3K Family Bundle harus diisi tepat 4 peserta: kamu dan 3 anggota lainnya.'],
        'family alone' => ['family', [], '3K Family Bundle harus diisi tepat 4 peserta: kamu dan 3 anggota lainnya.'],
        'couple of 3' => ['couple', array_fill(0, 2, bundleMember()), '5K Couple Bundle harus diisi tepat 2 peserta: kamu dan 1 anggota lainnya.'],
        'couple alone' => ['couple', [], '5K Couple Bundle harus diisi tepat 2 peserta: kamu dan 1 anggota lainnya.'],
    ]);

    it('points at the fields of a Family Bundle member left empty', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('registration.bundle.store', 'family'), familyBundlePayload([
                'members' => [familyMember(), familyMember(), ['full_name' => '', 'phone' => '', 'birth_date' => '', 'gender' => '', 'tshirt_size' => '', 'medical_history' => '', 'drug_allergies' => '']],
            ]))
            ->assertSessionHasErrors([
                'members.2.full_name' => 'Nama anggota wajib diisi.',
                'members.2.birth_date' => 'Tanggal lahir anggota wajib diisi.',
                'members.2.gender' => 'Pilih gender anggota.',
                'members.2.tshirt_size' => 'Pilih ukuran T-shirt anggota.',
                'members.2.medical_history' => 'Riwayat penyakit anggota wajib diisi. Tulis “Tidak Ada” jika tidak memiliki riwayat penyakit.',
                'members.2.drug_allergies' => 'Alergi obat anggota wajib diisi. Tulis “Tidak Ada” jika tidak memiliki alergi obat.',
            ])
            ->assertSessionDoesntHaveErrors(['members', 'members.0.full_name', 'members.1.full_name']);

        expect(Registration::count())->toBe(0);
    });

    it('validates every member', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('registration.bundle.store', 'couple'), registrationPayload([
                'members' => [bundleMember(['full_name' => '', 'identity_number' => '12-3', 'phone' => '999', 'gender' => 'other', 'birth_date' => '2099-01-01', 'medical_history' => '', 'drug_allergies' => ''])],
            ]))
            ->assertSessionHasErrors([
                'members.0.full_name' => 'Nama anggota wajib diisi.',
                'members.0.identity_number' => 'KTP/Passport anggota harus terdiri dari 6–20 huruf atau angka.',
                'members.0.phone' => 'Format Nomor telepon anggota tidak valid. Gunakan format 08xxxxxxxxxx.',
                'members.0.gender' => 'Gender anggota yang dipilih tidak valid.',
                'members.0.birth_date' => 'Tanggal lahir anggota harus sebelum hari ini.',
                'members.0.medical_history' => 'Riwayat penyakit anggota wajib diisi. Tulis “Tidak Ada” jika tidak memiliki riwayat penyakit.',
                'members.0.drug_allergies' => 'Alergi obat anggota wajib diisi. Tulis “Tidak Ada” jika tidak memiliki alergi obat.',
            ])
            ->assertSessionDoesntHaveErrors(['members.0.ktp_photo']);

        expect(Registration::count())->toBe(0);
    });

    it('keeps a full bundle closed', function () {
        setPackage('bundling', null, '5k', ['quota' => 1]);
        Registration::factory()->bundle('couple')->paid()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('registration.bundle.create', 'couple'))
            ->assertRedirect(route('registration.price', 'bundling'))
            ->assertSessionHas('error', 'Kuota paket 5K Couple Bundle (2 peserta) sudah habis. Silakan pilih paket lain.');

        $this->post(route('registration.bundle.store', 'couple'), registrationPayload(['members' => [bundleMember()]]))
            ->assertRedirect(route('registration.price', 'bundling'));

        expect(Registration::count())->toBe(1);
    });
});

describe('Foto KTP, Riwayat Penyakit and Alergi Obat', function () {
    it('asks the payer for a KTP photo and medical information', function () {
        $this->actingAs(User::factory()->create())
            ->get(route('registration.create', ['external', 'early-bird', '5k']))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSeeInOrder(['Full Nama (Nama Pembayar)', 'Foto KTP', 'JPG, JPEG, atau PNG, maks. 5 MB', 'Riwayat Penyakit', 'Alergi Obat'])
            ->assertSee(['type="file"', 'name="ktp_photo"', 'accept="image/jpeg,image/png"', 'name="medical_history"', 'name="drug_allergies"'], false)
            ->assertDontSee(['name="identity_number"', 'KTP/Passport'], false);
    });

    it('accepts “Tidak Ada” for Riwayat Penyakit and Alergi Obat', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload(['medical_history' => 'Tidak Ada', 'drug_allergies' => 'Tidak Ada']))
            ->assertRedirect(route('payment.show'));

        expect($user->registration()->sole())
            ->medical_history->toBe('Tidak Ada')
            ->drug_allergies->toBe('Tidak Ada');
    });

    it('saves a JPG, JPEG or PNG photo on the private disk under a random name', function (UploadedFile $photo, string $extension) {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload(['ktp_photo' => $photo]))
            ->assertRedirect(route('payment.show'));

        $path = $user->registration()->sole()->ktp_photo_path;
        expect($path)->toMatch('/^ktp\/[A-Za-z0-9]{40}\.'.$extension.'$/')
            ->and(Storage::disk('identity_photos')->exists($path))->toBeTrue()
            ->and(Storage::disk('identity_photos')->allFiles())->toHaveCount(1);
    })->with([
        'jpg' => [fn () => uploadedImage('ktp.jpg'), 'jpg'],
        'jpeg, upper case name' => [fn () => uploadedImage('KTP Budi.JPEG'), 'jpg'],
        'png' => [fn () => uploadedImage('ktp.png', 'png'), 'png'],
        'exactly 5 MB' => [fn () => UploadedFile::fake()->image('ktp.jpg', 1200, 760)->size(5 * 1024), 'jpg'],
    ]);

    it('refuses files that are not a JPG or PNG image, whatever their name says', function (UploadedFile $file) {
        $this->actingAs(User::factory()->create())
            ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload(['ktp_photo' => $file]))
            ->assertSessionHasErrors(['ktp_photo' => 'Foto KTP harus berupa gambar JPG, JPEG, atau PNG.']);

        expect(Registration::count())->toBe(0)
            ->and(Storage::disk('identity_photos')->allFiles())->toBeEmpty();
    })->with([
        'a PDF' => fn () => uploadedFile('ktp.pdf', "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF"),
        'a text file named .jpg' => fn () => uploadedFile('ktp.jpg', 'This is not a photo.'),
        'a PHP script named .png' => fn () => uploadedFile('ktp.png', '<?php echo "hello"; ?>'),
        'a GIF image' => fn () => uploadedImage('ktp.gif', 'gif'),
        'a GIF image named .jpg' => fn () => uploadedImage('ktp.jpg', 'gif'),
        'an SVG image' => fn () => uploadedFile('ktp.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"/>'),
        'a JPG image named .php' => fn () => uploadedImage('ktp.php'),
    ]);

    it('refuses a photo larger than 5 MB', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload([
                'ktp_photo' => UploadedFile::fake()->image('ktp.jpg', 1200, 760)->size(5 * 1024 + 1),
            ]))
            ->assertSessionHasErrors(['ktp_photo' => 'Ukuran Foto KTP maksimal 5 MB.']);

        expect(Registration::count())->toBe(0)
            ->and(Storage::disk('identity_photos')->allFiles())->toBeEmpty();
    });

    it('explains when the upload itself failed, for example above the server upload limit', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload([
                'ktp_photo' => uploadedFile('ktp.jpg', '', UPLOAD_ERR_INI_SIZE),
            ]))
            ->assertSessionHasErrors(['ktp_photo' => 'Foto KTP gagal diunggah. Pastikan ukurannya tidak lebih dari 5 MB, lalu pilih ulang fotonya.']);

        expect(Registration::count())->toBe(0);
    });

    it('refuses a photo too small to read', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload(['ktp_photo' => uploadedImage('ktp.jpg', 'jpeg', 200, 120)]))
            ->assertSessionHasErrors(['ktp_photo' => 'Foto KTP tidak dapat dibaca atau terlalu kecil. Gunakan foto KTP yang jelas, minimal 320 × 200 piksel.']);

        expect(Registration::count())->toBe(0);
    });

    it('stores no photo when another field is invalid', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload(['medical_history' => '']))
            ->assertSessionHasErrors('medical_history')
            ->assertSessionMissing('_old_input.ktp_photo');

        expect(Storage::disk('identity_photos')->allFiles())->toBeEmpty();
    });

    it('removes the uploaded photo when the registration cannot be saved', function () {
        Registration::creating(fn () => throw new RuntimeException('Database unavailable'));
        $this->withoutExceptionHandling();
        $user = User::factory()->create();

        expect(fn () => $this->actingAs($user)->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload()))
            ->toThrow(RuntimeException::class, 'Database unavailable');

        expect(Registration::count())->toBe(0)
            ->and(Storage::disk('identity_photos')->allFiles())->toBeEmpty();
    });

    it('sends the participant back to the form when the photo cannot be saved', function () {
        Exceptions::fake();
        $failingDisk = Mockery::mock(FilesystemAdapter::class);
        $failingDisk->shouldReceive('putFileAs')->andThrow(new RuntimeException('Disk full'));
        Storage::set('identity_photos', $failingDisk);
        $form = route('registration.create', ['external', 'early-bird', '5k']);

        $this->actingAs(User::factory()->create())
            ->from($form)
            ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload(['full_name' => 'Rina Wijaya']))
            ->assertRedirect($form)
            ->assertSessionHasErrors(['ktp_photo' => 'Foto KTP gagal disimpan. Silakan pilih ulang fotonya lalu kirim kembali.'])
            ->assertSessionHasInput('full_name', 'Rina Wijaya');

        expect(Registration::count())->toBe(0);
        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Disk full');
        Http::assertNothingSent();
    });
});
