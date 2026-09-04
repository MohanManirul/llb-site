<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedFields;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug', 'name_bn', 'name_en', 'short_name_bn', 'short_name_en',
    'has_levels', 'level_label_bn', 'level_label_en',
    'has_exam_stages', 'has_sessions', 'sort_order', 'is_active',
])]
class Program extends Model
{
    use HasTranslatedFields, Searchable;

    protected function casts(): array
    {
        return [
            'has_levels' => 'boolean',
            'has_exam_stages' => 'boolean',
            'has_sessions' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<ProgramLevel, $this>
     */
    public function levels(): HasMany
    {
        return $this->hasMany(ProgramLevel::class)->orderBy('sort_order')->orderBy('position');
    }

    /**
     * @return HasMany<Subject, $this>
     */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class);
    }
}
