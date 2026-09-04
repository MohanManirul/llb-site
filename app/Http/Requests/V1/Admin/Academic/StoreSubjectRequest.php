<?php

namespace App\Http\Requests\V1\Admin\Academic;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

class StoreSubjectRequest extends FormRequest
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
        $subjectId = $this->route('subject')?->id;

        return [
            'program_id' => ['required', 'integer', Rule::exists('programs', 'id')],
            'program_level_id' => [
                'nullable', 'integer',
                Rule::exists('program_levels', 'id')->where('program_id', $this->input('program_id')),
            ],
            'code' => [
                'nullable', 'string', 'max:20',
                Rule::unique('subjects', 'code')->ignore($subjectId),
            ],
            'name_bn' => ['required', 'string', 'max:200', $this->uniquePerProgramAndLevel('name_bn', $subjectId)],
            'name_en' => ['required', 'string', 'max:200', $this->uniquePerProgramAndLevel('name_en', $subjectId)],
            'description_bn' => ['nullable', 'string', 'max:1000'],
            'description_en' => ['nullable', 'string', 'max:1000'],
            'marks' => ['nullable', 'integer', 'between:1,500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    private function uniquePerProgramAndLevel(string $column, ?int $ignoreId): Unique
    {
        $levelId = $this->input('program_level_id');

        return Rule::unique('subjects', $column)
            ->where('program_id', $this->input('program_id'))
            ->when(
                $levelId === null || $levelId === '',
                fn (Unique $rule) => $rule->whereNull('program_level_id'),
                fn (Unique $rule) => $rule->where('program_level_id', $levelId),
            )
            ->ignore($ignoreId);
    }
}
