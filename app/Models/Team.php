<?php

namespace App\Models;

use App\Enums\TeamRole;
use App\Models\Concerns\CreatedBetween;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use CreatedBetween, Searchable, SoftDeletes;

    protected $fillable = ['company_id', 'department_id', 'name', 'description', 'is_active'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'team_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function leaders(): BelongsToMany
    {
        return $this->members()
            ->wherePivot('role', TeamRole::Leader->value);
    }

    public function regularMembers(): BelongsToMany
    {
        return $this->members()
            ->wherePivot('role', TeamRole::Member->value);
    }

    public function hasLeader(Employee $employee): bool
    {
        return $this->leaders()
            ->where('employee_id', $employee->id)
            ->exists();
    }

    public function scopeLedByEmployees(Builder $query, array $employeeIds): void
    {
        $query->whereHas('members', fn (Builder $member) => $member
            ->whereIn('employees.id', $employeeIds)
            ->where('team_members.role', TeamRole::Leader->value));
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class);
    }
}
