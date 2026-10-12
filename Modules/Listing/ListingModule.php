<?php

namespace Modules\Listing;

use Illuminate\Routing\Router;
use Laramod\Admin\Contracts\ProvidesDashboardScripts;
use Laramod\Contracts\Module;
use Laramod\Contracts\Ordered;
use Laramod\Contracts\ProvidesApiRoutes;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesSeeders;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;
use Laramod\Contracts\ProvidesViteEntries;
use Modules\Listing\Database\Seeders\FeatureSeeder;
use Modules\Listing\Database\Seeders\ListingTypeSeeder;
use Modules\Listing\Database\Seeders\LocationSeeder;

class ListingModule implements Module, Ordered, ProvidesApiRoutes, ProvidesDashboardScripts, ProvidesMigrations, ProvidesRoutes, ProvidesSeeders, ProvidesTranslations, ProvidesViews, ProvidesViteEntries
{
    public function name(): string
    {
        return 'listing';
    }

    public function path(): string
    {
        return __DIR__;
    }

    /** Wired last: the public /{type} routes are fallbacks that must come after every static page and the admin. */
    public function order(): int
    {
        return -1;
    }

    public function routes(Router $router): void
    {
        require __DIR__.'/routes/web.php';
    }

    public function apiRoutes(Router $router): void
    {
        require __DIR__.'/routes/api.php';
    }

    public function migrations(): array
    {
        return ['Database/Migrations'];
    }

    public function seeders(): array
    {
        return [ListingTypeSeeder::class, FeatureSeeder::class, LocationSeeder::class];
    }

    public function views(): string
    {
        return 'resources/views';
    }

    public function translations(): string
    {
        return 'lang';
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
