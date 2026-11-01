<?php

use App\Models\Order;
use App\Models\Registration;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registration page can be rendered', function () {
    $response = $this->get('/registration');

    $response->assertStatus(200);
    $response->assertSee('LOGO');
    $response->assertSee('1. PERSONAL INFORMATION');
    $response->assertSee('2. RACE INFORMATION');
    $response->assertSee('3. ADDRESS INFORMATION');
    $response->assertSee('Submit Registration');
});

test('participant can submit registration successfully', function () {
    $formData = [
        'full_name' => 'Edward Christopher',
        'email' => 'edward@example.com',
        'phone_number' => '081234567890',
        'date_of_birth' => '2000-05-15',
        'gender' => 'Male',
        'ktp_passport' => '3578012345670001',
        'race_category' => '5K Run',
        'tshirt_size' => 'L',
        'emergency_name' => 'Budi Christopher',
        'emergency_phone' => '081298765432',
        'province' => 'Jawa Timur',
        'city' => 'Surabaya',
        'address' => 'Jl. CitraLand No. 1, Sambikerep',
        'agreement' => '1',
    ];

    $response = $this->post('/registration', $formData);

    $response->assertRedirect(route('my-registration'));

    $this->assertDatabaseHas('registrations', [
        'full_name' => 'Edward Christopher',
        'email' => 'edward@example.com',
        'race_category' => '5K Run',
        'tshirt_size' => 'L',
        'amount' => 150000,
        'payment_status' => 'Pending',
    ]);

    $registration = Registration::where('email', 'edward@example.com')->first();
    expect($registration)->not->toBeNull();
    expect($registration->order_id)->not->toBeNull();

    // Verify order was also created
    $this->assertDatabaseHas('orders', [
        'order_id' => $registration->order_id,
        'participant_name' => 'Edward Christopher',
        'status' => 'PENDING',
    ]);
});

test('registration requires mandatory fields', function () {
    $response = $this->post('/registration', []);

    $response->assertSessionHasErrors([
        'full_name',
        'email',
        'phone_number',
        'date_of_birth',
        'gender',
        'ktp_passport',
        'race_category',
        'tshirt_size',
        'emergency_name',
        'emergency_phone',
        'province',
        'city',
        'address',
        'agreement',
    ]);
});

test('my registration shows empty state when no registration exists', function () {
    $response = $this->get('/my-registration');

    $response->assertStatus(200);
    $response->assertSee('No Registration Yet');
    $response->assertSee('Register Now');
});

test('my registration shows pending state with payment action for unpaid participant', function () {
    $order = Order::create([
        'order_id' => 'UCRUN-TEST-001',
        'participant_name' => 'Edward Christopher',
        'race_category' => '10K Run',
        'tshirt_size' => 'M',
        'item_name' => 'UC Color Run 10K Run',
        'amount' => 200000,
        'status' => 'PENDING',
    ]);

    $registration = Registration::create([
        'registration_code' => 'UCR-2027-12345',
        'full_name' => 'Edward Christopher',
        'email' => 'edward@example.com',
        'phone_number' => '081234567890',
        'date_of_birth' => '2000-05-15',
        'gender' => 'Male',
        'ktp_passport' => '3578012345670001',
        'race_category' => '10K Run',
        'tshirt_size' => 'M',
        'emergency_name' => 'Budi Christopher',
        'emergency_phone' => '081298765432',
        'province' => 'Jawa Timur',
        'city' => 'Surabaya',
        'address' => 'CitraLand Surabaya',
        'amount' => 200000,
        'payment_status' => 'Pending',
        'order_id' => $order->order_id,
        'agreement' => true,
    ]);

    $response = $this->withSession(['registered_id' => $registration->id])
        ->get('/my-registration');

    $response->assertStatus(200);
    $response->assertSee('Registration Submitted');
    $response->assertSee('UCR-2027-12345');
    $response->assertSee('Pay Now');
    $response->assertSee('Edit Registration');
    $response->assertSee('Rp200.000');
});

test('participant can edit registration before payment', function () {
    $registration = Registration::create([
        'registration_code' => 'UCR-2027-99999',
        'full_name' => 'Old Name',
        'email' => 'old@example.com',
        'phone_number' => '081111111111',
        'date_of_birth' => '1998-01-01',
        'gender' => 'Male',
        'ktp_passport' => '1234567890',
        'race_category' => '5K Run',
        'tshirt_size' => 'S',
        'emergency_name' => 'Contact',
        'emergency_phone' => '082222222222',
        'province' => 'DKI Jakarta',
        'city' => 'Jakarta Pusat',
        'address' => 'Jl. Sudirman',
        'amount' => 150000,
        'payment_status' => 'Pending',
        'agreement' => true,
    ]);

    $response = $this->put("/registration/{$registration->id}", [
        'full_name' => 'Updated Name',
        'email' => 'updated@example.com',
        'phone_number' => '081111111111',
        'date_of_birth' => '1998-01-01',
        'gender' => 'Male',
        'ktp_passport' => '1234567890',
        'race_category' => '10K Run',
        'tshirt_size' => 'XL',
        'emergency_name' => 'Contact',
        'emergency_phone' => '082222222222',
        'province' => 'DKI Jakarta',
        'city' => 'Jakarta Pusat',
        'address' => 'Jl. Thamrin',
    ]);

    $response->assertRedirect(route('my-registration'));

    $registration->refresh();
    expect($registration->full_name)->toBe('Updated Name');
    expect($registration->race_category)->toBe('10K Run');
    expect($registration->tshirt_size)->toBe('XL');
    expect($registration->amount)->toBe(200000);
});

test('my registration shows paid state with view my bib when paid', function () {
    $registration = Registration::create([
        'registration_code' => 'UCR-2027-PAID1',
        'full_name' => 'Paid Runner',
        'email' => 'paid@example.com',
        'phone_number' => '081234567890',
        'date_of_birth' => '1995-10-10',
        'gender' => 'Female',
        'ktp_passport' => '3578012345670002',
        'race_category' => '5K Run',
        'tshirt_size' => 'M',
        'emergency_name' => 'Family Contact',
        'emergency_phone' => '081298765432',
        'province' => 'Jawa Timur',
        'city' => 'Surabaya',
        'address' => 'Surabaya Barat',
        'amount' => 150000,
        'payment_status' => 'Paid',
        'bib_number' => 'UC27888',
        'agreement' => true,
    ]);

    $response = $this->withSession(['registered_id' => $registration->id])
        ->get('/my-registration');

    $response->assertStatus(200);
    $response->assertSee('Registration Confirmed');
    $response->assertSee('View My BIB');
});

test('paid participant can access bib page', function () {
    $registration = Registration::create([
        'registration_code' => 'UCR-2027-BIB99',
        'full_name' => 'BIB Runner',
        'email' => 'bib@example.com',
        'phone_number' => '081234567890',
        'date_of_birth' => '1995-10-10',
        'gender' => 'Male',
        'ktp_passport' => '3578012345670003',
        'race_category' => '21K Half Marathon',
        'tshirt_size' => 'L',
        'emergency_name' => 'Emergency',
        'emergency_phone' => '081298765432',
        'province' => 'Bali',
        'city' => 'Denpasar',
        'address' => 'Denpasar Bali',
        'amount' => 300000,
        'payment_status' => 'Paid',
        'bib_number' => 'UC27999',
        'agreement' => true,
    ]);

    $response = $this->withSession(['registered_id' => $registration->id])
        ->get('/my-bib');

    $response->assertStatus(200);
    $response->assertSee('UC27999');
    $response->assertSee('BIB Runner');
    $response->assertSee('21K Half Marathon');
});
