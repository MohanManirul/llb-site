<?php

namespace App\Http\Requests\V1\Admin\Notice;

use App\Enums\NoticeCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNoticeRequest extends FormRequest
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
            'title_bn' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'excerpt_bn' => ['nullable', 'string', 'max:500'],
            'excerpt_en' => ['nullable', 'string', 'max:500'],
            'body_bn' => ['required', 'string', 'max:20000'],
            'body_en' => ['nullable', 'string', 'max:20000'],
            'category' => ['required', Rule::enum(NoticeCategory::class)],
            'program_id' => ['nullable', 'integer', Rule::exists('programs', 'id')],
            'program_level_id' => [
                'nullable', 'integer',
                Rule::exists('program_levels', 'id')->where('program_id', $this->input('program_id')),
            ],
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')],
            'academic_session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')],
            'is_pinned' => ['required', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'attachment' => [
                'nullable', 'file', 'mimes:pdf', 'mimetypes:application/pdf',
                'max:'.config('llb.max_pdf_kb'),
            ],
        ];
    }
}
