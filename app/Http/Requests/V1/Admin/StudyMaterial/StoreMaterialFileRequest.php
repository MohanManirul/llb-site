<?php

namespace App\Http\Requests\V1\Admin\StudyMaterial;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaterialFileRequest extends FormRequest
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
            'file' => [
                'required', 'file', 'mimes:pdf', 'mimetypes:application/pdf',
                'max:'.config('llb.max_pdf_kb'),
            ],
            'label_bn' => ['nullable', 'string', 'max:150'],
            'label_en' => ['nullable', 'string', 'max:150'],
            'page_count' => ['nullable', 'integer', 'between:1,10000'],
        ];
    }
}
