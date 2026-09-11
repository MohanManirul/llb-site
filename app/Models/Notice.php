<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\NoticeCategory;
use App\Models\Concerns\CreatedBetween;
use App\Models\Concerns\HasTranslatedFields;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'slug', 'title_bn', 'title_en', 'excerpt_bn', 'excerpt_en', 'body_bn', 'body_en',
    'category', 'program_id', 'program_level_id', 'subject_id', 'academic_session_id',
    'college_id', 'teacher_id',
    'is_pinned', 'status', 'published_at', 'expires_at',
    'attachment_disk', 'attachment_path', 'attachment_name', 'attachment_size',
    'created_by', 'updated_by',
])]
class Notice extends Model
{
    use CreatedBetween, HasTranslatedFields, Searchable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'category' => NoticeCategory::class,
            'status' => ContentStatus::class,
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
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
     * @return BelongsTo<ProgramLevel, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(ProgramLevel::class, 'program_level_id');
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
     * @return BelongsTo<College, $this>
     */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * Anonymous readers only ever see notices with no college: a non-null
     * college_id marks a notice private to that college's members.
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->whereNull('college_id')
            ->where('status', ContentStatus::Published)
            ->where('published_at', '<=', now());
    }

    public function scopeForCollege(Builder $query, int $collegeId): Builder
    {
        return $query
            ->where('college_id', $collegeId)
            ->where('status', ContentStatus::Published)
            ->where('published_at', '<=', now());
    }

    public function scopeUnexpired(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->whereNull('expires_at')
            ->orWhere('expires_at', '>', now()));
    }

    public function isPubliclyVisible(): bool
    {
        return $this->college_id === null
            && $this->status === ContentStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }

    public function isVisibleToCollege(int $collegeId): bool
    {
        return $this->college_id === $collegeId
            && $this->status === ContentStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }
}
