<?php

use App\Enums\RegistrationType;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

it('decides which registration types each account may choose', function (string $email, bool $verified, RegistrationType $type, bool $allowed) {
    $user = User::factory()->create(['email' => $email, 'email_verified_at' => $verified ? now() : null]);

    expect(Gate::forUser($user)->allows('create', [Registration::class, $type]))->toBe($allowed);
})->with([
    'UC email, Internal' => ['rina@student.ciputra.ac.id', true, RegistrationType::Internal, true],
    'UC email, External' => ['rina@student.ciputra.ac.id', true, RegistrationType::External, true],
    'UC email, Bundling' => ['rina@student.ciputra.ac.id', true, RegistrationType::Bundling, true],
    'other email, Internal' => ['rina@gmail.com', true, RegistrationType::Internal, false],
    'other email, External' => ['rina@gmail.com', true, RegistrationType::External, true],
    'other email, Bundling' => ['rina@gmail.com', true, RegistrationType::Bundling, true],
    'unverified UC email, Internal' => ['rina@ciputra.ac.id', false, RegistrationType::Internal, false],
    'unverified UC email, External' => ['rina@ciputra.ac.id', false, RegistrationType::External, true],
]);

it('explains why Internal is refused', function () {
    $user = User::factory()->create(['email' => 'rina@gmail.com']);

    expect(Gate::forUser($user)->inspect('create', [Registration::class, RegistrationType::Internal])->message())
        ->toBe('Kategori Internal hanya untuk akun Google dengan email Universitas Ciputra (ciputra.ac.id).');
});
