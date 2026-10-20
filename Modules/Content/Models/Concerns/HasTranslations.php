<?php

namespace Modules\Content\Models\Concerns;

use Modules\Content\Services\Translations;
use Modules\Site\Site;

/**
 * Public pages read `$listing->title` and get the Bangla text when the admin filled it in. Only attribute
 * reads are affected (serialisation and the admin API still see the stored English), and only outside /api.
 *
 * A model declares `translationGroup()` and `translatable` (field names); override `translationKey()` when the
 * English text itself, not the id, is the key (locations, shared by name with listings).
 */
trait HasTranslations
{
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        if (is_string($value) && $value !== '' && in_array($key, static::$translatable, true) && Translations::active()) {
            return Translations::get($this->translationGroupFor($key), $this->translationKey($key), $this->translationField($key)) ?? $value;
        }

        // Admin-typed amounts and sizes ("৳ 35 Lakh / katha"): unit words and digits follow the language.
        if (is_string($value) && $value !== '' && property_exists(static::class, 'localizedNumbers') && in_array($key, static::$localizedNumbers, true) && Translations::active()) {
            return Site::num($value);
        }

        return $value;
    }

    public function translationField(string $field): string
    {
        return $field;
    }

    public function translationGroupFor(string $field): string
    {
        return static::translationGroup();
    }

    public function translationKey(string $field): string
    {
        return (string) $this->getKey();
    }
}
