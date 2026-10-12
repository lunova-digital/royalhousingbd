<?php

namespace Modules\Listing\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** An amenity a listing can have (lift, parking, generator…); visitors filter by them. */
class Feature extends Model
{
    protected $guarded = [];

    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('name');
    }
}
