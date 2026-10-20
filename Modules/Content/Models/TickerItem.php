<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Content\Models\Concerns\HasTranslations;

/** One line of the homepage announcement ticker (FR-HOME-003). */
class TickerItem extends Model
{
    use HasTranslations;

    /** @var list<string> */
    public static array $translatable = ['text'];

    public static function translationGroup(): string
    {
        return 'ticker';
    }

    protected $guarded = [];

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }
}
