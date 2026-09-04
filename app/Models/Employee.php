<?php

namespace App\Models;

use App\Enums\TeamRole;
use App\Models\Concerns\CreatedBetween;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use CreatedBetween, Searchable, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company_id',
        'department_id',
        'designation_id',
        'description',
        'is_active',
        'joining_date',
        'resignation_date',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'joining_date' => 'date',
            'resignation_date' => 'date',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::get(fn () => $this->user?->name);
    }

    protected function email(): Attribute
    {
        return Attribute::get(fn () => $this->user?->email);
    }

    protected function phone(): Attribute
    {
        return Attribute::get(fn () => $this->user?->phone);
    }

    protected function image(): Attribute
    {
        return Attribute::get(fn () => $this->user?->image);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->user?->image_url;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->user?->thumbnail_url;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ledTeams(): BelongsToMany
    {
        return $this->teams()->wherePivot('role', TeamRole::Leader->value);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function assignedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'assigned_employee_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(ProjectSale::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }

    public function getDesignationWithRole(): string
    {
        $designation = $this->designation?->name ?? 'N/A';

        $role = $this->teams()
            ->select('team_members.role')
            ->orderByRaw("CASE WHEN team_members.role = '".TeamRole::Leader->value."' THEN 0 WHEN team_members.role = '".TeamRole::Member->value."' THEN 1 ELSE 2 END")
            ->limit(1)
            ->first();

        if (! $role) {
            return $designation;
        }

        $label = TeamRole::tryFrom($role->pivot->role)?->label() ?? 'N/A';

        return "{$designation} ({$label})";
    }
}
