<?php

namespace App\Http\Requests\V1\TeacherApi;

use App\Enums\NoticeCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeacherNoticeRequest extends FormRequest
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
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')],
            'academic_session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')],
            'is_pinned' => ['nullable', 'boolean'],
            'expires_at' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'remove_attachment' => ['nullable', 'boolean'],
        ];
    }
}
