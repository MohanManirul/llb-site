<?php

namespace App\Http\Requests\V1\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexDashboardReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function dateFrom(): ?string
    {
        return $this->validated('date_from') ?: null;
    }

    public function dateTo(): ?string
    {
        return $this->validated('date_to') ?: null;
    }
}
