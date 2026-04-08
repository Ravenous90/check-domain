<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetApiLocale
{
    private const LOCALES = ['uk', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('X-Locale');
        if ($locale && in_array($locale, self::LOCALES, true)) {
            app()->setLocale($locale);
        } else {
            $accept = strtolower($request->header('Accept-Language', 'en'));
            if (str_starts_with($accept, 'uk') || str_contains($accept, 'uk-')) {
                app()->setLocale('uk');
            } else {
                app()->setLocale('en');
            }
        }

        return $next($request);
    }
}
