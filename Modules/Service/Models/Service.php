<?php

namespace Modules\Service\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

/** One of the other services (car, solar, lift), with an English and a Bangla version of its text. */
class Service extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['benefits' => 'array', 'benefits_bn' => 'array', 'gallery' => 'array', 'active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function url(): string
    {
        return "/services/{$this->slug}";
    }

    /** A text field in the visitor's language; Bangla falls back to English when it was left empty. */
    public function text(string $field): string
    {
        $bn = app()->getLocale() === 'bn' ? trim((string) $this->getAttribute($field.'_bn')) : '';

        return $bn !== '' ? $bn : trim((string) $this->getAttribute($field));
    }

    /** @return list<string> */
    public function benefitList(): array
    {
        $bn = app()->getLocale() === 'bn' ? array_values(array_filter((array) $this->benefits_bn, fn ($b) => trim((string) $b) !== '')) : [];

        return array_values(array_filter(array_map('trim', array_map('strval', $bn ?: (array) $this->benefits))));
    }

    private static ?Collection $footer = null;

    /** The active services for the footer, read once per request. */
    public static function footer(): Collection
    {
        if (self::$footer === null || app()->runningUnitTests()) {
            self::$footer = Schema::hasTable('services') ? static::query()->active()->ordered()->get() : collect();
        }

        return self::$footer;
    }
}
