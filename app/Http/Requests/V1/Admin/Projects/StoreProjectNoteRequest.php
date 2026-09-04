<?php

namespace App\Http\Requests\V1\Admin\Projects;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:5000'],
        ];
    }
}
