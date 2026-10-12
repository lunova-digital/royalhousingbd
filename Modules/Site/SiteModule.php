<?php

namespace Modules\Site;

use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesGlobalMiddlewares;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Site\Http\Middleware\SetLocale;

class SiteModule implements Module, ProvidesGlobalMiddlewares, ProvidesTranslations, ProvidesViews, ProvidesViteEntries
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

    /** The public chrome's Bangla copy (lang/bn.json); English is the source string. */
    public function translations(): string
    {
        return 'lang';
    }

    public function globalMiddlewares(): array
    {
        return [SetLocale::class];
    }
}
