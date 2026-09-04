<?php

namespace App\Http\Requests\V1\StudentApi;

use App\Http\Requests\IndexRequest;

class IndexAttemptRequest extends IndexRequest
{
    protected function defaultPerPage(): int
    {
        return 12;
    }
}
