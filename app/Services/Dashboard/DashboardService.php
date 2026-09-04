<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Traits\ChecksDashboardAccess;

final class DashboardService
{
    use ChecksDashboardAccess;

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'roles' => $user->effectiveRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'sections' => $this->sections($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function sections(User $user): array
    {
        $sections = [];

        if ($this->canViewDashboard($user)) {
            $sections['overview'] = $this->overview();
        }

        return $sections;
    }

    /** @return array<string, mixed> */
    private function overview(): array
    {
        return [
            'total_users' => User::count(),
        ];
    }
}
