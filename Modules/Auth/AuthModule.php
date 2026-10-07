<?php

namespace Modules\Auth;

use Illuminate\Routing\Router;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesSeeders;
use Laramod\Contracts\ProvidesViews;
use Modules\Auth\Database\Seeders\AdminUserSeeder;

class AuthModule implements Module, ProvidesApiRoutes, ProvidesRoutes, ProvidesSeeders, ProvidesViews
{
    public function name(): string
    {
        return 'auth';
    }

    public function path(): string
    {
        return __DIR__;
    }

    public function routes(Router $router): void
    {
        require __DIR__.'/routes/web.php';
    }

    public function apiRoutes(Router $router): void
    {
        require __DIR__.'/routes/api.php';
    }

    public function views(): string
    {
        return 'resources/views';
    }

    public function seeders(): array
    {
        return [AdminUserSeeder::class];
    }
}
