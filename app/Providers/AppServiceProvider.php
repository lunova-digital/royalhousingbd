<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Account\Support\Sms\LogSmsSender;
use Modules\Account\Support\Sms\SmsSender;
use Modules\Content\Translation\TextOverrideLoader;
use Modules\Site\Site;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Mobile-only password resets need a text-message gateway: bind your own SmsSender here (see Modules/Account/Support/Sms).
        $this->app->bind(SmsSender::class, fn () => match (config('services.sms.driver')) {
            default => new LogSmsSender,
        });
        // Every public string can be rewritten from the admin (Site → Site text): the
        // Content module's loader lays those rows over the lang files as they load.
        $this->app->extend('translation.loader', fn (Loader $loader) => new TextOverrideLoader($loader));
    }

    public function boot(): void
    {
        // NFR-003: basic rate limiting on the admin login.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));
        // Public accounts: a broad per-IP ceiling here; AuthController also throttles per identifier.
        RateLimiter::for('member-login', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));
        RateLimiter::for('member-register', fn (Request $request) => Limit::perHour(15)->by($request->ip()));
        RateLimiter::for('member-reset', fn (Request $request) => [Limit::perMinute(5)->by($request->ip()), Limit::perHour(15)->by(mb_strtolower((string) $request->input('identifier', $request->input('phone', $request->input('email', '')))).'|reset')]);

        // Error pages render inside the public layout without a controller,
        // so the layout fills in the shared chrome when a page did not.
        View::composer('site::layouts.app', function (\Illuminate\View\View $view) {
            if (! array_key_exists('site_name', $view->getData())) {
                $view->with(Site::page(['title' => $view->getData()['title'] ?? '']));
            }
        });
    }
}
