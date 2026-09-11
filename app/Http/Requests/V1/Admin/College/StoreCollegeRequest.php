<?php

namespace App\Http\Requests\V1\Admin\College;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCollegeRequest extends FormRequest
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
            'name_bn' => ['required', 'string', 'max:200'],
            'name_en' => ['nullable', 'string', 'max:200'],
            'short_name_bn' => ['nullable', 'string', 'max:60'],
            'short_name_en' => ['nullable', 'string', 'max:60'],
            'eiin_code' => ['nullable', 'string', 'max:20'],
            'college_code' => ['nullable', 'string', 'max:20'],
            'district_bn' => ['nullable', 'string', 'max:100'],
            'district_en' => ['nullable', 'string', 'max:100'],
            'address_bn' => ['nullable', 'string', 'max:255'],
            'address_en' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
