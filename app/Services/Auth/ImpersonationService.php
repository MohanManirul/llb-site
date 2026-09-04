<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class ImpersonationService
{
    public const SESSION_KEY = 'impersonator_id';

    public const SESSION_STARTED_AT = 'impersonated_at';

    /** @var array<int, string>|null */
    private ?array $actorPermissions = null;

    private ?int $actorPermissionsFor = null;

    public function start(User $actor, User $target): void
    {
        $this->guard($actor, $target);

        activity()->causedBy($actor)->performedOn($target)
            ->log("Started impersonating {$target->name}.");

        session()->put(self::SESSION_KEY, $actor->id);
        session()->put(self::SESSION_STARTED_AT, now()->timestamp);

        $this->swapTo($target);
    }

    public function stop(): ?User
    {
        $actorId = session()->get(self::SESSION_KEY);

        if (! $actorId) {
            throw new AccessDeniedHttpException('You are not impersonating anyone.');
        }

        $target = Auth::guard('web')->user();
        $actor = User::find($actorId);

        session()->forget([self::SESSION_KEY, self::SESSION_STARTED_AT]);

        if (! $actor) {
            Auth::guard('web')->forgetUser();
            session()->invalidate();
            session()->regenerateToken();

            return null;
        }

        if ($target) {
            activity()->causedBy($actor)->performedOn($target)
                ->log("Stopped impersonating {$target->name}.");
        }

        $this->swapTo($actor);

        return $actor;
    }

    public function mayImpersonate(User $actor, User $target): bool
    {
        if ($this->isImpersonating() || $actor->id === $target->id) {
            return false;
        }

        if ($target->hasRole('super-admin')) {
            return false;
        }

        return $this->holdsEveryPermissionOf($actor, $target);
    }

    public function isImpersonating(): bool
    {
        return (bool) session()->get(self::SESSION_KEY);
    }

    public function impersonator(): ?User
    {
        $actorId = session()->get(self::SESSION_KEY);

        return $actorId ? User::find($actorId) : null;
    }

    private function guard(User $actor, User $target): void
    {
        if ($this->isImpersonating()) {
            throw new AccessDeniedHttpException('You are already impersonating another user.');
        }

        if ($actor->id === $target->id) {
            throw new AccessDeniedHttpException('You cannot impersonate yourself.');
        }

        if ($target->hasRole('super-admin')) {
            throw new AccessDeniedHttpException('A super admin cannot be impersonated.');
        }

        if (! $this->holdsEveryPermissionOf($actor, $target)) {
            throw new AccessDeniedHttpException('You cannot impersonate a user who holds permissions you do not.');
        }
    }

    private function holdsEveryPermissionOf(User $actor, User $target): bool
    {
        if ($actor->hasRole('super-admin')) {
            return true;
        }

        $missing = array_diff(
            $target->getAllPermissions()->pluck('name')->all(),
            $this->actorPermissions($actor),
        );

        return $missing === [];
    }

    /**
     * @return array<int, string>
     */
    private function actorPermissions(User $actor): array
    {
        if ($this->actorPermissionsFor !== $actor->id) {
            $this->actorPermissions = $actor->getAllPermissions()->pluck('name')->all();
            $this->actorPermissionsFor = $actor->id;
        }

        return $this->actorPermissions ?? [];
    }

    private function swapTo(User $user): void
    {
        Auth::guard('web')->login($user);

        session()->forget('password_hash_web');
    }
}
