<?php

namespace App\Http\Requests\V1\Admin\Projects;

use App\Rules\SalesReport\NoOverlappingWeek;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesReportRequest extends FormRequest
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
            'week_start' => [
                'required',
                'date',
                new NoOverlappingWeek(
                    $this->route('project')->id,
                    $this->input('week_end'),
                    $this->route('salesReport')->id,
                ),
            ],
            'week_end' => ['required', 'date', 'after_or_equal:week_start'],
            'total_sales' => ['required', 'numeric', 'min:0'],
            'total_order_quantity' => ['required', 'integer', 'min:0'],
            'total_amount_spent' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'week_end.after_or_equal' => 'Week end cannot be earlier than week start.',
        ];
    }
}
