<?php

namespace Modules\Listing\Support;

/**
 * Prices in Taka for the budget filter. A listing's displayed price stays free
 * text ("৳ 35 Lakh / katha"); `price_amount` is the number the filter compares.
 */
final class Price
{
    public const LAKH = 100_000;

    public const CRORE = 10_000_000;

    /** The top of the budget range (100 crore). */
    public const MAX = 100 * self::CRORE;

    /** The options of the min/max selects, in Taka. */
    public const STEPS = [
        5 * self::LAKH, 10 * self::LAKH, 15 * self::LAKH, 20 * self::LAKH, 25 * self::LAKH, 30 * self::LAKH, 40 * self::LAKH, 50 * self::LAKH, 60 * self::LAKH, 75 * self::LAKH,
        self::CRORE, 125 * self::LAKH, 150 * self::LAKH, 2 * self::CRORE, 250 * self::LAKH, 3 * self::CRORE, 4 * self::CRORE, 5 * self::CRORE, 750 * self::LAKH,
        10 * self::CRORE, 15 * self::CRORE, 20 * self::CRORE, 30 * self::CRORE, 50 * self::CRORE, 75 * self::CRORE, self::MAX,
    ];

    private const UNITS = [
        'crore' => self::CRORE, 'crores' => self::CRORE, 'cr' => self::CRORE, 'koti' => self::CRORE, 'কোটি' => self::CRORE,
        'lakh' => self::LAKH, 'lakhs' => self::LAKH, 'lac' => self::LAKH, 'lacs' => self::LAKH, 'lk' => self::LAKH, 'লাখ' => self::LAKH, 'লক্ষ' => self::LAKH,
        'million' => 1_000_000, 'm' => 1_000_000, 'thousand' => 1_000, 'k' => 1_000, 'হাজার' => 1_000,
    ];

    /**
     * The amount a displayed price means, or null when it is not a plain amount.
     * "৳ 4.5 Crore" → 45,000,000; "35 Lakh / katha" → 3,500,000 (the per-unit price);
     * "1,20,00,000" → 12,000,000; bare numbers below 10,000 are not prices.
     */
    public static function parse(?string $text): ?int
    {
        $text = mb_strtolower(trim((string) $text));
        if ($text === '') {
            return null;
        }
        $text = strtr($text, ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9']);
        $text = str_replace(',', '', $text);
        if (! preg_match('/(\d+(?:\.\d+)?)\s*([a-z\x{980}-\x{9ff}]+)?/u', $text, $m)) {
            return null;
        }
        $number = (float) $m[1];
        $unit = $m[2] ?? '';
        if ($unit !== '' && isset(self::UNITS[$unit])) {
            $amount = (int) round($number * self::UNITS[$unit]);
        } elseif ($unit !== '' && ! in_array($unit, ['tk', 'taka', 'bdt', 'টাকা'], true)) {
            return null; // "3 katha", "5th floor": a size, not a price
        } else {
            $amount = (int) round($number);
            if ($amount < 10_000) {
                return null;
            }
        }

        return $amount > 0 ? $amount : null;
    }

    /** "৳ 4.5 Crore", "৳ 35 Lakh", "৳ 85,000", with the unit words translated. */
    public static function format(int $amount): string
    {
        if ($amount >= self::CRORE) {
            return '৳ '.self::number($amount / self::CRORE).' '.__('listing::index.crore');
        }
        if ($amount >= self::LAKH) {
            return '৳ '.self::number($amount / self::LAKH).' '.__('listing::index.lakh');
        }

        return '৳ '.number_format($amount);
    }

    /** A budget bound from the query string: a positive integer, capped at MAX; 0 means unset. */
    public static function bound(mixed $value): int
    {
        $value = is_numeric($value) ? (int) $value : 0;

        return max(0, min(self::MAX, $value));
    }

    private static function number(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }
}
