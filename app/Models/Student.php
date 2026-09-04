<?php

namespace App\Models;

use App\Models\Concerns\CreatedBetween;
use App\Notifications\StudentResetPassword;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name', 'email', 'phone', 'password', 'program_id',
    'email_verified_at', 'is_active', 'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class Student extends Authenticatable
{
    use CreatedBetween, HasApiTokens, Notifiable, Searchable;

    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
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
     * @return HasMany<TestAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(TestAttempt::class);
    }

    /**
     * @return HasMany<PracticeSession, $this>
     */
    public function practiceSessions(): HasMany
    {
        return $this->hasMany(PracticeSession::class);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new StudentResetPassword($token));
    }
}
