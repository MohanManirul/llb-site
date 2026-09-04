<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user('student')?->is_active, 403, 'Account is deactivated.');

        return $next($request);
    }
}
