<?php

namespace Modules\Auth\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * The single admin login (FR-ADM-001), session based. The admin (laramod-admin)
 * has no sign in of its own: it sends guests to the "login" route and posts
 * its "Sign out" to the "logout" route.
 */
class AuthController
{
    public function show(): View|RedirectResponse
    {
        return Auth::check() ? redirect()->route('admin') : view('auth::login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, remember: true)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('admin'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function password(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'min:8'],
        ]);
        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'The current password is wrong.']);
        }
        $request->user()->update(['password' => $data['password']]);

        return response()->json(['message' => 'Password updated.']);
    }
}
