<?php

namespace App\Models;

use App\Observers\SalesReportObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([SalesReportObserver::class])]
final class SalesReport extends Model
{
    protected $fillable = [
        'company_id',
        'project_id',
        'week_start',
        'week_end',
        'total_sales',
        'total_order_quantity',
        'total_amount_spent',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'week_end' => 'date',
            'total_sales' => 'decimal:2',
            'total_amount_spent' => 'decimal:2',
            'total_order_quantity' => 'integer',
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

    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeWithLiveClient(Builder $query): Builder
    {
        return $query->whereHas('project.client');
    }

    public function scopeInPeriod(Builder $query, string $start, string $end): Builder
    {
        return $query->where('week_start', '>=', $start)
            ->where('week_end', '<=', $end);
    }

    public function scopeOverlappingPeriod(Builder $query, ?string $start, ?string $end): Builder
    {
        return $query
            ->when($end !== null, fn (Builder $inner) => $inner->where('week_start', '<=', $end))
            ->when($start !== null, fn (Builder $inner) => $inner->where('week_end', '>=', $start));
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('week_start')->orderByDesc('id');
    }
}
