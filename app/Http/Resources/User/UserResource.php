<?php

namespace App\Http\Resources\User;

use App\Services\Auth\ImpersonationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'image_url' => $this->image_url,
            'thumbnail_url' => $this->thumbnail_url,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'can_impersonate' => $request->user()?->can('impersonate users')
                && app(ImpersonationService::class)->mayImpersonate($request->user(), $this->resource),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
