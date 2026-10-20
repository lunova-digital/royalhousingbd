<?php

namespace Modules\Listing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Content\Models\Concerns\HasTranslations;

/**
 * An area a listing can be in (Uttara, Mirpur, Cox's Bazar…), managed in the admin.
 * Every listing stores the location's name in listings.location as well as the id,
 * so the public filter and search stay plain column reads; a rename updates them.
 */
class Location extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public static array $translatable = ['name'];

    public static function translationGroup(): string
    {
        return 'location';
    }

    public function translationField(string $field): string
    {
        return 'value';
    }

    public function translationKey(string $field): string
    {
        return (string) $this->getRawOriginal('name');
    }

    protected $guarded = [];

    protected static function booted(): void
    {
        static::updated(function (self $location) {
            if ($location->wasChanged('name')) {
                $location->listings()->update(['location' => $location->name]);
            }
        });
    }

    public function listings(): HasMany
    {
        return $this->hasMany(Listing::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }
}
