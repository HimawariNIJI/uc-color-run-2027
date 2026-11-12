<?php

use App\Models\Admin;
use App\Models\Registration;
use App\Models\RegistrationMember;

beforeEach(function () {
    $this->admin = Admin::factory()->create(['name' => 'Panitia Racepack']);
});

it('lists registrations, newest first', function () {
    $older = Registration::factory()->paid()->create(['full_name' => 'Rina Wijaya', 'category' => '10k', 'bib_number' => '1020024', 'created_at' => now()->subDay()]);
    $newer = Registration::factory()->create(['full_name' => 'Budi Santoso']);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.index'))
        ->assertOk()
        ->assertSee(['Participant Registrations', 'Review and manage individual entries', '2 entries shown'])
        ->assertSeeInOrder([$newer->code, 'Budi Santoso', 'Unpaid', $older->code, 'Rina Wijaya', '10K', 'Paid', '1020024']);
});

it('filters registrations by search term, payment status and category', function (array $filters, array $expected) {
    Registration::factory()->paid()->create(['full_name' => 'Rina Wijaya', 'email' => 'rina@example.com', 'category' => '5k', 'code' => 'UCR-2027-AAAAA']);
    Registration::factory()->create(['full_name' => 'Budi Santoso', 'email' => 'budi@example.com', 'category' => '5k', 'code' => 'UCR-2027-BBBBB']);
    Registration::factory()->paid()->create(['full_name' => 'Siti Aminah', 'email' => 'siti@example.com', 'category' => '10k', 'code' => 'UCR-2027-CCCCC']);

    $response = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.index', $filters))
        ->assertOk();

    foreach (['Rina Wijaya', 'Budi Santoso', 'Siti Aminah'] as $name) {
        in_array($name, $expected, true) ? $response->assertSee($name) : $response->assertDontSee($name);
    }
})->with([
    'name' => [['search' => 'rina'], ['Rina Wijaya']],
    'email' => [['search' => 'budi@'], ['Budi Santoso']],
    'registration ID' => [['search' => 'cccc'], ['Siti Aminah']],
    'payment status' => [['status' => 'paid'], ['Rina Wijaya', 'Siti Aminah']],
    'category' => [['category' => '5k'], ['Rina Wijaya', 'Budi Santoso']],
    'status and category' => [['status' => 'paid', 'category' => '5k'], ['Rina Wijaya']],
    'unknown values are ignored' => [['status' => 'refunded', 'category' => '42k'], ['Rina Wijaya', 'Budi Santoso', 'Siti Aminah']],
]);

it('finds a registration by the BIB of its payer or of any bundle member', function (string $bib) {
    $family = Registration::factory()->bundle('family')->paid()->create(['full_name' => 'Hendra Wijaya', 'bib_number' => '0350001']);
    RegistrationMember::factory()->for($family)->create(['position' => 2, 'bib_number' => '0350002']);
    Registration::factory()->paid()->create(['full_name' => 'Budi Santoso', 'bib_number' => '0520001']);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.index', ['search' => $bib]))
        ->assertOk()
        ->assertSee(['Hendra Wijaya', '0350001'])
        ->assertDontSee('Budi Santoso');
})->with(['payer BIB' => '0350001', 'member BIB' => '0350002']);

it('shows every detail of a paid registration with its racepack QR code', function () {
    $this->travelTo('2026-10-20 09:00:00');
    $registration = Registration::factory()->paid()->withKtpPhoto()->create([
        'full_name' => 'Ethan Cannavaro Lauda',
        'email' => 'ethan@example.com',
        'phone' => '0812345678',
        'birth_date' => '2000-10-17',
        'gender' => 'male',
        'category' => '10k',
        'type' => 'external',
        'tshirt_size' => 'XL',
        'emergency_contact_name' => 'Edward',
        'emergency_contact_phone' => '0812999888',
        'medical_history' => 'Asma ringan',
        'drug_allergies' => 'Penisilin',
        'province' => 'Jawa Timur',
        'city' => 'Kota Surabaya',
        'address' => 'Jl. Raya CitraLand',
        'amount' => 150000,
        'transaction_id' => 'PAY-UCR-2027-ABCDE',
        'bib_number' => '1020024',
    ]);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.show', $registration))
        ->assertOk()
        ->assertSeeInOrder([
            'Registration Details', $registration->code,
            '1. Personal Information', 'Full Nama (Nama Pembayar)', 'Ethan Cannavaro Lauda', 'ethan@example.com', '0812345678', '17 Oktober 2000', 'Laki-laki',
            'Foto KTP', 'src="'.route('admin.registrations.identity-photo', $registration).'"', 'Lihat ukuran penuh',
            '2. Race Information', '10K', 'External', 'Early Bid', 'XL', 'Edward', '0812999888', 'Riwayat Penyakit', 'Asma ringan', 'Alergi Obat', 'Penisilin',
            '3. Address Information', 'Jawa Timur', 'Kota Surabaya', 'Jl. Raya CitraLand',
            '4. Registration Information', '20 Okt 2026', 'Rp150.000', '20 Okt 2026', 'Paid', 'PAY-UCR-2027-ABCDE', '1020024', 'Belum diambil',
            '5. QR Code', 'data-racepack-qr="UCCR-1020024"', '<svg', 'BIB 1020024',
        ], false)
        ->assertDontSee(['KTP/Passport', $registration->ktp_photo_path, 'Coming Soon']);
});

it('shows the typed KTP/Passport number of a registration made before KTP photos', function () {
    $registration = Registration::factory()->beforeKtpPhoto()->create(['identity_number' => '3578011205990001']);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.show', $registration))
        ->assertOk()
        ->assertSeeInOrder([
            'KTP/Passport', '3578011205990001', 'Foto KTP', 'Belum diunggah',
            'Riwayat Penyakit', 'Belum diisi', 'Alergi Obat', 'Belum diisi',
        ])
        ->assertDontSee('/identity-photo');
});

it('shows the Kartu Keluarga photo of a Family Bundle instead of a KTP photo', function () {
    $registration = Registration::factory()->withFamilyCardPhoto()->create();

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.show', $registration))
        ->assertOk()
        ->assertSeeInOrder(['1. Personal Information', 'Foto Kartu Keluarga (KK)', 'src="'.route('admin.registrations.identity-photo', $registration).'"', 'Lihat ukuran penuh', '2. Race Information'], false)
        ->assertDontSee(['Foto KTP', 'KTP/Passport', $registration->family_card_photo_path]);
});

it('shows the medical information of every bundle member', function () {
    $registration = Registration::factory()->bundle()->create();
    RegistrationMember::factory()->for($registration)->create(['position' => 2, 'full_name' => 'Indah Wijaya', 'medical_history' => "Asma\nKontrol rutin", 'drug_allergies' => 'Tidak Ada']);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.show', $registration))
        ->assertOk()
        ->assertSeeInOrder(['Bundle Members', 'Peserta 2', 'Indah Wijaya', 'Riwayat Penyakit:', "Asma\nKontrol rutin", 'Alergi Obat:', 'Tidak Ada']);
});

it('explains that the QR code appears once the registration is paid', function () {
    $registration = Registration::factory()->create();

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.show', $registration))
        ->assertOk()
        ->assertSee('QR tersedia setelah pembayaran lunas.')
        ->assertDontSee('QR code racepack');
});

it('shows when and by whom the BIB was collected', function () {
    $this->travelTo('2027-04-08 08:15:00');
    $registration = Registration::factory()->bibCollected($this->admin)->create();

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.show', $registration))
        ->assertSee('Sudah diambil · 8 Apr 2027, 08:15 oleh Panitia Racepack');
});

it('returns only the modal content to the admin modal', function () {
    $registration = Registration::factory()->create();

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.show', $registration), ['X-Admin-Modal' => '1'])
        ->assertOk()
        ->assertSee(['Registration Details', $registration->code])
        ->assertDontSee('<html', false);
});

it('returns not found for an unknown registration ID', function () {
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.show', 'UCR-2027-ZZZZZ'))
        ->assertNotFound();
});
