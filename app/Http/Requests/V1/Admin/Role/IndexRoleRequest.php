<?php

namespace App\Http\Requests\V1\Admin\Role;

use App\Http\Requests\IndexRequest;

class IndexRoleRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return [
            'name', 'created_at',
        ];
    }
}
