<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // The public site's language cookie is read by a global middleware
        // (Modules\Site\Http\Middleware\SetLocale), before the web group would
        // decrypt it, so it travels in plain text.
        $middleware->encryptCookies(except: ['lang']);

        // Behind Cloudflare / a TLS-terminating proxy: without this Laravel sees every visitor as the proxy's
        // IP (so the throttle:60,1 on the slot feed and throttle:12,1 on bookings are shared by ALL visitors
        // and start returning 429) and sees http:// (asset, canonical and ?lang= URLs go mixed-content).
        // TRUSTED_PROXIES: '*' (default) or a comma-separated list of proxy IPs/CIDRs.
        $proxies = trim((string) env('TRUSTED_PROXIES', '*'));
        $middleware->trustProxies(at: $proxies === '*' || $proxies === '' ? '*' : array_map('trim', explode(',', $proxies)));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
