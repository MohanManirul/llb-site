<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetPublicLocale
{
    /**
     * The app locale stays scoped to the public route group: this repo ships
     * no lang/bn directory, so switching it globally would break Laravel's
     * own validation messages on the admin side.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = (string) $request->route('locale');

        if (! in_array($locale, config('llb.locales'), true)) {
            abort(404);
        }

        app()->setLocale($locale);

        $response = $next($request);

        return $response->withCookie(cookie('locale', $locale, 60 * 24 * 365));
    }
}
