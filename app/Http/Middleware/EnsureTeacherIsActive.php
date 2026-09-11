<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user('teacher')?->is_active, 403, 'Account is pending admin approval.');

        return $next($request);
    }
}
