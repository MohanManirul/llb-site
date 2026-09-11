<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatedFields;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug', 'name_bn', 'name_en', 'short_name_bn', 'short_name_en',
    'eiin_code', 'college_code', 'district_bn', 'district_en',
    'address_bn', 'address_en', 'phone', 'email', 'website',
    'is_active', 'sort_order',
])]
class College extends Model
{
    use HasFactory, HasTranslatedFields, Searchable;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Student, $this>
     */
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    /**
     * @return HasMany<Teacher, $this>
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class);
    }

    /**
     * @return HasMany<Notice, $this>
     */
    public function notices(): HasMany
    {
        return $this->hasMany(Notice::class);
    }

    /**
     * @return HasMany<ClassRoutine, $this>
     */
    public function classRoutines(): HasMany
    {
        return $this->hasMany(ClassRoutine::class);
    }

    /**
     * @return HasMany<ClassNote, $this>
     */
    public function classNotes(): HasMany
    {
        return $this->hasMany(ClassNote::class);
    }
}
