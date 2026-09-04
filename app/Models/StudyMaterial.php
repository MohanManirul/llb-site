<?php

namespace App\Models;

use App\Enums\ContentLanguage;
use App\Enums\ContentStatus;
use App\Enums\ExamStage;
use App\Enums\MaterialType;
use App\Models\Concerns\CreatedBetween;
use App\Models\Concerns\HasTranslatedFields;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'type', 'slug', 'title_bn', 'title_en', 'description_bn', 'description_en',
    'subject_id', 'academic_session_id', 'exam_stage', 'exam_year',
    'content_language', 'author', 'publisher', 'edition', 'page_count',
    'cover_image', 'meta', 'status', 'published_at', 'is_featured',
    'sort_order', 'created_by', 'updated_by',
])]
class StudyMaterial extends Model
{
    use CreatedBetween, HasTranslatedFields, Searchable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'type' => MaterialType::class,
            'status' => ContentStatus::class,
            'exam_stage' => ExamStage::class,
            'content_language' => ContentLanguage::class,
            'meta' => 'array',
            'published_at' => 'datetime',
            'is_featured' => 'boolean',
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
     * @return BelongsTo<AcademicSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    /**
     * @return HasMany<MaterialFile, $this>
     */
    public function files(): HasMany
    {
        return $this->hasMany(MaterialFile::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<MaterialDownload, $this>
     */
    public function downloadEvents(): HasMany
    {
        return $this->hasMany(MaterialDownload::class);
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

    public function getCoverUrlAttribute(): ?string
    {
        return $this->cover_image ? assetUrl($this->cover_image) : null;
    }

    public function getCoverThumbnailUrlAttribute(): ?string
    {
        return $this->cover_image ? assetUrl(getThumbnailPath($this->cover_image)) : null;
    }
}
