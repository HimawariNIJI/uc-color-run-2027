<?php

use App\Models\Admin;
use App\Models\Registration;

beforeEach(function () {
    $this->admin = Admin::factory()->create(['name' => 'Panitia Racepack']);
});

it('lists registrations, newest first', function () {
    $older = Registration::factory()->paid()->create(['full_name' => 'Rina Wijaya', 'category' => '10k', 'bib_number' => '10024', 'created_at' => now()->subDay()]);
    $newer = Registration::factory()->create(['full_name' => 'Budi Santoso']);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.index'))
        ->assertOk()
        ->assertSee(['Participant Registrations', 'Review and manage individual entries', '2 entries shown'])
        ->assertSeeInOrder([$newer->code, 'Budi Santoso', 'Unpaid', $older->code, 'Rina Wijaya', '10K', 'Paid', '10024']);
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

it('shows every detail of a paid registration with its racepack QR code', function () {
    $this->travelTo('2026-10-20 09:00:00');
    $registration = Registration::factory()->paid()->create([
        'full_name' => 'Ethan Cannavaro Lauda',
        'email' => 'ethan@example.com',
        'phone' => '0812345678',
        'birth_date' => '2000-10-17',
        'gender' => 'male',
        'identity_number' => '1234567890',
        'category' => '10k',
        'type' => 'external',
        'tshirt_size' => 'XL',
        'emergency_contact_name' => 'Edward',
        'emergency_contact_phone' => '0812999888',
        'province' => 'Jawa Timur',
        'city' => 'Kota Surabaya',
        'address' => 'Jl. Raya CitraLand',
        'amount' => 150000,
        'transaction_id' => 'PAY-UCR-2027-ABCDE',
        'bib_number' => '10024',
    ]);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.show', $registration))
        ->assertOk()
        ->assertSeeInOrder([
            'Registration Details', $registration->code,
            '1. Personal Information', 'Ethan Cannavaro Lauda', 'ethan@example.com', '0812345678', '17 Oktober 2000', 'Laki-laki', '1234567890',
            '2. Race Information', '10K', 'External', 'Early Bid', 'XL', 'Edward', '0812999888',
            '3. Address Information', 'Jawa Timur', 'Kota Surabaya', 'Jl. Raya CitraLand',
            '4. Registration Information', '20 Okt 2026', 'Rp150.000', '20 Okt 2026', 'Paid', 'PAY-UCR-2027-ABCDE', '10024', 'Belum diambil',
            '5. QR Code', 'BIB Pickup QR', 'Coming Soon',
        ])
        ->assertDontSee($registration->racepackPayload());
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
    $this->travelTo('2027-03-20 08:15:00');
    $registration = Registration::factory()->bibCollected($this->admin)->create();

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.registrations.show', $registration))
        ->assertSee('Sudah diambil · 20 Mar 2027, 08:15 oleh Panitia Racepack');
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
