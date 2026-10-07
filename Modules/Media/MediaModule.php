<?php

namespace Modules\Media;

use Illuminate\Routing\Router;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;

class MediaModule implements Module, ProvidesApiRoutes
{
    public function name(): string
    {
        return 'media';
    }

    public function path(): string
    {
        return __DIR__;
    }

    public function apiRoutes(Router $router): void
    {
        require __DIR__.'/routes/api.php';
    }
}
