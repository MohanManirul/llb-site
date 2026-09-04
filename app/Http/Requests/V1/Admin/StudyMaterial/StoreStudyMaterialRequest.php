<?php

namespace App\Http\Requests\V1\Admin\StudyMaterial;

use App\Enums\ContentLanguage;
use App\Enums\ExamStage;
use App\Enums\MaterialType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudyMaterialRequest extends FormRequest
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
            'type' => ['required', Rule::enum(MaterialType::class)],
            'title_bn' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description_bn' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'subject_id' => ['required', 'integer', Rule::exists('subjects', 'id')],
            'academic_session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')],
            'exam_stage' => ['nullable', Rule::enum(ExamStage::class)],
            'exam_year' => ['nullable', 'integer', 'between:2000,2100'],
            'content_language' => ['required', Rule::enum(ContentLanguage::class)],
            'author' => ['nullable', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'edition' => ['nullable', 'string', 'max:50'],
            'page_count' => ['nullable', 'integer', 'between:1,10000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*.file' => [
                'required', 'file', 'mimes:pdf', 'mimetypes:application/pdf',
                'max:'.config('llb.max_pdf_kb'),
            ],
            'files.*.label_bn' => ['nullable', 'string', 'max:150'],
            'files.*.label_en' => ['nullable', 'string', 'max:150'],
            'files.*.page_count' => ['nullable', 'integer', 'between:1,10000'],
        ];
    }
}
