<?php

namespace App\Http\Requests\V1\Admin\Projects;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexSalesReportRequest extends FormRequest
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
            'week_start' => ['nullable', 'date'],
            'week_end' => ['nullable', 'date', 'after_or_equal:week_start'],
        ];
    }

    public function weekStart(): ?string
    {
        return $this->validated('week_start') ?: null;
    }

    public function weekEnd(): ?string
    {
        return $this->validated('week_end') ?: null;
    }
}
