<?php

namespace App\Traits;

use App\Models\Client;
use App\Models\User;

trait ChecksDashboardAccess
{
    protected function canViewCompanyDashboard(User|Client|null $user): bool
    {
        return $user instanceof User && $user->can('view dashboard');
    }

    protected function canSeeProjectClient(User|Client|null $user): bool
    {
        return $user instanceof User && $user->can('view project client');
    }

    protected function canViewDashboardFinance(User|Client|null $user): bool
    {
        return $user instanceof User && $user->can('view finance');
    }
}
