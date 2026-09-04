<?php

namespace App\Http\Resources\Project;

use App\Http\Resources\Milestone\MilestoneProgressResource;
use App\Models\Client;
use App\Models\User;
use App\Services\Project\MilestoneProgressService;
use App\Traits\ChecksProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ProjectIndexResource extends JsonResource
{
    use ChecksProjectAccess;

    public function toArray(Request $request): array
    {
        $user = $request->user();
        $employeeIds = $request->attributes->get(
            'project_employee_ids',
            $user instanceof User ? $user->employeeIds() : [],
        );
        $ledTeamIds = $request->attributes->get('project_led_team_ids', []);
        $canSeeClient = $user instanceof Client || ($user !== null && $user->can('view project client'));

        $milestone = $this->resource->milestoneProgress
            ?? app(MilestoneProgressService::class)->forProject($this->resource);

        return [
            'id' => $this->id,
            'project_name' => $this->project_name,
            'business_name' => $this->business_name,
            'website_url' => $this->website_url,
            'company' => $this->company?->name,
            'department' => $this->department?->name,
            'client' => $canSeeClient ? $this->client?->name : null,
            'client_email' => $canSeeClient ? $this->client?->email : null,
            'client_phone' => $canSeeClient ? $this->client?->phone : null,
            'team' => $this->team?->name,
            'assigned_employee' => $this->assignedEmployee?->user?->name,
            'assigned_employee_email' => $this->assignedEmployee?->user?->email,
            'assigned_employee_phone' => $this->assignedEmployee?->user?->phone,
            'package_amount' => $this->package_amount,
            'amount_paid' => $this->amount_paid,
            'amount_due' => $this->amount_due,
            'sales_target' => $this->sales_target,
            'monthly_target' => $milestone->target,
            'achieved_sales' => $milestone->achieved,
            'milestone' => MilestoneProgressResource::make($milestone),
            'health_status' => $milestone->health->value,
            'health_label' => $milestone->health->label(),
            'health_color' => $milestone->health->color(),
            'project_type' => $this->project_type->value,
            'project_type_label' => $this->project_type->label(),
            'business_status' => $this->business_status?->value,
            'business_status_label' => $this->business_status?->label(),
            'business_status_color' => $this->business_status?->color(),
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'next_payment_date' => $this->next_payment_date?->toDateString(),
            'created_at' => $this->created_at?->toDateString(),
            'can_edit' => $this->canEditProject($user, $this->resource, $employeeIds, $ledTeamIds),
            'can_delete' => $this->canDeleteProject($user, $this->resource, $employeeIds, $ledTeamIds),
            'can_view_notes' => $this->canViewProjectNotes($user, $this->resource, $employeeIds, $ledTeamIds),
            'can_add_notes' => $this->canAddProjectNotes($user, $this->resource, $employeeIds, $ledTeamIds),
            'can_view_reports' => $this->canViewProjectReports($user, $this->resource, $employeeIds, $ledTeamIds),
            'can_submit_reports' => $this->canSubmitProjectReports($user, $this->resource, $employeeIds),
        ];
    }
}
