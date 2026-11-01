<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

it('signs a participant in without Google in the local environment', function () {
    $this->app['env'] = 'local';
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->post(route('login.local'), ['name' => 'Peserta Demo', 'email' => 'demo@example.com'])
        ->assertRedirect(route('home'));

    $this->assertAuthenticatedAs(User::where('email', 'demo@example.com')->sole());
});

it('returns 404 outside the local environment', function () {
    $this->post(route('login.local'), ['name' => 'Peserta Demo', 'email' => 'demo@example.com'])
        ->assertNotFound();

    $this->assertGuest();
    expect(User::count())->toBe(0);
});

it('only offers the local sign in form in the local environment', function () {
    $this->get(route('login'))->assertDontSee('Local development only');

    $this->app['env'] = 'local';

    $this->get(route('login'))->assertSee('Local development only');
});
