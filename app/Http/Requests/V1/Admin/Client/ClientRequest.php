<?php

namespace App\Http\Requests\V1\Admin\Client;

use App\Models\Client;
use App\Rules\PhoneNumber;
use App\Rules\UniquePhone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function rules(): array
    {
        $clientId = $this->route('client')?->id;

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:50',
                Rule::unique('clients', 'email')->ignore($clientId),
            ],
            'password' => [
                'nullable',
                Rule::requiredIf($clientId === null),
                'string',
                'min:6',
                'max:255',
                'confirmed',
            ],
            'phone' => [
                'bail',
                'required',
                'digits_between:8,15',
                new PhoneNumber,
                new UniquePhone(new Client, $clientId === null ? null : (string) $clientId),
            ],
            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}
