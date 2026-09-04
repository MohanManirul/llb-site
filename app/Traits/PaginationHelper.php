<?php

namespace App\Traits;

trait PaginationHelper
{
    protected function perPage(int $default = 10): int
    {
        return min(max((int) request()->query('per_page', $default), 1), 100);
    }
}
