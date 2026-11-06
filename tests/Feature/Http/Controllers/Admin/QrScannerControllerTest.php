<?php

use App\Models\Admin;
use App\Models\Registration;

beforeEach(function () {
    $this->admin = Admin::factory()->create();
});

it('lists the ten most recent BIB collections', function () {
    $latest = Registration::factory()->bibCollected()->create(['full_name' => 'Rina Wijaya', 'bib_collected_at' => now()]);
    Registration::factory()->count(9)->bibCollected()->create(['bib_collected_at' => now()->subHour()]);
    $oldest = Registration::factory()->bibCollected()->create(['full_name' => 'Peserta Terlama', 'bib_collected_at' => now()->subDay()]);
    Registration::factory()->paid()->create(['full_name' => 'Belum Mengambil']);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.scanner.index'))
        ->assertOk()
        ->assertSee(['QR Scanner', 'Scan the QR Code to collect your BIB', 'Start Camera', 'Recent QR Scanned'])
        ->assertSeeInOrder(['Recent QR Scanned', $latest->code, 'Rina Wijaya'])
        ->assertDontSee(['Peserta Terlama', 'Belum Mengambil']);
});

it('hands over the BIB when a paid participant QR code is scanned', function () {
    $this->freezeSecond();
    $registration = Registration::factory()->paid()->create(['bib_number' => '50012']);

    $this->actingAs($this->admin, 'admin')
        ->postJson(route('admin.scanner.store'), ['payload' => $registration->racepackPayload()])
        ->assertOk()
        ->assertJson([
            'tone' => 'success',
            'message' => "Successfully scanned {$registration->code}. Your BIB Number is 50012.",
        ])
        ->assertJsonPath('row', fn (string $row): bool => str_contains($row, $registration->code) && str_contains($row, '50012'));

    expect($registration->fresh())
        ->bib_collected_at->toEqual(now())
        ->bib_collected_by->toBe($this->admin->id);
});

it('accepts a typed registration ID in any letter case', function () {
    $registration = Registration::factory()->paid()->create(['code' => 'UCR-2027-ABCDE']);

    $this->actingAs($this->admin, 'admin')
        ->postJson(route('admin.scanner.store'), ['code' => ' ucr-2027-abcde '])
        ->assertJson(['tone' => 'success']);

    expect($registration->fresh()->hasCollectedBib())->toBeTrue();
});

it('warns when the BIB was already collected', function () {
    $firstDesk = Admin::factory()->create();
    $registration = Registration::factory()->bibCollected($firstDesk)->create([
        'bib_number' => '50012',
        'bib_collected_at' => '2027-03-20 08:15:00',
    ]);

    $this->actingAs($this->admin, 'admin')
        ->postJson(route('admin.scanner.store'), ['payload' => $registration->racepackPayload()])
        ->assertOk()
        ->assertExactJson([
            'tone' => 'warning',
            'message' => "{$registration->code} sudah mengambil BIB 50012 pada 20 Mar 2027, 08:15.",
            'row' => null,
        ]);

    expect($registration->fresh()->bib_collected_by)->toBe($firstDesk->id);
});

it('refuses codes that cannot be handed a BIB', function (Closure $payload, string $message) {
    $this->actingAs($this->admin, 'admin')
        ->postJson(route('admin.scanner.store'), $payload())
        ->assertOk()
        ->assertJson(['tone' => 'error', 'message' => $message, 'row' => null]);

    expect(Registration::query()->bibCollected()->exists())->toBeFalse();
})->with([
    'unpaid registration' => [
        fn () => ['code' => Registration::factory()->create(['code' => 'UCR-2027-UNPAY'])->code],
        'Registrasi UCR-2027-UNPAY belum lunas, sehingga BIB belum dapat diberikan.',
    ],
    'unknown registration' => [
        fn () => ['code' => 'UCR-2027-NOPE1'],
        'Registrasi UCR-2027-NOPE1 tidak ditemukan.',
    ],
    'BIB number that does not match' => [
        fn () => ['payload' => 'UCR2027|'.Registration::factory()->paid()->create(['code' => 'UCR-2027-MATCH', 'bib_number' => '50012'])->code.'|59999'],
        'QR code tidak cocok dengan data registrasi UCR-2027-MATCH.',
    ],
    'QR code from elsewhere' => [
        fn () => ['payload' => 'https://example.com/ticket/123'],
        'QR code tidak dikenali. Pastikan peserta menunjukkan QR racepack dari halaman My BIB.',
    ],
]);

it('requires a QR payload or a registration ID', function () {
    $this->actingAs($this->admin, 'admin')
        ->postJson(route('admin.scanner.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['payload' => 'Pindai QR code atau masukkan Registration ID.']);
});

it('reports the result as a flash message when JavaScript is unavailable', function () {
    $registration = Registration::factory()->paid()->create(['bib_number' => '30004']);

    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.scanner.store'), ['code' => $registration->code])
        ->assertRedirect(route('admin.scanner.index'))
        ->assertSessionHas('success', "Successfully scanned {$registration->code}. Your BIB Number is 30004.");
});

it('rejects scans from guests', function () {
    $registration = Registration::factory()->paid()->create();

    $this->postJson(route('admin.scanner.store'), ['code' => $registration->code])->assertUnauthorized();

    expect($registration->fresh()->hasCollectedBib())->toBeFalse();
});
