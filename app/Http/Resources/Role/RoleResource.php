<?php

namespace App\Http\Resources\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $permissions = $this->permissions->pluck('name')->all();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'permissions' => array_values($permissions),
            'permissions_count' => $this->whenCounted('permissions'),
            'users_count' => $this->when(isset($this->users_count), $this->users_count),
            'protected' => $this->name === 'super-admin',
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
