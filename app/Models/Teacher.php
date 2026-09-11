<?php

namespace App\Models;

use App\Models\Concerns\CreatedBetween;
use App\Notifications\TeacherResetPassword;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name', 'email', 'phone', 'password', 'college_id',
    'designation_bn', 'designation_en',
    'email_verified_at', 'is_active', 'approved_at', 'approved_by', 'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class Teacher extends Authenticatable
{
    use CreatedBetween, HasApiTokens, HasFactory, Notifiable, Searchable;

    protected $attributes = [
        'is_active' => false,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
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
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new TeacherResetPassword($token));
    }
}
