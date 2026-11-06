<?php

use App\Models\User;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function () {
    config([
        'services.google.client_id' => 'test-client-id',
        'services.google.client_secret' => 'test-client-secret',
    ]);
});

it('redirects guests to Google', function () {
    Socialite::fake('google');

    $this->get(route('auth.google'))
        ->assertRedirect('https://socialite.fake/google/authorize');
});

it('returns to the sign in page when Google is not configured', function () {
    config(['services.google.client_id' => null]);

    $this->get(route('auth.google'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Login Google belum dikonfigurasi. Silakan hubungi panitia.');
});

it('creates and signs in a participant from the Google callback', function () {
    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Rina Wijaya',
        'email' => 'rina@example.com',
        'avatar' => 'https://example.com/rina.png',
    ]));

    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

    $user = User::sole();
    expect($user)
        ->google_id->toBe('google-123')
        ->name->toBe('Rina Wijaya')
        ->email->toBe('rina@example.com')
        ->avatar->toBe('https://example.com/rina.png')
        ->email_verified_at->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('links a Google account to an existing participant with the same email', function () {
    $existing = User::factory()->create(['name' => 'Rina W.', 'email' => 'rina@example.com']);

    Socialite::fake('google', SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Rina Wijaya',
        'email' => 'rina@example.com',
    ]));

    $this->get(route('auth.google.callback'));

    expect(User::count())->toBe(1)
        ->and($existing->fresh())
        ->google_id->toBe('google-123')
        ->name->toBe('Rina W.');
    $this->assertAuthenticatedAs($existing);
});

it('sends the participant back to the page they wanted after signing in', function () {
    Socialite::fake('google', SocialiteUser::fake(['id' => 'google-123', 'name' => 'Rina', 'email' => 'rina@example.com']));

    $this->get(route('my-registration.show'))->assertRedirect(route('login'));

    $this->get(route('auth.google.callback'))->assertRedirect(route('my-registration.show'));
});

it('returns to the sign in page when Google sign in fails', function () {
    Socialite::fake('google', fn () => throw new RuntimeException('access_denied'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Login dengan Google gagal atau dibatalkan. Silakan coba lagi.');

    $this->assertGuest();
});
