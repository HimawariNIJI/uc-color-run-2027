<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Passwordless sign-in for local development, so the participant pages can be
 * tested without Google OAuth credentials. Disabled outside the local environment.
 */
class LocalSignInController extends Controller
{
    public static function isEnabled(): bool
    {
        return app()->isLocal();
    }

    public function __invoke(Request $request): RedirectResponse
    {
        abort_unless(self::isEnabled(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::firstOrCreate(['email' => $validated['email']], ['name' => $validated['name']]);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }
}
