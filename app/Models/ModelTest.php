<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\ExamStage;
use App\Models\Concerns\CreatedBetween;
use App\Models\Concerns\HasTranslatedFields;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'slug', 'title_bn', 'title_en', 'description_bn', 'description_en',
    'program_id', 'exam_stage', 'duration_minutes', 'negative_mark',
    'status', 'published_at', 'created_by', 'updated_by',
])]
class ModelTest extends Model
{
    use CreatedBetween, HasTranslatedFields, Searchable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'exam_stage' => ExamStage::class,
            'negative_mark' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsToMany<Question, $this>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'model_test_questions')
            ->withPivot(['sort_order', 'marks'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    /**
     * @return HasMany<TestAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->where('status', ContentStatus::Published)
            ->where('published_at', '<=', now());
    }

    public function isPubliclyVisible(): bool
    {
        return $this->status === ContentStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function totalMarks(): float
    {
        return (float) $this->questions()->sum('model_test_questions.marks');
    }
}
