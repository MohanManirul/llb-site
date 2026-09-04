<?php

namespace App\Services\Access;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

final class AccessService
{
    /**
     * @return Collection<int, string>
     */
    public function permissionNames(): Collection
    {
        return Permission::orderBy('name')->pluck('name');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createStaff(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $user->syncRoles('staff');

        return $user;
    }
}
