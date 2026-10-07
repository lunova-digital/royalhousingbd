<?php

namespace Modules\Content;

use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesViteEntries;

class ContentModule implements Module, ProvidesApiRoutes, ProvidesDashboardScripts, ProvidesMigrations, ProvidesViteEntries
{
    public function name(): string
    {
        return 'content';
    }

    public function path(): string
    {
        return __DIR__;
    }

    public function apiRoutes(Router $router): void
    {
        require __DIR__.'/routes/api.php';
    }

    public function migrations(): array
    {
        return ['Database/Migrations'];
    }

    public function viteEntries(): array
    {
        return ['resources/js/dashboard.tsx'];
    }

    public function dashboardScripts(): array
    {
        return ['resources/js/dashboard.tsx'];
    }
}
