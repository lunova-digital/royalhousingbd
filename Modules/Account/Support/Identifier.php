<?php

namespace Modules\Account\Support;

/**
 * The single "Email or mobile number" field: tells which one was typed and normalises it.
 * Mobile numbers are Bangladeshi: 01XXXXXXXXX, 8801XXXXXXXXX and +8801XXXXXXXXX (spaces, dashes and Bangla digits tolerated)
 * all become +8801XXXXXXXXX.
 */
final class Identifier
{
    private const BN_DIGITS = ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9'];

    /** @return array{type: 'email'|'phone', value: string}|null null when it is neither a plausible email nor a BD mobile number */
    public static function parse(?string $raw): ?array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }
        if (str_contains($raw, '@')) {
            $email = mb_strtolower($raw);

            return filter_var($email, FILTER_VALIDATE_EMAIL) ? ['type' => 'email', 'value' => $email] : null;
        }
        $phone = self::phone($raw);

        return $phone ? ['type' => 'phone', 'value' => $phone] : null;
    }

    /** +8801XXXXXXXXX, or null when it is not a Bangladeshi mobile number. */
    public static function phone(?string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', strtr(trim((string) $raw), self::BN_DIGITS)) ?? '';
        if (preg_match('/^(?:880)?(1[3-9]\d{8})$/', $digits, $m)) {
            return '+880'.$m[1];
        }
        if (preg_match('/^0(1[3-9]\d{8})$/', $digits, $m)) {
            return '+880'.$m[1];
        }

        return null;
    }

    /** "+8801712345678" → "01712 345678" style is not needed; this is the local form people expect to read. */
    public static function local(?string $phone): string
    {
        return $phone ? '0'.substr($phone, 4) : '';
    }
}
