<?php

namespace Modules\Site\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Site\Site;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public site's language: `?lang=bn` picks one and is remembered in a
 * plain cookie (excluded from encryption in bootstrap/app.php, so this global
 * middleware can read it before the web group runs). English is the default.
 */
class SetLocale
{
    public const COOKIE = 'lang';

    public function handle(Request $request, Closure $next): Response
    {
        $chosen = Site::locale($request->query(self::COOKIE));
        $locale = $chosen ?? Site::locale($request->cookie(self::COOKIE)) ?? config('app.locale');
        app()->setLocale($locale);

        $response = $next($request);

        if ($chosen !== null) {
            $response->headers->setCookie(cookie()->forever(self::COOKIE, $chosen, httpOnly: false, sameSite: 'lax'));
        }

        return $response;
    }
}
