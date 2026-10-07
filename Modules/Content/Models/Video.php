<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Site\Support\YouTube;

/** One embed of the homepage YouTube showcase (FR-HOME-004). */
class Video extends Model
{
    protected $guarded = [];

    public function youtubeId(): ?string
    {
        return YouTube::id($this->url);
    }
}
