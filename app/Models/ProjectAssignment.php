<?php

namespace App\Models;

use App\Enums\AssignmentReason;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProjectAssignment extends Model
{
    protected $fillable = [
        'company_id', 'project_id', 'team_id', 'employee_id',
        'assigned_at', 'unassigned_at', 'reason',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'unassigned_at' => 'datetime',
            'reason' => AssignmentReason::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('unassigned_at');
    }
}
