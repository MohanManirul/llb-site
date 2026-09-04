<?php

namespace App\Http\Requests\V1\Admin\Client;

use App\Services\Client\ClientImportService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator;

class ImportClientsRequest extends FormRequest
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
                'required',
                'file',
                'mimes:csv,txt',
                'mimetypes:text/plain,text/csv,application/csv,application/vnd.ms-excel',
                'max:10240',
            ],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $file = $this->file('file');

                if (! $file instanceof UploadedFile) {
                    return;
                }

                $problem = app(ClientImportService::class)->headerProblem($file);

                if ($problem !== null) {
                    $validator->errors()->add('file', $problem);
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please choose a CSV file to upload.',
            'file.mimes' => 'Only CSV files are allowed.',
            'file.mimetypes' => 'Only CSV files are allowed.',
            'file.max' => 'The CSV file may not be larger than 10 MB.',
        ];
    }
}
