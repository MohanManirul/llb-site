<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedFields;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'program_id', 'program_level_id', 'code', 'slug', 'name_bn', 'name_en',
    'description_bn', 'description_en', 'marks', 'sort_order', 'is_active',
])]
class Subject extends Model
{
    use HasTranslatedFields, Searchable;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<StudyMaterial, $this>
     */
    public function materials(): HasMany
    {
        return $this->hasMany(StudyMaterial::class);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<ProgramLevel, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(ProgramLevel::class, 'program_level_id');
    }
}
