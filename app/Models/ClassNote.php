<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\CreatedBetween;
use App\Models\Concerns\HasTranslatedFields;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'college_id', 'subject_id', 'title_bn', 'title_en', 'description_bn', 'description_en',
    'program_id', 'program_level_id', 'academic_session_id',
    'status', 'published_at', 'expires_at',
    'attachment_disk', 'attachment_path', 'attachment_name', 'attachment_size',
    'teacher_id', 'created_by', 'updated_by',
])]
class ClassNote extends Model
{
    use CreatedBetween, HasFactory, HasTranslatedFields, Searchable, SoftDeletes;

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',

        ];
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
     * @return BelongsTo<AcademicSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function scopeVisibleToCollege(Builder $query, int $collegeId): Builder
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

    public function isVisibleTo(int $collegeId): bool
    {
        return $this->college_id === $collegeId
            && $this->status === ContentStatus::Published
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }
}
