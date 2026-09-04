<?php

namespace App\Http\Resources\Project;

use App\Http\Resources\Assignment\AssignmentResource;
use App\Http\Resources\Client\ClientResource;
use App\Http\Resources\Employee\EmployeeResource;
use App\Http\Resources\Invoice\InvoiceResource;
use App\Http\Resources\Milestone\MilestoneProgressResource;
use App\Http\Resources\Milestone\MilestoneResource;
use App\Http\Resources\Payment\PaymentResource;
use App\Http\Resources\Sale\SaleResource;
use App\Models\Client;
use App\Models\Team;
use App\Models\User;
use App\Services\Project\MilestoneProgressService;
use App\Traits\ChecksProjectAccess;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    use ChecksProjectAccess;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $employeeIds = $user instanceof User ? $user->employeeIds() : [];
        $ledTeamIds = $employeeIds !== [] && $this->team_id !== null
            ? Team::whereKey($this->team_id)->ledByEmployees($employeeIds)->pluck('id')->all()
            : [];
        $canSeeClient = $this->canSeeClient($request);
        $canSeeContact = $this->canSeeContact($request);

        $milestone = $this->resource->milestoneProgress
            ?? app(MilestoneProgressService::class)->forProject($this->resource);

        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'department_id' => $this->department_id,
            'client_id' => $this->client_id,
            'team_id' => $this->team_id,
            'assigned_employee_id' => $this->assigned_employee_id,
            'business_status' => $this->business_status?->value,
            'business_status_label' => $this->business_status?->label(),
            'business_status_color' => $this->business_status?->color(),
            'project_name' => $this->project_name,
            'business_name' => $this->business_name,
            'website_url' => $this->website_url,
            'description' => $this->description,
            'start_date' => $this->start_date?->toDateString(),
            'contract_months' => $this->contract_months,
            'contract_days' => $this->contract_days,
            'end_date' => $this->end_date?->toDateString(),
            'contact_person' => $this->when($canSeeContact, $this->contact_person),
            'contact_email' => $this->when($canSeeContact, $this->contact_email),
            'contact_phone' => $this->when($canSeeContact, $this->contact_phone),
            'package_amount' => $this->package_amount,
            'amount_paid' => $this->amount_paid,
            'amount_due' => $this->amount_due,
            'next_payment_date' => $this->next_payment_date?->toDateString(),
            'project_type' => $this->project_type?->value,
            'project_type_label' => $this->project_type?->label(),
            'sales_target' => $this->sales_target,
            'target_start_date' => $this->target_start_date?->toDateString(),
            'target_months' => $this->target_months,
            'target_days' => $this->target_days,
            'target_deadline' => $this->target_deadline?->toDateString(),
            'monthly_target' => $milestone->target,
            'achieved_sales' => $milestone->achieved,
            'total_achieved_sales' => (float) ($this->resource->achieved_sales ?? 0),
            'milestone' => MilestoneProgressResource::make($milestone),
            'health_status' => $milestone->health->value,
            'health_label' => $milestone->health->label(),
            'health_color' => $milestone->health->color(),
            'created_at' => $this->created_at?->toDateTimeString(),

            'company' => $this->whenLoaded('company', fn () => $this->nameOnly($this->company)),
            'department' => $this->whenLoaded('department', fn () => $this->nameOnly($this->department)),
            'team' => $this->whenLoaded('team', fn () => $this->nameOnly($this->team)),

            'client' => $this->when(
                $canSeeClient,
                fn () => ClientResource::make($this->whenLoaded('client')),
            ),

            'assigned_employee' => EmployeeResource::make($this->whenLoaded('assignedEmployee')),

            'milestones' => MilestoneResource::collection($this->whenLoaded('milestones')),
            'sales' => SaleResource::collection($this->whenLoaded('sales')),
            'assignments' => AssignmentResource::collection($this->whenLoaded('assignments')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),

            'can_edit' => $this->canEditProject($user, $this->resource, $employeeIds, $ledTeamIds),
            'can_delete' => $this->canDeleteProject($user, $this->resource, $employeeIds, $ledTeamIds),
            'can_view_notes' => $this->canViewProjectNotes($user, $this->resource, $employeeIds, $ledTeamIds),
            'can_add_notes' => $this->canAddProjectNotes($user, $this->resource, $employeeIds, $ledTeamIds),
            'can_view_reports' => $this->canViewProjectReports($user, $this->resource, $employeeIds, $ledTeamIds),
            'can_submit_reports' => $this->canSubmitProjectReports($user, $this->resource, $employeeIds),
        ];
    }

    private function nameOnly(?object $resource): ?string
    {
        return $resource?->name;
    }

    private function canSeeClient(Request $request): bool
    {
        $user = $request->user();

        if ($user instanceof Client) {
            return true;
        }

        return $user !== null && $user->can('view project client');
    }

    private function canSeeContact(Request $request): bool
    {
        $user = $request->user();

        if ($user instanceof Client) {
            return true;
        }

        return $user !== null && $user->can('view project contact');
    }
}
