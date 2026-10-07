<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;

/** One line of the homepage announcement ticker (FR-HOME-003). */
class TickerItem extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
