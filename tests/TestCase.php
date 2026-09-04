<?php

namespace Tests;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /**
     * The management API is now permission-gated (Spatie). Tests that just do
     * actingAs(User::factory()->create()) predate that and expect full access,
     * so a user with no explicit role is signed in as super-admin (who passes
     * every check via Gate::before). Tests exercising a specific role can
     * simply assign it before calling actingAs.
     */
    public function actingAs(Authenticatable $user, $guard = null): static
    {
        if ($user instanceof User && $user->roles()->count() === 0) {
            $this->grantFullAccess($user);
        }

        return parent::actingAs($user, $guard);
    }

    /** Make this user a super-admin (passes every permission check). */
    protected function grantFullAccess(User $user): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate('super-admin', 'web');
        $user->assignRole('super-admin');

        return $user;
    }
}
