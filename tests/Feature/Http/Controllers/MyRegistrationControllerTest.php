<?php

use App\Enums\PaymentStatus;
use App\Enums\TshirtSize;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * A 5K Early Bid Internal registration with its QRIS, ended by a verified Midtrans notification.
 */
function registrationWithEndedQris(string $midtransStatus): Registration
{
    $registration = Registration::factory()->create(['type' => 'internal', 'category' => '5k']);
    $registration->startQrisPayment();
    midtransReports($midtransStatus);
    test()->postJson(route('payments.midtrans.notification'), midtransNotification($registration->latestPayment()->first(), $midtransStatus, '202'))->assertOk();

    return $registration;
}

it('asks guests to sign in', function (string $routeName) {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with(['my-registration.show', 'my-registration.edit']);

it('shows the empty state when the participant has not registered', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('my-registration.show'))
        ->assertOk()
        ->assertSee('No Registration Yet')
        ->assertSee(route('registration.type'));
});

it('shows a pending registration with payment actions', function () {
    $registration = Registration::factory()->create(['amount' => 150000]);

    $this->actingAs($registration->user)
        ->get(route('my-registration.show'))
        ->assertOk()
        ->assertSee(['Pending', 'Registration Submitted', $registration->code, 'Full Nama (Nama Pembayar)', $registration->full_name, 'Unpaid', 'Rp150.000', 'Pay Now', 'Edit Registration']);
});

it('shows a confirmed registration with its racepack QR and a link to the BIB', function () {
    $registration = Registration::factory()->paid()->create(['bib_number' => '0520012']);

    $this->actingAs($registration->user)
        ->get(route('my-registration.show'))
        ->assertOk()
        ->assertSee(['Registration Confirmed', 'Racepack QR', 'BIB 0520012', 'View My BIB'])
        ->assertSee('data-racepack-qr="UCCR-0520012"', false)
        ->assertDontSee(['Edit Registration', 'Pay Again', 'Pay Now']);
});

describe('paying again', function () {
    it('offers Pay Now while the current QRIS can still be paid', function () {
        $registration = Registration::factory()->create(['type' => 'internal', 'category' => '5k']);
        $registration->startQrisPayment();

        $this->actingAs($registration->user)
            ->get(route('my-registration.show'))
            ->assertOk()
            ->assertSee(['Unpaid', 'Pay Now'])
            ->assertSee('href="'.route('payment.show').'"', false)
            ->assertDontSee(['Pay Again', 'Pembayaran ulang tidak tersedia']);
    });

    it('offers Pay Again after the payment expired, was cancelled or failed, while the package has a free slot', function (string $midtransStatus, string $status) {
        $registration = registrationWithEndedQris($midtransStatus);

        $this->actingAs($registration->user)
            ->get(route('my-registration.show'))
            ->assertOk()
            ->assertSee([$status, 'Pay Again', 'Edit Registration'])
            ->assertSee('action="'.route('payment.retry').'"', false)
            ->assertDontSee(['Pay Now', 'Pembayaran ulang tidak tersedia']);
    })->with([
        'expired' => ['expire', 'Expired'],
        'cancelled' => ['cancel', 'Cancelled'],
        'failed' => ['deny', 'Failed'],
    ]);

    it('checks an expired QRIS with Midtrans first, and shows the payment confirmed when it was paid at the last moment', function () {
        $registration = Registration::factory()->create(['type' => 'internal', 'category' => '5k']);
        $registration->startQrisPayment();
        $this->travel(61)->minutes();
        midtransReports('settlement');

        $this->actingAs($registration->user)
            ->get(route('my-registration.show'))
            ->assertOk()
            ->assertSee(['Registration Confirmed', 'View My BIB', 'BIB 0510001'])
            ->assertDontSee('Pay Again');
    });

    it('explains, without a working Pay Again button, that another participant took the last slot', function () {
        setPackage('internal', 'early_bird', '5k', ['quota' => 1]);
        $registration = Registration::factory()->create(['type' => 'internal', 'category' => '5k']);
        $registration->startQrisPayment();
        $this->travel(61)->minutes();
        midtransReports('expire');
        Registration::factory()->paid()->create(['type' => 'internal', 'category' => '5k']);

        $this->actingAs($registration->user)
            ->get(route('my-registration.show'))
            ->assertOk()
            ->assertSee(['Expired', 'Pembayaran ulang tidak tersedia', 'Kuota paket 5K Early Bid (Internal) sudah habis, jadi pembayaran ulang tidak dapat dilakukan.', 'Pay Again'])
            ->assertSee('disabled', false)
            ->assertDontSee('action="'.route('payment.retry').'"', false);

        // The page is only a hint: a request sent anyway is refused by the server as well.
        $this->post(route('payment.retry'))->assertRedirect(route('payment.failed'))->assertSessionHas('error');

        expect($registration->fresh()->payment_status)->toBe(PaymentStatus::Failed)
            ->and($registration->payments()->count())->toBe(1);
    });
});

it('updates an unpaid registration', function () {
    $registration = Registration::factory()->create(['tshirt_size' => TshirtSize::Small]);

    $this->actingAs($registration->user)
        ->put(route('my-registration.update'), registrationPayload(['tshirt_size' => 'XL', 'full_name' => 'Budi S.']))
        ->assertRedirect(route('my-registration.show'))
        ->assertSessionHas('success');

    expect($registration->fresh())
        ->tshirt_size->toBe(TshirtSize::ExtraLarge)
        ->full_name->toBe('Budi S.');
});

it('does not let participants change a paid registration', function () {
    $registration = Registration::factory()->paid()->create(['full_name' => 'Budi Santoso']);

    $this->actingAs($registration->user)
        ->get(route('my-registration.edit'))
        ->assertRedirect(route('my-registration.show'))
        ->assertSessionHas('error');

    $this->actingAs($registration->user)
        ->put(route('my-registration.update'), registrationPayload(['full_name' => 'Someone Else']))
        ->assertRedirect(route('my-registration.show'));

    expect($registration->fresh()->full_name)->toBe('Budi Santoso');
});

it('shows the saved medical information and that a KTP photo is saved, without showing the photo', function () {
    $registration = Registration::factory()->withKtpPhoto()->create(['medical_history' => 'Asma ringan', 'drug_allergies' => 'Penisilin']);

    $this->actingAs($registration->user)
        ->get(route('my-registration.edit'))
        ->assertOk()
        ->assertSee('enctype="multipart/form-data"', false)
        ->assertSeeInOrder(['Full Nama (Nama Pembayar)', 'Foto KTP sudah tersimpan', 'Ganti foto', 'Riwayat Penyakit', 'Asma ringan', 'Alergi Obat', 'Penisilin'])
        ->assertDontSee([$registration->ktp_photo_path, '/identity-photo'], false);
});

it('replaces the KTP photo and removes the old file', function () {
    $registration = Registration::factory()->withKtpPhoto()->create();
    $oldPath = $registration->ktp_photo_path;

    $this->actingAs($registration->user)
        ->put(route('my-registration.update'), registrationPayload(['ktp_photo' => uploadedImage('ktp-baru.png', 'png')]))
        ->assertRedirect(route('my-registration.show'))
        ->assertSessionHas('success');

    $newPath = $registration->fresh()->ktp_photo_path;
    expect($newPath)->not->toBe($oldPath)->toEndWith('.png')
        ->and(Storage::disk('identity_photos')->allFiles())->toBe([$newPath]);
});

it('keeps the saved KTP photo when no new one is chosen', function () {
    $registration = Registration::factory()->withKtpPhoto()->create();
    $path = $registration->ktp_photo_path;

    $this->actingAs($registration->user)
        ->put(route('my-registration.update'), registrationPayload(['ktp_photo' => null, 'medical_history' => 'Hipertensi', 'drug_allergies' => 'Ibuprofen']))
        ->assertRedirect(route('my-registration.show'));

    expect($registration->fresh())
        ->ktp_photo_path->toBe($path)
        ->medical_history->toBe('Hipertensi')
        ->drug_allergies->toBe('Ibuprofen')
        ->and(Storage::disk('identity_photos')->allFiles())->toBe([$path]);
});

it('keeps the saved KTP photo when the new file is refused', function () {
    $registration = Registration::factory()->withKtpPhoto()->create();
    $path = $registration->ktp_photo_path;

    $this->actingAs($registration->user)
        ->put(route('my-registration.update'), registrationPayload(['ktp_photo' => uploadedFile('ktp.jpg', 'This is not a photo.')]))
        ->assertSessionHasErrors(['ktp_photo' => 'Foto KTP harus berupa gambar JPG, JPEG, atau PNG.']);

    expect($registration->fresh()->ktp_photo_path)->toBe($path)
        ->and(Storage::disk('identity_photos')->allFiles())->toBe([$path]);
});

it('removes the new photo and keeps the old one when saving the change fails', function () {
    $registration = Registration::factory()->withKtpPhoto()->create();
    $path = $registration->ktp_photo_path;
    Registration::updating(fn () => throw new RuntimeException('Database unavailable'));
    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($registration->user)->put(route('my-registration.update'), registrationPayload()))
        ->toThrow(RuntimeException::class, 'Database unavailable');

    expect(Storage::disk('identity_photos')->allFiles())->toBe([$path]);
});

it('lets a Family Bundle replace its Kartu Keluarga photo without asking for a KTP', function () {
    $registration = Registration::factory()->withFamilyCardPhoto()->create();
    $oldPath = $registration->family_card_photo_path;

    $this->actingAs($registration->user)
        ->get(route('my-registration.edit'))
        ->assertOk()
        ->assertSee(['Foto Kartu Keluarga (KK)', 'Foto Kartu Keluarga sudah tersimpan', 'name="family_card_photo"'], false)
        ->assertDontSee(['Foto KTP', 'name="ktp_photo"'], false);

    $payload = Arr::except(registrationPayload(), 'ktp_photo');

    $this->put(route('my-registration.update'), [...$payload, 'family_card_photo' => uploadedImage('kk-baru.png', 'png')])
        ->assertRedirect(route('my-registration.show'))
        ->assertSessionHas('success');

    $newPath = $registration->fresh()->family_card_photo_path;
    expect($newPath)->not->toBe($oldPath)->toStartWith('kk/')
        ->and($registration->fresh()->ktp_photo_path)->toBeNull()
        ->and(Storage::disk('identity_photos')->allFiles())->toBe([$newPath]);
});

it('asks a registration made before the new fields for a KTP photo and medical information', function () {
    $registration = Registration::factory()->beforeKtpPhoto()->create();

    $this->actingAs($registration->user)
        ->get(route('my-registration.edit'))
        ->assertOk()
        ->assertSee('Pilih foto KTP')
        ->assertDontSee('Foto KTP sudah tersimpan');

    $this->put(route('my-registration.update'), Arr::except(registrationPayload(), ['ktp_photo', 'medical_history', 'drug_allergies']))
        ->assertSessionHasErrors([
            'ktp_photo' => 'Foto KTP wajib diunggah.',
            'medical_history' => 'Riwayat Penyakit wajib diisi. Tulis “Tidak Ada” jika tidak memiliki riwayat penyakit.',
            'drug_allergies' => 'Alergi Obat wajib diisi. Tulis “Tidak Ada” jika tidak memiliki alergi obat.',
        ]);

    expect($registration->fresh())
        ->ktp_photo_path->toBeNull()
        ->identity_number->toBe($registration->identity_number);
});

it('sends participants without a registration back to My Registration when editing', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('my-registration.edit'))
        ->assertRedirect(route('my-registration.show'));
});
