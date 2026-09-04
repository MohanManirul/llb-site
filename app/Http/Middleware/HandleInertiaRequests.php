<?php

namespace App\Http\Middleware;

use App\Models\Program;
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
            'locale' => fn () => app()->getLocale(),
            'programs' => fn () => $this->publicPrograms($request),
        ];
    }

    /**
     * Shared only on public pages so the program switcher costs no request;
     * the admin side gets an empty list.
     *
     * @return array<int, array<string, mixed>>
     */
    private function publicPrograms(Request $request): array
    {
        if ($request->is('admin', 'admin/*')) {
            return [];
        }

        return Program::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Program $program) => [
                'slug' => $program->slug,
                'name' => $program->translated('name'),
                'short_name' => $program->translated('short_name', false),
                'has_levels' => $program->has_levels,
                'has_exam_stages' => $program->has_exam_stages,
            ])
            ->all();
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
