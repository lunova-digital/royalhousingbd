<?php

namespace Modules\Listing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingImage extends Model
{
    protected $guarded = [];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }
}
