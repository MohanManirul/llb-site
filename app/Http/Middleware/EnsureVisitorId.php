<?php

namespace App\Http\Middleware;

use App\Services\Analytics\VisitorTrackingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureVisitorId
{
    public function __construct(
        private readonly VisitorTrackingService $visitorTrackingService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->visitorTrackingService->isBot($request)) {
            return $response;
        }

        if ($this->visitorTrackingService->visitorId($request) !== null) {
            return $response;
        }

        return $response->withCookie(cookie(
            VisitorTrackingService::COOKIE,
            VisitorTrackingService::generateId(),
            60 * 24 * 365,
        ));
    }
}
