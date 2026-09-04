<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\ExamStage;
use App\Enums\QuestionType;
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
    'type', 'subject_id', 'exam_stage', 'exam_year', 'question_bn', 'question_en',
    'explanation_bn', 'explanation_en', 'reference', 'status',
    'created_by', 'updated_by',
])]
class Question extends Model
{
    use CreatedBetween, HasTranslatedFields, Searchable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'status' => ContentStatus::class,
            'exam_stage' => ExamStage::class,
        ];
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return HasMany<QuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return BelongsToMany<ModelTest, $this>
     */
    public function modelTests(): BelongsToMany
    {
        return $this->belongsToMany(ModelTest::class, 'model_test_questions')
            ->withPivot(['sort_order', 'marks'])
            ->withTimestamps();
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
        return $query->where('status', ContentStatus::Published);
    }

    public function correctOption(): ?QuestionOption
    {
        return $this->options->firstWhere('is_correct', true);
    }
}
