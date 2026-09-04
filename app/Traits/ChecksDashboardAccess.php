<?php

namespace App\Traits;

use App\Models\User;

trait ChecksDashboardAccess
{
    protected function canViewDashboard(?User $user): bool
    {
        return $user?->can('view dashboard') ?? false;
    }
}
