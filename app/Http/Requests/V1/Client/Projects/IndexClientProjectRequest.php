<?php

namespace App\Http\Requests\V1\Client\Projects;

use App\Enums\BusinessStatus;
use App\Enums\HealthStatus;
use App\Http\Requests\IndexRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexClientProjectRequest extends IndexRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    protected function filterRules(): array
    {
        return [
            'health_status' => ['nullable', 'string', Rule::enum(HealthStatus::class)],
            'business_status' => ['nullable', 'string', Rule::enum(BusinessStatus::class)],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return [
            'project_name', 'business_name', 'package_amount', 'amount_due',
            'sales_target', 'achieved_sales', 'health_status',
            'business_status', 'end_date', 'created_at',
        ];
    }
}
