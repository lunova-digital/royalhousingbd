<?php

namespace Modules\Account\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Modules\Account\Models\Member;
use Modules\Account\Models\SavedListing;

/** Continue with Google. An existing account with the same email is linked, never duplicated. */
class SocialController
{
    public static function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirect(Request $request): RedirectResponse
    {
        if (! self::configured()) {
            return redirect()->route('member.login')->withErrors(['identifier' => __('account::auth.google_unavailable')]);
        }
        $to = (string) $request->query('redirect', '/');
        $request->session()->put('google.redirect', str_starts_with($to, '/') && ! str_starts_with($to, '//') ? $to : '/');

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $google = Socialite::driver('google')->user();
        } catch (\Throwable) {
            return redirect()->route('member.login')->withErrors(['identifier' => __('account::auth.google_failed')]);
        }
        $email = $google->getEmail() ? mb_strtolower($google->getEmail()) : null;

        $member = Member::query()->where('google_id', $google->getId())->first()
            ?? ($email ? Member::query()->where('email', $email)->first() : null);

        if ($member) {
            // Link Google to the existing account (same email) and keep what the member already has.
            $member->forceFill(['google_id' => $google->getId(), 'email_verified_at' => $member->email_verified_at ?? ($email ? now() : null)])->save();
        } else {
            $member = Member::query()->create(['name' => $google->getName(), 'email' => $email, 'google_id' => $google->getId(), 'email_verified_at' => $email ? now() : null]);
        }

        Auth::guard('member')->login($member, true);
        $request->session()->regenerate();
        // A save the visitor started before signing in completes now.
        if ($listingId = $request->session()->pull('pending_save')) {
            SavedListing::query()->firstOrCreate(['member_id' => $member->id, 'listing_id' => (int) $listingId]);
        }

        return redirect($request->session()->pull('google.redirect', '/'));
    }
}
