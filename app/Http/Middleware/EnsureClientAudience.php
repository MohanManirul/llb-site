<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientAudience
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user() instanceof Client,
            403,
            'This endpoint is for client portal accounts.',
        );

        return $next($request);
    }
}
