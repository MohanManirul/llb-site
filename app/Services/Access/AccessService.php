<?php

namespace App\Services\Access;

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public function grantClientLogin(Client $client, string $password): Client
    {
        $client->forceFill(['password' => $password])->save();

        return $client->refresh();
    }

    public function revokeClientLogin(Client $client): void
    {
        DB::transaction(function () use ($client) {
            $client->tokens()->delete();

            $client->forceFill(['password' => Str::random(64)])->save();
        });
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
