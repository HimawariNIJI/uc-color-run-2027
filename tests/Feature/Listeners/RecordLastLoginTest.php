<?php

use App\Models\Admin;
use App\Models\User;
use Laravel\Socialite\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('records when a participant signs in', function () {
    $this->freezeSecond();
    config(['services.google.client_id' => 'test-client-id', 'services.google.client_secret' => 'test-client-secret']);
    $user = User::factory()->create(['google_id' => 'google-123', 'email' => 'rina@example.com', 'last_login_at' => null]);
    Socialite::fake('google', SocialiteUser::fake(['id' => 'google-123', 'email' => 'rina@example.com', 'email_verified' => true]));

    $this->get(route('auth.google.callback'));

    expect($user->fresh()->last_login_at)->toEqual(now());
});

it('records when an admin signs in', function () {
    $this->freezeSecond();
    $admin = Admin::factory()->create(['username' => 'panitia', 'password' => 'rahasia-123']);

    $this->post(route('admin.login.store'), ['username' => 'panitia', 'password' => 'rahasia-123']);

    expect($admin->fresh()->last_login_at)->toEqual(now());
});
