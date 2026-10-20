<?php

namespace Modules\Content\Services;

use Illuminate\Support\Facades\Schema;
use Modules\Content\Models\Translation;

/**
 * Looks up the visitor's language for admin-entered content. All rows of the current locale load in
 * one query per request (the table holds a few hundred short strings), so reading a translated
 * attribute never costs a query. English is the source language and is never looked up.
 */
class Translations
{
    /** @var array<string, array<string, string>> locale => "group|key|field" => text */
    private static array $loaded = [];

    private static ?bool $tableExists = null;

    public static function active(): bool
    {
        return app()->getLocale() !== 'en' && ! request()->is('api/*');
    }

    /** The translated text, or null when there is none (or the site is in English). */
    public static function get(string $group, string $key, string $field = 'value'): ?string
    {
        if (! self::active()) {
            return null;
        }
        $value = self::rows(app()->getLocale())["{$group}|{$key}|{$field}"] ?? null;

        return $value === null || trim($value) === '' ? null : $value;
    }

    /** A free-standing English string (a stat label, a role, a facility) in the visitor's language. */
    public static function tr(?string $english): string
    {
        $english = (string) $english;

        return $english === '' ? '' : (self::get('string', self::stringKey($english)) ?? $english);
    }

    public static function stringKey(string $english): string
    {
        return mb_strlen($english) <= 150 ? $english : 'h:'.md5($english);
    }

    /** Forget what was loaded: after the admin saves, and between tests. */
    public static function flush(): void
    {
        self::$loaded = [];
        self::$tableExists = null;
    }

    /** @return array<string, string> */
    private static function rows(string $locale): array
    {
        self::$tableExists ??= Schema::hasTable('translations');
        if (! self::$tableExists) {
            return [];
        }

        return self::$loaded[$locale] ??= Translation::query()->where('locale', $locale)
            ->get(['group', 'key', 'field', 'value'])
            ->mapWithKeys(fn (Translation $t) => ["{$t->group}|{$t->key}|{$t->field}" => $t->value])->all();
    }
}
