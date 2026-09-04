<?php

namespace App\Http\Resources\SalesReport;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project_name' => $this->whenLoaded('project', fn () => $this->project?->business_name),
            'company_id' => $this->company_id,
            'week_start' => $this->week_start?->toDateString(),
            'week_end' => $this->week_end?->toDateString(),
            'total_sales' => $this->total_sales,
            'total_order_quantity' => $this->total_order_quantity,
            'total_amount_spent' => $this->total_amount_spent,
            'description' => $this->description,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
