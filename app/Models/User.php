<?php

namespace App\Models;

use App\Notifications\AdminResetPassword;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'phone', 'image', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, Searchable;

    protected string $guard_name = 'web';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * @return array<int, int>
     */
    public function employeeIds(): array
    {
        return once(fn () => $this->employees()
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all());
    }

    public function effectiveRoleNames(): Collection
    {
        $employeeIds = $this->employeeIds();

        if ($employeeIds === []) {
            return $this->getRoleNames();
        }

        $leadsATeam = Team::ledByEmployees($employeeIds)->exists();

        return $this->getRoleNames()
            ->push('employee')
            ->when($leadsATeam, fn (Collection $names) => $names->push('team-leader'))
            ->unique()
            ->values();
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? assetUrl($this->image) : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->image ? assetUrl(getThumbnailPath($this->image)) : null;
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new AdminResetPassword($token));
    }
}
