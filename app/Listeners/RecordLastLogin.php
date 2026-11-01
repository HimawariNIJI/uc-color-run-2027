<?php

namespace App\Listeners;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Auth\Events\Login;

/**
 * Stamps the last login time of participants and admins, shown in the admin console.
 * Also runs when a session is restored from a "remember me" cookie.
 */
class RecordLastLogin
{
    public function handle(Login $event): void
    {
        if ($event->user instanceof User || $event->user instanceof Admin) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
        }
    }
}
