<?php

use App\Models\Admin;
use App\Models\Registration;
use App\Models\User;

beforeEach(function () {
    $this->admin = Admin::factory()->create();
});

it('lists accounts with their last login and BIB number, latest sign-in first', function () {
    $this->travelTo('2026-10-21 10:42:00');
    User::factory()->create(['name' => 'Belum Pernah Login']);
    Registration::factory()->paid()->for(User::factory()->create(['name' => 'Rina Wijaya', 'last_login_at' => now()]))->create(['bib_number' => '0520012']);
    User::factory()->create(['name' => 'Budi Santoso', 'last_login_at' => now()->subDay()]);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee(['User Accounts', 'Google accounts that have accessed the event website', 'Export CSV', '3 accounts displayed'])
        ->assertSeeInOrder(['Rina Wijaya', '21 Okt 2026, 10:42', '0520012', 'Budi Santoso', '20 Okt 2026, 10:42', 'Belum Pernah Login']);
});

it('searches accounts by name or email', function () {
    User::factory()->create(['name' => 'Rina Wijaya', 'email' => 'rina@example.com']);
    User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi.rina@example.com']);
    User::factory()->create(['name' => 'Siti Aminah', 'email' => 'siti@example.com']);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.users.index', ['search' => 'rina']))
        ->assertOk()
        ->assertSee(['Rina Wijaya', 'Budi Santoso', '2 accounts displayed'])
        ->assertDontSee('Siti Aminah');
});

it('explains when no account matches the search', function () {
    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.users.index', ['search' => 'tidak-ada']))
        ->assertOk()
        ->assertSee(['0 accounts displayed', 'Tidak ada akun yang cocok.']);
});

it('shows ten accounts per page', function () {
    User::factory()->count(11)->create();

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.users.index', ['page' => 2]))
        ->assertOk()
        ->assertSee('Page 2 of 2')
        ->assertViewHas('users', fn ($users): bool => $users->count() === 1);
});

it('escapes participant names', function () {
    User::factory()->create(['name' => '<script>alert("x")</script>']);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.users.index'))
        ->assertSee('&lt;script&gt;', false)
        ->assertDontSee('<script>alert("x")</script>', false);
});

it('shows the account details with the registration status', function (?string $state, string $status, bool $hasRegistrationLink) {
    $user = $state === null
        ? User::factory()->create(['google_id' => 'google-1'])
        : Registration::factory()->{$state}()->for(User::factory()->create(['google_id' => 'google-1']))->create()->user;

    $response = $this->actingAs($this->admin, 'admin')
        ->get(route('admin.users.show', $user))
        ->assertOk()
        ->assertSee(['User Account Details', 'Account overview', $user->name, $user->email, $status, 'Google Sign-In']);

    $hasRegistrationLink
        ? $response->assertSee(['View Registration Details', route('admin.registrations.show', $user->registration)])
        : $response->assertDontSee('View Registration Details');
})->with([
    'not registered' => [null, 'Not Registered', false],
    'pending payment' => ['expired', 'Pending Payment', true],
    'payment failed' => ['failed', 'Payment Failed', true],
    'registered' => ['paid', 'Registered', true],
]);

it('labels accounts created by the local dev sign-in', function () {
    $user = User::factory()->create(['google_id' => null]);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.users.show', $user))
        ->assertSee('Local Dev Sign-In');
});

it('returns only the modal content to the admin modal', function () {
    $user = User::factory()->create();

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.users.show', $user), ['X-Admin-Modal' => '1'])
        ->assertOk()
        ->assertSee('User Account Details')
        ->assertDontSee('<html', false)
        ->assertDontSee('Admin Console');
});
