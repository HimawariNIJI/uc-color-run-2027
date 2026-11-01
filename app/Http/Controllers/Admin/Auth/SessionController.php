<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // The intended URL is shared with the participant sign-in, so only follow admin pages.
        $intended = (string) $request->session()->pull('url.intended');

        return redirect()->to(Str::startsWith($intended, route('admin.dashboard')) ? $intended : route('admin.dashboard'));
    }

    /**
     * Sign the admin out without ending a participant session in the same browser.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->regenerateToken();

        return to_route('admin.login')->with('success', 'Kamu telah keluar dari Admin Console.');
    }
}
