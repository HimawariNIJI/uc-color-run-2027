<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

it('records when a participant signs in', function () {
    $this->freezeSecond();
    $this->app['env'] = 'local';
    $this->withoutMiddleware(PreventRequestForgery::class);
    $user = User::factory()->create(['email' => 'rina@example.com', 'last_login_at' => null]);

    $this->post(route('login.local'), ['name' => 'Rina', 'email' => 'rina@example.com']);

    expect($user->fresh()->last_login_at)->toEqual(now());
});

it('records when an admin signs in', function () {
    $this->freezeSecond();
    $admin = Admin::factory()->create(['username' => 'panitia', 'password' => 'rahasia-123']);

    $this->post(route('admin.login.store'), ['username' => 'panitia', 'password' => 'rahasia-123']);

    expect($admin->fresh()->last_login_at)->toEqual(now());
});
