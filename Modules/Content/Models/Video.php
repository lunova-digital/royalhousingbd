<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Content\Models\Concerns\HasTranslations;
use Modules\Site\Support\YouTube;

/** One embed of the homepage YouTube showcase (FR-HOME-004). */
class Video extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public static array $translatable = ['title'];

    public static function translationGroup(): string
    {
        return 'video';
    }

    protected $guarded = [];

    public function youtubeId(): ?string
    {
        return YouTube::id($this->url);
    }
}
