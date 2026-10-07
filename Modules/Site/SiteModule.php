<?php

namespace Modules\Site;

use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesViews;
use Laramod\Contracts\ProvidesViteEntries;

class SiteModule implements Module, ProvidesViews, ProvidesViteEntries
{
    public function name(): string
    {
        return 'site';
    }

    public function path(): string
    {
        return __DIR__;
    }

    public function views(): string
    {
        return 'resources/views';
    }

    public function viteEntries(): array
    {
        return ['resources/js/site.ts'];
    }
}
