<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

it('shows the admin sign in page', function () {
    $this->get(route('admin.login'))
        ->assertOk()
        ->assertSee(['UC Color Run 2027', 'Administration Portal', 'Welcome back', 'Username', 'Password', 'Sign In', 'Secure Admin Access']);
});

it('signs an admin in with their username and password', function () {
    $admin = Admin::factory()->create(['username' => 'panitia', 'password' => 'rahasia-123']);

    $this->post(route('admin.login.store'), ['username' => ' Panitia ', 'password' => 'rahasia-123'])
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin, 'admin');
});

it('rejects a wrong password', function () {
    Admin::factory()->create(['username' => 'panitia', 'password' => 'rahasia-123']);

    $this->from(route('admin.login'))
        ->post(route('admin.login.store'), ['username' => 'panitia', 'password' => 'salah'])
        ->assertRedirect(route('admin.login'))
        ->assertSessionHasErrors(['username' => 'Username atau password salah.']);

    $this->assertGuest('admin');
});

it('requires a username and password', function () {
    $this->post(route('admin.login.store'))
        ->assertSessionHasErrors([
            'username' => 'Username wajib diisi.',
            'password' => 'Password wajib diisi.',
        ]);
});

it('locks the username out after five failed attempts', function () {
    $admin = Admin::factory()->create(['username' => 'panitia', 'password' => 'rahasia-123']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('admin.login.store'), ['username' => 'panitia', 'password' => 'salah']);
    }

    $this->post(route('admin.login.store'), ['username' => 'panitia', 'password' => 'rahasia-123'])
        ->assertSessionHasErrors('username');

    expect(session('errors')->first('username'))->toStartWith('Terlalu banyak percobaan masuk.');
    $this->assertGuest('admin');
});

it('returns the admin to the admin page they asked for', function () {
    Admin::factory()->create(['username' => 'panitia', 'password' => 'rahasia-123']);

    $this->get(route('admin.users.index'))->assertRedirect(route('admin.login'));

    $this->post(route('admin.login.store'), ['username' => 'panitia', 'password' => 'rahasia-123'])
        ->assertRedirect(route('admin.users.index'));
});

it('does not follow a participant page left over from the participant sign in', function () {
    Admin::factory()->create(['username' => 'panitia', 'password' => 'rahasia-123']);

    $this->withSession(['url.intended' => route('my-bib.show')])
        ->post(route('admin.login.store'), ['username' => 'panitia', 'password' => 'rahasia-123'])
        ->assertRedirect(route('admin.dashboard'));
});

it('sends a signed in admin from the sign in page to the dashboard', function () {
    $this->actingAs(Admin::factory()->create(), 'admin')
        ->get(route('admin.login'))
        ->assertRedirect(route('admin.dashboard'));
});

it('signs the admin out without signing out a participant in the same browser', function () {
    $participant = User::factory()->create();
    $participantKey = Auth::guard('web')->getName();
    $adminKey = Auth::guard('admin')->getName();

    $this->withSession([$participantKey => $participant->id, $adminKey => Admin::factory()->create()->id])
        ->post(route('admin.logout'))
        ->assertRedirect(route('admin.login'))
        ->assertSessionHas('success')
        ->assertSessionMissing($adminKey)
        ->assertSessionHas($participantKey, $participant->id);
});

it('sends guests to the admin sign in page', function (string $routeName, array $parameters) {
    $this->get(route($routeName, $parameters))->assertRedirect(route('admin.login'));
})->with([
    'dashboard' => ['admin.dashboard', []],
    'users' => ['admin.users.index', []],
    'user export' => ['admin.users.export', []],
    'registrations' => ['admin.registrations.index', []],
    'registration export' => ['admin.registrations.export', []],
    'qr scanner' => ['admin.scanner.index', []],
]);

it('keeps participants out of the admin console', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.login'));
});
