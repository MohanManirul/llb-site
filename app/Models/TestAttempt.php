<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'student_id', 'model_test_id', 'status', 'active', 'started_at', 'expires_at',
    'submitted_at', 'score', 'correct_count', 'wrong_count', 'skipped_count',
])]
class TestAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'active' => 'boolean',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'score' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Student, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo<ModelTest, $this>
     */
    public function modelTest(): BelongsTo
    {
        return $this->belongsTo(ModelTest::class);
    }

    /**
     * @return HasMany<AttemptAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function isPastExpiry(): bool
    {
        return $this->expires_at->lt(now());
    }

    public function remainingSeconds(): int
    {
        return max(0, (int) now()->diffInSeconds($this->expires_at, false));
    }
}
