<?php

namespace Modules\Account\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Account\Models\Member;
use Modules\Account\Models\PhoneOtp;
use Modules\Account\Support\Identifier;
use Modules\Account\Support\Sms\SmsSender;
use Modules\Site\Site;

/**
 * Password reset. An email account gets a link; a mobile-only account gets a one-time code by SMS (SmsSender, log driver
 * until a gateway is wired in). Every answer is the same whether or not the account exists.
 */
class PasswordController
{
    private const OTP_MINUTES = 10;

    private const OTP_TRIES = 5;

    public function showForgot(): View
    {
        return view('account::forgot', Site::page(['title' => __('account::auth.forgot_title'), 'nav' => ''], ['step' => 'identifier']));
    }

    public function sendReset(Request $request, SmsSender $sms): JsonResponse|RedirectResponse
    {
        $request->validate(['identifier' => ['required', 'string', 'max:190']], ['identifier.required' => __('account::auth.identifier_required')]);
        $id = Identifier::parse($request->input('identifier')) ?? throw ValidationException::withMessages(['identifier' => __('account::auth.invalid_identifier')]);

        if ($id['type'] === 'email') {
            Password::broker('members')->sendResetLink(['email' => $id['value']]); // result deliberately ignored
            $next = null;
        } else {
            if ($member = Member::query()->where('phone', $id['value'])->first()) {
                $code = (string) random_int(100000, 999999);
                PhoneOtp::query()->where('phone', $id['value'])->delete();
                PhoneOtp::query()->create(['phone' => $id['value'], 'code_hash' => Hash::make($code), 'expires_at' => now()->addMinutes(self::OTP_MINUTES)]);
                $sms->send($id['value'], __('account::auth.otp_sms', ['code' => $code, 'minutes' => self::OTP_MINUTES]));
            }
            $next = '/forgot-password/code?phone='.urlencode(Identifier::local($id['value']));
        }

        $message = __('account::auth.reset_sent');

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => $message, 'next' => $next])
            : ($next ? redirect($next)->with('status', $message) : back()->with('status', $message));
    }

    public function showCode(Request $request): View
    {
        return view('account::forgot', Site::page(['title' => __('account::auth.code_title'), 'nav' => ''], ['step' => 'code', 'phone' => (string) $request->query('phone', '')]));
    }

    public function resetWithCode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'code' => ['required', 'string', 'max:10'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
        ], ['password.min' => __('account::auth.password_min'), 'password.confirmed' => __('account::auth.password_confirmed')]);

        $phone = Identifier::phone($data['phone']);
        $otp = $phone ? PhoneOtp::query()->where('phone', $phone)->latest('id')->first() : null;
        $invalid = ValidationException::withMessages(['code' => __('account::auth.code_invalid')]);
        if (! $otp || $otp->expires_at->isPast() || $otp->attempts >= self::OTP_TRIES) {
            throw $invalid;
        }
        $otp->increment('attempts');
        if (! Hash::check(trim($data['code']), $otp->code_hash)) {
            throw $invalid;
        }

        Member::query()->where('phone', $phone)->first()?->update(['password' => $data['password']]);
        PhoneOtp::query()->where('phone', $phone)->delete();

        return redirect()->route('member.login')->with('status', __('account::auth.password_updated'));
    }

    public function showReset(Request $request, string $token): View
    {
        return view('account::forgot', Site::page(['title' => __('account::auth.reset_title'), 'nav' => ''], ['step' => 'reset', 'token' => $token, 'email' => (string) $request->query('email', '')]));
    }

    public function reset(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'max:200', 'confirmed'],
        ], ['password.min' => __('account::auth.password_min'), 'password.confirmed' => __('account::auth.password_confirmed')]);

        $status = Password::broker('members')->reset($data, function (Member $member, string $password) {
            $member->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __('account::auth.reset_invalid')]);
        }

        return redirect()->route('member.login')->with('status', __('account::auth.password_updated'));
    }
}
