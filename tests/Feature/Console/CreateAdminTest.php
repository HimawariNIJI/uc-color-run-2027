<?php

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

it('creates an admin account from options', function () {
    $this->artisan('admin:create', ['--name' => 'Panitia Racepack', '--username' => 'Panitia', '--password' => 'rahasia-123'])
        ->assertSuccessful();

    $admin = Admin::sole();
    expect($admin)
        ->name->toBe('Panitia Racepack')
        ->username->toBe('panitia')
        ->and(Hash::check('rahasia-123', $admin->password))->toBeTrue();
});

it('asks for the account details when options are omitted', function () {
    $this->artisan('admin:create')
        ->expectsQuestion('Name', 'Panitia Racepack')
        ->expectsQuestion('Username', 'panitia')
        ->expectsQuestion('Password', 'rahasia-123')
        ->assertSuccessful();

    expect(Admin::sole()->username)->toBe('panitia');
});

it('refuses a username that is taken or a short password', function (array $options) {
    Admin::factory()->create(['username' => 'panitia']);

    $this->artisan('admin:create', ['--name' => 'Panitia', ...$options])->assertFailed();

    expect(Admin::count())->toBe(1);
})->with([
    'taken username' => [['--username' => 'panitia', '--password' => 'rahasia-123']],
    'short password' => [['--username' => 'panitia2', '--password' => 'pendek']],
    'username with spaces' => [['--username' => 'panitia dua', '--password' => 'rahasia-123']],
]);
