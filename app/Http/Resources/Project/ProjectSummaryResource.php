<?php

namespace App\Http\Resources\Project;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_name' => $this->project_name,
            'business_name' => $this->business_name,
            'client_id' => $this->client_id,
            'client_name' => $this->when(
                $this->canSeeClient($request),
                fn () => $this->relationLoaded('client') ? $this->client?->name : null,
            ),
            'team_id' => $this->team_id,
            'assigned_employee_id' => $this->assigned_employee_id,
            'assigned_employee_name' => $this->whenLoaded('assignedEmployee', fn () => $this->assignedEmployee?->user?->name),
            'business_status' => $this->business_status?->value,
            'business_status_label' => $this->business_status?->label(),
            'business_status_color' => $this->business_status?->color(),
            'project_type' => $this->project_type?->value,
            'project_type_label' => $this->project_type?->label(),
            'health_status' => $this->health_status?->value,
            'health_label' => $this->health_status?->label(),
            'health_color' => $this->health_status?->color(),
            'package_amount' => $this->package_amount,
            'amount_paid' => $this->amount_paid,
            'amount_due' => $this->amount_due,
            'sales_target' => $this->sales_target,
            'achieved_sales' => $this->achieved_sales,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'next_payment_date' => $this->next_payment_date?->toDateString(),
        ];
    }

    private function canSeeClient(Request $request): bool
    {
        $user = $request->user();

        if ($user instanceof Client) {
            return true;
        }

        return $user !== null && $user->can('view project client');
    }
}
