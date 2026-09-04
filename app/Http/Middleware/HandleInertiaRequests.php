<?php

namespace App\Http\Middleware;

use App\Services\Auth\ImpersonationService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => fn () => ['user' => $this->authUser($request)],
            'portal' => fn () => ['base' => $request->is('admin', 'admin/*') ? '/admin' : ''],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'impersonation' => fn () => $this->impersonation($request),
        ];
    }

    /**
     * @return array{name: string, since: int}|null
     */
    private function impersonation(Request $request): ?array
    {
        if (! $request->hasSession()) {
            return null;
        }

        if (! app(ImpersonationService::class)->isImpersonating()) {
            return null;
        }

        return [
            'name' => (string) $request->user()?->name,
            'since' => (int) $request->session()->get(ImpersonationService::SESSION_STARTED_AT),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function authUser(Request $request): ?array
    {
        $account = $request->user();

        if (! $account) {
            return null;
        }

        return [
            ...$account->only('id', 'name', 'email'),
            'roles' => $account->effectiveRoleNames()->all(),
            'permissions' => $account->getAllPermissions()->pluck('name')->all(),
        ];
    }
}
