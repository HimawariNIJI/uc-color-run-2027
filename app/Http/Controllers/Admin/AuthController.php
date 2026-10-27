<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $username = config('admin.username');
        $password = config('admin.password');

        if (
            $username &&
            $password &&
            hash_equals($username, $credentials['username']) &&
            hash_equals($password, $credentials['password'])
        ) {
            $request->session()->regenerate();

            $request->session()->put('is_admin', true);

            return redirect()->intended('/admin/dashboard');
        }

        return back()
            ->withErrors([
                'username' => 'Username or password is incorrect.',
            ])
            ->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}