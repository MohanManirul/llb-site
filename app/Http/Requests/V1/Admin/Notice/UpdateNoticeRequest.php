<?php

namespace App\Http\Requests\V1\Admin\Notice;

use Illuminate\Contracts\Validation\ValidationRule;

class UpdateNoticeRequest extends StoreNoticeRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'remove_attachment' => ['nullable', 'boolean'],
        ];
    }
}
