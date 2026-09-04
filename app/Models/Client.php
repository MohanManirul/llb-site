<?php

namespace App\Models;

use App\Models\Concerns\CreatedBetween;
use App\Notifications\ClientResetPassword;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Client extends Authenticatable
{
    use CreatedBetween, HasApiTokens, HasFactory, Notifiable, Searchable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'image',
        'description',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? assetUrl($this->image) : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->image ? assetUrl(getThumbnailPath($this->image)) : null;
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ClientResetPassword($token));
    }
}
