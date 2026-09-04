<?php

namespace App\Http\Requests\V1\Admin\Role;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $permissions = $this->get('permissions', []);

        if (in_array('view project client info', $permissions)) {
            $permissions = array_filter(
                $permissions,
                fn ($p) => $p !== 'view project client info'
            );
            $permissions[] = 'view project client';
            $permissions[] = 'view project contact';
            $this->merge(['permissions' => $permissions]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->ignore($roleId),
            ],
            'permissions' => ['array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
            ],
        ];
    }
}
