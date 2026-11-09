<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;
use Throwable;

/**
 * Participants sign in with Google only (OAuth 2.0 through Socialite, with state validation).
 * New accounts are created on the first sign-in; there are no passwords.
 */
class GoogleAuthController extends Controller
{
    public static function isConfigured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    /**
     * Always show Google's account chooser, so participants can pick their Universitas Ciputra
     * account instead of being signed in with whichever account the browser used last.
     * An optional "return_to" path (for example /contact) brings them back to that page afterwards.
     */
    public function redirect(Request $request): SymfonyRedirectResponse
    {
        if (! self::isConfigured()) {
            return $this->failed('Login Google belum dikonfigurasi. Silakan hubungi panitia.');
        }

        if ($returnPath = self::safeReturnPath($request->query('return_to'))) {
            redirect()->setIntendedUrl(url($returnPath));
        }

        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (! self::isConfigured()) {
            return $this->failed('Login Google belum dikonfigurasi. Silakan hubungi panitia.');
        }

        try {
            /** @var AbstractUser $googleUser */
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return $this->failed('Login dengan Google gagal atau dibatalkan. Silakan coba lagi.');
        }

        $email = Str::lower(trim((string) $googleUser->getEmail()));

        if ($email === '' || ! $this->hasVerifiedEmail($googleUser)) {
            return $this->failed('Email akun Google kamu belum terverifikasi. Verifikasi email tersebut di akun Google, lalu coba lagi.');
        }

        $user = $this->accountFor((string) $googleUser->getId(), $email);

        if ($user === null) {
            return $this->failed('Email ini sudah terhubung dengan akun Google lain. Silakan hubungi panitia.');
        }

        $user->fill([
            'name' => $user->name ?? $googleUser->getName() ?? $email,
            'email' => $email,
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
        ]);

        if ($user->email_verified_at === null || $user->isDirty('email')) {
            $user->email_verified_at = now();
        }

        $user->save();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * Google confirms that the person owns the address in the "email_verified" claim.
     */
    private function hasVerifiedEmail(AbstractUser $googleUser): bool
    {
        return filter_var($googleUser->getRaw()['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * The participant account for this Google account: matched by Google ID, or by email when that
     * account has no Google account linked yet. Returns null when the email already belongs to a
     * different Google account, so one Google account can never take over another's registration.
     */
    private function accountFor(string $googleId, string $email): ?User
    {
        $byGoogleId = User::firstWhere('google_id', $googleId);
        $byEmail = User::firstWhere('email', $email);

        if ($byEmail !== null && $byEmail->google_id !== null && $byEmail->google_id !== $googleId) {
            return null;
        }

        if ($byGoogleId !== null && $byEmail !== null && ! $byGoogleId->is($byEmail)) {
            return null;
        }

        return $byGoogleId ?? $byEmail ?? new User;
    }

    /**
     * Only a path on this site is accepted, never a full URL, "//host" or "/\host", so the
     * parameter cannot be used to send people to another website after signing in.
     */
    private static function safeReturnPath(mixed $path): ?string
    {
        return is_string($path) && preg_match('~^/(?![/\\\\])[^\x00-\x20\x7F\\\\]*$~', $path) === 1 ? $path : null;
    }

    private function failed(string $message): RedirectResponse
    {
        return to_route('login')->with('error', $message);
    }
}
