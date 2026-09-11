<?php

namespace App\Http\Requests\V1\TeacherApi;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassNoteRequest extends FormRequest
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
            'description_bn' => ['nullable', 'string', 'max:20000'],
            'description_en' => ['nullable', 'string', 'max:20000'],
            'subject_id' => ['nullable', 'integer', Rule::exists('subjects', 'id')],
            'program_id' => ['nullable', 'integer', Rule::exists('programs', 'id')],
            'program_level_id' => [
                'nullable', 'integer',
                Rule::exists('program_levels', 'id')->where('program_id', $this->input('program_id')),
            ],
            'academic_session_id' => ['nullable', 'integer', Rule::exists('academic_sessions', 'id')],
            'expires_at' => ['nullable', 'date'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:20480'],
            'remove_attachment' => ['nullable', 'boolean'],
        ];
    }
}
