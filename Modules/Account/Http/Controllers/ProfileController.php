<?php

namespace Modules\Account\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Modules\Account\Models\Member;
use Modules\Account\Support\Avatar;
use Modules\Account\Support\Identifier;
use Modules\Site\Site;

/** The member's profile: every field optional, but an email or a mobile number must stay (it is how they log in). */
class ProfileController
{
    public function show(Request $request): View
    {
        return view('account::profile', Site::page(['title' => __('account::profile.title'), 'nav' => ''], ['member' => $request->user('member')]));
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        /** @var Member $member */
        $member = $request->user('member');
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $email = trim((string) ($data['email'] ?? '')) !== '' ? mb_strtolower(trim($data['email'])) : null;
        if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages(['email' => __('account::profile.email_invalid')]);
        }
        $phoneRaw = trim((string) ($data['phone'] ?? ''));
        $phone = $phoneRaw !== '' ? Identifier::phone($phoneRaw) : null;
        if ($phoneRaw !== '' && $phone === null) {
            throw ValidationException::withMessages(['phone' => __('account::profile.phone_invalid')]);
        }
        if ($email === null && $phone === null) {
            throw ValidationException::withMessages(['email' => __('account::profile.keep_one')]);
        }
        if ($email !== null && Member::query()->where('email', $email)->where('id', '!=', $member->id)->exists()) {
            throw ValidationException::withMessages(['email' => __('account::profile.email_taken')]);
        }
        if ($phone !== null && Member::query()->where('phone', $phone)->where('id', '!=', $member->id)->exists()) {
            throw ValidationException::withMessages(['phone' => __('account::profile.phone_taken')]);
        }

        $member->update([
            'name' => trim((string) ($data['name'] ?? '')) ?: null,
            'email' => $email,
            'phone' => $phone,
            'address' => trim((string) ($data['address'] ?? '')) ?: null,
            // a changed email is no longer the verified one
            'email_verified_at' => $email === $member->email ? $member->email_verified_at : null,
        ]);

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('status', __('account::profile.saved'));
    }

    public function avatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'max:8192']], ['avatar.*' => __('account::profile.avatar_invalid')]);
        /** @var Member $member */
        $member = $request->user('member');
        $old = $member->avatar;
        $member->update(['avatar' => Avatar::store($request->file('avatar'), $member)]);
        Avatar::delete($old);

        return response()->json(['ok' => true, 'url' => $member->fresh()->avatarUrl()]);
    }

    public function removeAvatar(Request $request): JsonResponse
    {
        $member = $request->user('member');
        Avatar::delete($member->avatar);
        $member->update(['avatar' => null]);

        return response()->json(['ok' => true]);
    }

    /** Change the password; a Google-only member (no password yet) can just set one. */
    public function password(Request $request): RedirectResponse|JsonResponse
    {
        /** @var Member $member */
        $member = $request->user('member');
        $data = $request->validate([
            'current_password' => [$member->hasPassword() ? 'required' : 'nullable', 'string'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
        ], ['password.min' => __('account::auth.password_min'), 'password.confirmed' => __('account::auth.password_confirmed')]);

        if ($member->hasPassword() && ! Hash::check((string) $data['current_password'], $member->password)) {
            throw ValidationException::withMessages(['current_password' => __('account::profile.current_wrong')]);
        }
        $member->update(['password' => $data['password']]);
        Auth::guard('member')->login($member, true);
        $request->session()->regenerate();

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('status', __('account::profile.password_saved'));
    }
}
