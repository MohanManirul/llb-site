<?php

namespace App\Models;

use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'slug', 'label', 'start_year', 'end_year', 'is_current', 'is_active', 'sort_order',
])]
class AcademicSession extends Model
{
    use Searchable;

    protected function casts(): array
    {
        return [
            'is_current' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
