<?php

namespace Modules\Home;

use Illuminate\Routing\Router;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;

class HomeModule implements Module, ProvidesRoutes, ProvidesTranslations, ProvidesViews
{
    public function name(): string
    {
        return 'home';
    }

    public function path(): string
    {
        return __DIR__;
    }

    public function routes(Router $router): void
    {
        require __DIR__.'/routes/web.php';
    }

    public function views(): string
    {
        return 'resources/views';
    }

    public function translations(): string
    {
        return 'lang';
    }
}
