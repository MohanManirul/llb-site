<?php

namespace App\Http\Requests\V1\Admin\StudyMaterial;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdateStudyMaterialRequest extends StoreStudyMaterialRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        unset(
            $rules['files'],
            $rules['files.*.file'],
            $rules['files.*.label_bn'],
            $rules['files.*.label_en'],
            $rules['files.*.page_count'],
        );

        $rules['remove_cover_image'] = ['nullable', 'boolean'];

        return $rules;
    }
}
