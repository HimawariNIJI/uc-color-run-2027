<?php

use App\Models\Admin;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

it('shows the KTP or Kartu Keluarga photo to a signed-in admin, inline and never cached', function (Registration $registration, string $prefix, string $column) {
    $response = $this->actingAs(Admin::factory()->create(), 'admin')
        ->get(route('admin.registrations.identity-photo', $registration))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg')
        ->assertHeader('X-Content-Type-Options', 'nosniff');

    expect($response->headers->get('Cache-Control'))->toContain('no-store')->toContain('private')
        ->and($response->headers->get('Content-Disposition'))->toBe('inline; filename='.$prefix.'-'.$registration->code.'.jpg')
        ->and($response->streamedContent())->toBe(Storage::disk('identity_photos')->get($registration->{$column}));
})->with([
    'KTP' => [fn () => Registration::factory()->withKtpPhoto()->create(), 'ktp', 'ktp_photo_path'],
    'Kartu Keluarga of a Family Bundle' => [fn () => Registration::factory()->withFamilyCardPhoto()->create(), 'kk', 'family_card_photo_path'],
]);

it('sends guests and participants to the admin sign-in page, including the photo owner', function () {
    $registration = Registration::factory()->withKtpPhoto()->create();
    $url = route('admin.registrations.identity-photo', $registration);

    $this->get($url)->assertRedirect(route('admin.login'));
    $this->actingAs($registration->user)->get($url)->assertRedirect(route('admin.login'));
    $this->actingAs(User::factory()->create())->get($url)->assertRedirect(route('admin.login'));
});

it('returns not found when there is no photo to show', function (Registration $registration) {
    $this->actingAs(Admin::factory()->create(), 'admin')
        ->get(route('admin.registrations.identity-photo', $registration))
        ->assertNotFound();
})->with([
    'registered before KTP photos' => fn () => Registration::factory()->beforeKtpPhoto()->create(),
    'file no longer on the disk' => fn () => Registration::factory()->create(['ktp_photo_path' => 'ktp/missing.jpg']),
    'Family Bundle that only has a KTP path' => fn () => Registration::factory()->withKtpPhoto()->bundle('family')->create(),
    'unknown registration' => fn () => new Registration(['code' => 'UCCR-0000000000']),
]);

it('keeps identity photos in private storage without a public URL', function () {
    expect(config('filesystems.disks.identity_photos'))
        ->toMatchArray(['driver' => 'local', 'root' => storage_path('app/private/identity-photos'), 'visibility' => 'private', 'serve' => false])
        ->not->toHaveKey('url');
});
