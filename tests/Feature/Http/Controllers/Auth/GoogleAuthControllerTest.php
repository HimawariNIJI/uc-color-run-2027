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

/**
 * A Google account as Socialite returns it; Google marks confirmed addresses with "email_verified".
 *
 * @param  array<string, mixed>  $attributes
 */
function fakeGoogleUser(array $attributes = []): SocialiteUser
{
    return SocialiteUser::fake([
        'id' => 'google-123',
        'name' => 'Rina Wijaya',
        'email' => 'rina@example.com',
        'email_verified' => true,
        ...$attributes,
    ]);
}

it('redirects guests to Google', function () {
    Socialite::fake('google');

    $this->get(route('auth.google'))
        ->assertRedirect('https://socialite.fake/google/authorize');
});

it('asks Google to show the account chooser and protects the request with a state value', function () {
    $response = $this->get(route('auth.google'));

    $url = $response->headers->get('Location');
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    expect($url)->toStartWith('https://accounts.google.com/o/oauth2/auth')
        ->and($query)
        ->toMatchArray(['prompt' => 'select_account', 'redirect_uri' => url('/auth/google/callback')])
        ->and($query['state'])->toBe(session('state'))->not->toBeEmpty();
});

it('returns to the sign in page when Google is not configured', function () {
    config(['services.google.client_id' => null]);

    $this->get(route('auth.google'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Login Google belum dikonfigurasi. Silakan hubungi panitia.');
});

it('creates and signs in a participant from the Google callback', function () {
    Socialite::fake('google', fakeGoogleUser(['avatar' => 'https://example.com/rina.png']));

    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

    $user = User::sole();
    expect($user)
        ->google_id->toBe('google-123')
        ->name->toBe('Rina Wijaya')
        ->email->toBe('rina@example.com')
        ->avatar->toBe('https://example.com/rina.png')
        ->password->toBeNull()
        ->email_verified_at->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});

it('signs a returning participant into the same account', function () {
    $existing = User::factory()->create(['google_id' => 'google-123', 'name' => 'Rina W.', 'email' => 'rina@example.com', 'avatar' => null]);
    Socialite::fake('google', fakeGoogleUser(['avatar' => 'https://example.com/new.png']));

    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));

    expect(User::count())->toBe(1)
        ->and($existing->fresh())
        ->name->toBe('Rina W.')
        ->avatar->toBe('https://example.com/new.png');
    $this->assertAuthenticatedAs($existing);
});

it('links a Google account to an existing participant with the same email', function () {
    $existing = User::factory()->create(['name' => 'Rina W.', 'email' => 'rina@example.com', 'google_id' => null]);
    Socialite::fake('google', fakeGoogleUser());

    $this->get(route('auth.google.callback'));

    expect(User::count())->toBe(1)
        ->and($existing->fresh())
        ->google_id->toBe('google-123')
        ->name->toBe('Rina W.');
    $this->assertAuthenticatedAs($existing);
});

it('stores the Google email in lower case', function () {
    Socialite::fake('google', fakeGoogleUser(['email' => 'Rina.Wijaya@Student.Ciputra.ac.id']));

    $this->get(route('auth.google.callback'));

    expect(User::sole()->email)->toBe('rina.wijaya@student.ciputra.ac.id');
});

it('rejects a Google account whose email is not verified', function (array $attributes) {
    Socialite::fake('google', fakeGoogleUser($attributes));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Email akun Google kamu belum terverifikasi. Verifikasi email tersebut di akun Google, lalu coba lagi.');

    $this->assertGuest();
    expect(User::count())->toBe(0);
})->with([
    'marked unverified' => [['email_verified' => false]],
    'verification missing' => [['email_verified' => null]],
    'no email' => [['email' => null]],
]);

it('refuses to sign in when the email belongs to a different Google account', function () {
    $owner = User::factory()->create(['google_id' => 'google-999', 'email' => 'rina@example.com']);
    Socialite::fake('google', fakeGoogleUser(['id' => 'google-123']));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Email ini sudah terhubung dengan akun Google lain. Silakan hubungi panitia.');

    $this->assertGuest();
    expect($owner->fresh()->google_id)->toBe('google-999');
});

it('returns to the page given in return_to after signing in, such as Contact Us', function () {
    Socialite::fake('google', fakeGoogleUser());

    $this->get(route('auth.google', ['return_to' => '/contact']))->assertRedirect('https://socialite.fake/google/authorize');

    $this->get(route('auth.google.callback'))->assertRedirect(route('contact'));
    $this->get(route('contact'))->assertSee('value="rina@example.com" readonly', false);
});

it('ignores a return_to that points outside this site', function (string $returnTo) {
    Socialite::fake('google', fakeGoogleUser());

    $this->get(route('auth.google', ['return_to' => $returnTo]));

    $this->get(route('auth.google.callback'))->assertRedirect(route('home'));
})->with([
    'full URL' => 'https://evil.example/phish',
    'protocol-relative URL' => '//evil.example/phish',
    'backslash trick' => '/\\evil.example/phish',
    'header injection' => "/contact\r\nSet-Cookie: a=b",
    'not a path' => 'javascript:alert(1)',
]);

it('sends the participant back to the page they wanted after signing in', function () {
    Socialite::fake('google', fakeGoogleUser());

    $this->get(route('my-registration.show'))->assertRedirect(route('login'));

    $this->get(route('auth.google.callback'))->assertRedirect(route('my-registration.show'));
});

it('starts a new session after signing in', function () {
    Socialite::fake('google', fakeGoogleUser());
    $this->get(route('login'));
    $guestSessionId = session()->getId();

    $this->get(route('auth.google.callback'));

    expect(session()->getId())->not->toBe($guestSessionId);
});

it('returns to the sign in page when Google sign in fails', function () {
    Socialite::fake('google', fn () => throw new RuntimeException('access_denied'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Login dengan Google gagal atau dibatalkan. Silakan coba lagi.');

    $this->assertGuest();
});

it('rejects a callback whose OAuth state does not match the one sent to Google', function () {
    $this->withSession(['state' => 'expected-state'])
        ->get(route('auth.google.callback', ['state' => 'forged-state', 'code' => 'fake-code']))
        ->assertRedirect(route('login'))
        ->assertSessionHas('error', 'Login dengan Google gagal atau dibatalkan. Silakan coba lagi.');

    $this->assertGuest();
    expect(User::count())->toBe(0);
});
