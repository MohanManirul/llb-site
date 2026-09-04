<?php

use App\Facades\ApiResponse;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        commands: __DIR__.'/../routes/console.php',
        using: function (): void {
            // The framework `health:` option is ignored once a custom `using`
            // closure is provided, so register the probe explicitly.
            Route::get('/up', fn () => response('OK'));

            Route::middleware('api')
                ->prefix('v1')
                ->name('v1.')
                ->group(base_path('routes/admin-v1.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // The staff side of the app: same `web` group as routes/web.php,
            // mounted under /admin.
            Route::middleware('web')
                ->prefix('admin')
                ->group(base_path('routes/web-admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Containerized deploys sit behind a reverse proxy (Coolify/Traefik);
        // trust it so Laravel honors X-Forwarded-* and generates https URLs.
        $middleware->trustProxies(at: '*');

        // Let same-domain browser requests to the versioned API authenticate
        // with the existing session cookie instead of a token. Requests from
        // other origins (the mobile app) are untouched and still use Bearer
        // tokens. This only affects the `api` group — the `web`/Inertia stack
        // below is unchanged. এর মানে auth:sanctum একই ডোমেইনের ব্রাউজার রিকোয়েস্টে session cookie দিয়েও পাস করবে, আর মোবাইল/এক্সটার্নাল ক্লায়েন্টে Bearer token দিয়ে। তাই একই middleware দুই ধরনের ক্লায়েন্ট সামলাচ্ছে — এটাই ডিজাইন, ভাঙার দরকার নেই।
        $middleware->statefulApi();

        // Spatie route middleware: `role:super-admin`, `permission:users.view`
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);

        // Run Inertia's middleware on every web request so each page
        // can share data (like the logged-in user) with React.
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Where to send guests hitting an `auth` route, and where to send
        // already-authenticated users hitting a `guest` route.
        $middleware->redirectGuestsTo('/admin/login');
        $middleware->redirectUsersTo('/admin/dashboard');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('v1/*'),
        );

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('v1/*')) {
                return ApiResponse::respondNotFound('Record not found.');
            }
        });

        $exceptions->render(function (QueryException $e, Request $request) {
            if ($e->getCode() === '23503' && $request->is('v1/*')) {
                return ApiResponse::respondError(
                    'This record is linked to other data and cannot be modified or deleted.',
                    409,
                    $e
                );
            }
        });

        // Render friendly Inertia pages for missing routes (404) and pages
        // the permission middleware refused (403).
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            if (! $request->is('v1/*') && $response->getStatusCode() === 404) {
                return Inertia::render('admin/errors/not-found/page')
                    ->toResponse($request)
                    ->setStatusCode(404);
            }

            if (! $request->is('v1/*') && $response->getStatusCode() === 403) {
                return Inertia::render('admin/errors/forbidden/page')
                    ->toResponse($request)
                    ->setStatusCode(403);
            }

            return $response;
        });
    })->create();
