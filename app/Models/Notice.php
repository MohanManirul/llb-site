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

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
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
        return $this->status === ContentStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }
}
