<?php

namespace Modules\Content;

use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesCommands;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Content\Console\TranslationGapsCommand;

class ContentModule implements Module, ProvidesApiRoutes, ProvidesCommands, ProvidesDashboardScripts, ProvidesMigrations, ProvidesViteEntries
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

    public function commands(): array
    {
        return [TranslationGapsCommand::class];
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
