<?php

namespace App\Http\Requests\V1\Admin\Teams;

use Illuminate\Validation\Rule;

class UpdateTeamRequest extends StoreTeamRequest
{
    public function rules(): array
    {
        $rules = parent::rules();

        $rules['name'] = [
            'required',
            'string',
            'max:255',
            Rule::unique('teams', 'name')
                ->where('company_id', $this->integer('company_id'))
                ->where('department_id', $this->integer('department_id'))
                ->ignore($this->route('team')),
        ];

        return $rules;
    }
}
