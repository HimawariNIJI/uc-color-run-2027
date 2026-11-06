<?php

use App\Enums\FeePeriod;
use App\Enums\PaymentStatus;
use App\Enums\RaceCategory;
use App\Enums\RegistrationType;
use App\Models\Registration;
use App\Models\User;

it('lets guests choose a registration type, price type and distance', function () {
    config(['event.pricing.external' => [
        '3k' => ['early_bird' => 125000, 'regular' => 150000],
        '5k' => ['early_bird' => 150000, 'regular' => 175000],
        '10k' => ['early_bird' => 200000, 'regular' => 225000],
    ]]);

    $this->get(route('registration.type'))
        ->assertOk()
        ->assertSee(['Internal', 'External', 'Bundling', route('registration.price', 'external')]);

    $this->get(route('registration.price', 'external'))
        ->assertOk()
        ->assertSeeInOrder(['Pilih Tipe Harga', 'Early Bird', 'Rp125.000 – Rp200.000', 'Slot terbatas', 'Normal Price', 'Rp150.000 – Rp225.000'])
        ->assertSee([route('registration.category', ['external', 'early-bird']), route('registration.category', ['external', 'normal'])]);

    $this->get(route('registration.category', ['external', 'normal']))
        ->assertOk()
        ->assertSeeInOrder(['3 KM', 'Normal Price · Rp150.000', '5 KM', 'Normal Price · Rp175.000', '10 KM', 'Normal Price · Rp225.000'])
        ->assertSee(route('registration.create', ['external', 'normal', '5k']))
        ->assertDontSee('Sisa');
});

it('shows the Early Bird slots left for each distance', function () {
    config(['event.early_bird_slots' => ['3k' => 2, '5k' => 50, '10k' => 1]]);
    Registration::factory()->create(['category' => '3k']);
    Registration::factory()->paid()->create(['category' => '10k']);

    $this->get(route('registration.category', ['internal', 'early-bird']))
        ->assertOk()
        ->assertSeeInOrder(['3 KM', 'Sisa 1 slot', '5 KM', 'Sisa 50 slot', '10 KM', 'Sold out'])
        ->assertSee(route('registration.create', ['internal', 'early-bird', '3k']))
        ->assertDontSee(route('registration.create', ['internal', 'early-bird', '10k']));
});

it('gives back the Early Bird slot of a failed payment', function () {
    config(['event.early_bird_slots' => ['3k' => 1, '5k' => 1, '10k' => 1]]);
    Registration::factory()->failed()->create(['category' => '5k']);
    Registration::factory()->normalPrice()->create(['category' => '5k']);

    $this->get(route('registration.category', ['internal', 'early-bird']))
        ->assertSeeInOrder(['5 KM', 'Sisa 1 slot']);
});

it('marks Early Bird as sold out once every distance is full', function () {
    config(['event.early_bird_slots' => ['3k' => 0, '5k' => 0, '10k' => 0]]);

    $this->get(route('registration.price', 'internal'))
        ->assertOk()
        ->assertSee('Sold out')
        ->assertDontSee(route('registration.category', ['internal', 'early-bird']))
        ->assertSee(route('registration.category', ['internal', 'normal']));

    $this->get(route('registration.category', ['internal', 'early-bird']))
        ->assertRedirect(route('registration.price', 'internal'))
        ->assertSessionHas('error', 'Slot Early Bird sudah habis untuk semua jarak lari. Silakan pilih Normal Price.');
});

it('returns 404 for an unknown registration type, price type or category', function (string $url) {
    $this->actingAs(User::factory()->create())->get($url)->assertNotFound();
})->with([
    '/registration/vip',
    '/registration/external/regular',
    '/registration/external/early_bird',
    '/registration/external/early-bird/21k',
]);

it('asks guests to sign in before filling the form', function () {
    $this->get(route('registration.create', ['external', 'early-bird', '5k']))->assertRedirect(route('login'));
    $this->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload())->assertRedirect(route('login'));

    expect(Registration::count())->toBe(0);
});

it('prefills the form with the participant name, email and chosen price', function () {
    config(['event.pricing.external.5k.regular' => 175000]);
    $user = User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@example.com']);

    $this->actingAs($user)
        ->get(route('registration.create', ['external', 'normal', '5k']))
        ->assertOk()
        ->assertSee('value="Budi Santoso"', false)
        ->assertSee('value="budi@example.com"', false)
        ->assertSee('value="5K External"', false)
        ->assertSee('Biaya registrasi Rp175.000 (Normal Price)')
        ->assertSee(route('registration.store', ['external', 'normal', '5k']));
});

it('sends participants back to the distances when the Early Bird slots of a distance are gone', function () {
    config(['event.early_bird_slots.5k' => 1]);
    Registration::factory()->create(['category' => '5k']);

    $this->actingAs(User::factory()->create())
        ->get(route('registration.create', ['external', 'early-bird', '5k']))
        ->assertRedirect(route('registration.category', ['external', 'early-bird']))
        ->assertSessionHas('error', 'Slot Early Bird untuk 5 KM sudah habis. Pilih jarak lain atau Normal Price.');
});

it('creates a registration at the early bird fee and opens the payment window', function () {
    $this->freezeSecond();
    config(['event.pricing.external.5k.early_bird' => 150000]);
    $user = User::factory()->create(['email' => 'budi@example.com']);

    $this->actingAs($user)
        ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload(['phone' => '0812-3456-7890']))
        ->assertRedirect(route('payment.show'));

    $registration = $user->registration()->sole();
    expect($registration)
        ->type->toBe(RegistrationType::External)
        ->category->toBe(RaceCategory::FiveK)
        ->email->toBe('budi@example.com')
        ->phone->toBe('081234567890')
        ->amount->toBe(150000)
        ->fee_period->toBe(FeePeriod::EarlyBird)
        ->payment_status->toBe(PaymentStatus::Unpaid)
        ->identity_number->toBe('3578011205990001')
        ->and($registration->code)->toMatch('/^UCR-2027-[A-Z2-9]{5}$/')
        ->and($registration->payment_expires_at->equalTo(now()->addMinutes(60)))->toBeTrue();
});

it('charges the normal price when the participant picks it', function () {
    config(['event.pricing.internal.10k.regular' => 200000]);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('registration.store', ['internal', 'normal', '10k']), registrationPayload());

    expect($user->registration()->sole())
        ->amount->toBe(200000)
        ->fee_period->toBe(FeePeriod::Regular);
});

it('keeps the form data when the last Early Bird slot is taken during sign-up', function () {
    config(['event.early_bird_slots.5k' => 1]);
    Registration::factory()->create(['category' => '5k']);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('registration.store', ['external', 'early-bird', '5k']), registrationPayload(['full_name' => 'Rina Wijaya']))
        ->assertRedirect(route('registration.create', ['external', 'normal', '5k']))
        ->assertSessionHas('error', 'Maaf, slot Early Bird untuk 5 KM baru saja habis. Datamu masih tersimpan di formulir ini dengan Normal Price. Periksa kembali lalu kirim ulang.')
        ->assertSessionHasInput('full_name', 'Rina Wijaya');

    expect($user->registration()->exists())->toBeFalse();
});

it('stores the identity number encrypted', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('registration.store', ['external', 'early-bird', '3k']), registrationPayload());

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
        ->call($method, $url, registrationPayload())
        ->assertRedirect(route('my-registration.show'))
        ->assertSessionHas('info');

    expect(Registration::count())->toBe(1);
})->with([
    'type page' => ['GET', '/registration'],
    'price type page' => ['GET', '/registration/external'],
    'category page' => ['GET', '/registration/external/early-bird'],
    'form' => ['GET', '/registration/external/early-bird/5k'],
    'submit' => ['POST', '/registration/external/early-bird/5k'],
]);
