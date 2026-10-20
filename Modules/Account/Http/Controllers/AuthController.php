<?php

namespace Modules\Account\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Modules\Account\Models\Member;
use Modules\Account\Support\Identifier;
use Modules\Site\Site;

/**
 * Sign up and log in with an email or a mobile number + password. The same endpoints serve the full pages
 * (/login, /register) and the auth sheet (fetch + JSON). Failures never say whether an account exists.
 */
class AuthController
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if (Auth::guard('member')->check()) {
            return redirect($this->target($request));
        }

        return view('account::login', Site::page(['title' => __('account::auth.login_title'), 'nav' => ''], ['redirect' => $this->target($request), 'mode' => 'login']));
    }

    public function showRegister(Request $request): View|RedirectResponse
    {
        if (Auth::guard('member')->check()) {
            return redirect($this->target($request));
        }

        return view('account::login', Site::page(['title' => __('account::auth.register_title'), 'nav' => ''], ['redirect' => $this->target($request), 'mode' => 'register']));
    }

    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:200'],
            'remember' => ['nullable'],
        ], $this->messages());

        $id = Identifier::parse($data['identifier']) ?? throw ValidationException::withMessages(['identifier' => __('account::auth.invalid_identifier')]);
        $key = 'member-login:'.$id['value'].'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['identifier' => __('account::auth.too_many', ['seconds' => RateLimiter::availableIn($key)])]);
        }

        $column = $id['type'] === 'email' ? 'email' : 'phone';
        if (! Auth::guard('member')->attempt([$column => $id['value'], 'password' => $data['password']], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['identifier' => __('account::auth.failed')]);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return $this->done($request);
    }

    public function register(Request $request): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'min:8', 'max:200'],
            'name' => ['nullable', 'string', 'max:120'],
        ], $this->messages());

        $id = Identifier::parse($data['identifier']) ?? throw ValidationException::withMessages(['identifier' => __('account::auth.invalid_identifier')]);
        $column = $id['type'] === 'email' ? 'email' : 'phone';
        if (Member::query()->where($column, $id['value'])->exists()) {
            throw ValidationException::withMessages(['identifier' => __('account::auth.already_registered')]);
        }

        $member = Member::query()->create([
            'name' => trim((string) ($data['name'] ?? '')) ?: null,
            $column => $id['value'],
            'password' => $data['password'],
        ]);
        Auth::guard('member')->login($member, true);
        $request->session()->regenerate();

        return $this->done($request);
    }

    /** Where to go after signing in: a same-site path from `redirect`, else the home page. */
    private function target(Request $request): string
    {
        $to = (string) ($request->input('redirect') ?: $request->query('redirect', ''));

        return str_starts_with($to, '/') && ! str_starts_with($to, '//') ? $to : '/';
    }

    private function done(Request $request): JsonResponse|RedirectResponse
    {
        $to = $this->target($request);

        // The session id changed, so the page's CSRF token is stale: hand over the new one.
        return $request->expectsJson() ? response()->json(['ok' => true, 'redirect' => $to, 'csrf' => csrf_token()]) : redirect($to);
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'identifier.required' => __('account::auth.identifier_required'),
            'password.required' => __('account::auth.password_required'),
            'password.min' => __('account::auth.password_min'),
            '*.max' => __('account::auth.too_long'),
        ];
    }
}
