<?php

namespace App\Models;

use App\Observers\ProjectSaleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([ProjectSaleObserver::class])]
final class ProjectSale extends Model
{
    protected $fillable = [
        'company_id', 'project_id', 'project_milestone_id',
        'team_id', 'employee_id',
        'sale_date', 'amount', 'reference', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'amount' => 'decimal:2',
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

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'project_milestone_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeWithLiveClient(Builder $query): Builder
    {
        return $query->whereHas('project.client');
    }

    public function scopeInPeriod(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('sale_date', [$start, $end]);
    }
}
