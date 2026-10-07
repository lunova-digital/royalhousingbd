<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Site\Site;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // NFR-003: basic rate limiting on the admin login.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Error pages render inside the public layout without a controller,
        // so the layout fills in the shared chrome when a page did not.
        View::composer('site::layouts.app', function (\Illuminate\View\View $view) {
            if (! array_key_exists('site_name', $view->getData())) {
                $view->with(Site::page(['title' => $view->getData()['title'] ?? '']));
            }
        });
    }
}
