<?php

namespace App\Services\Dashboard;

use App\Models\Client;

final class ClientDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function forClient(Client $client): array
    {
        return [
            'user' => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
            ],
            'roles' => ['client'],
            'permissions' => [],
            'sections' => ['client' => $this->clientSection($client)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function clientSection(Client $client): array
    {
        $projects = $client->projects()
            ->get([
                'id', 'business_name', 'website_url', 'business_status',
                'start_date', 'end_date', 'package_amount', 'amount_paid',
                'amount_due', 'next_payment_date', 'health_status',
            ]);

        return [
            'profile' => $client->only(['id', 'name', 'email', 'phone']),
            'projects' => $projects,
            'totals' => [
                'projects' => $projects->count(),
                'package_amount' => (float) $projects->sum('package_amount'),
                'paid' => (float) $projects->sum('amount_paid'),
                'due' => (float) $projects->sum('amount_due'),
            ],
        ];
    }
}
