<?php

namespace Modules\Listing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An area a listing can be in (Uttara, Mirpur, Cox's Bazar…), managed in the admin.
 * Every listing stores the location's name in listings.location as well as the id,
 * so the public filter and search stay plain column reads; a rename updates them.
 */
class Location extends Model
{
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
