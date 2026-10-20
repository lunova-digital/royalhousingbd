<?php

namespace Modules\Account;

use Illuminate\Routing\Router;
use Laramod\Contracts\Module;
use Laramod\Contracts\ProvidesMigrations;
use Laramod\Contracts\ProvidesRoutes;
use Laramod\Contracts\ProvidesTranslations;
use Laramod\Contracts\ProvidesViews;
use Laramod\Contracts\ProvidesViteEntries;

/**
 * Public accounts: sign up / log in with email or mobile + password or Google, password reset, a profile, and saved
 * properties. Members are separate from the admin (own table, guard and password broker).
 */
class AccountModule implements Module, ProvidesMigrations, ProvidesRoutes, ProvidesTranslations, ProvidesViews, ProvidesViteEntries
{
    public function name(): string
    {
        return 'account';
    }

    public function path(): string
    {
        return __DIR__;
    }

    public function routes(Router $router): void
    {
        require __DIR__.'/routes/web.php';
    }

    public function migrations(): array
    {
        return ['Database/Migrations'];
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
        return ['resources/js/account.ts'];
    }
}
