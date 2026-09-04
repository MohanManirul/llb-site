<?php

namespace App\Http\Requests\V1\Admin\User;

use App\Http\Requests\IndexRequest;

class IndexUserRequest extends IndexRequest
{
    /**
     * @return array<int, string>
     */
    protected function allowedSorts(): array
    {
        return [
            'name', 'email', 'created_at',
        ];
    }
}
