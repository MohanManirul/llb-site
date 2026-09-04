<?php

namespace App\Models;

use App\Enums\HealthStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProjectMilestone extends Model
{
    protected $fillable = [
        'company_id', 'project_id', 'sequence', 'label',
        'period_start', 'period_end', 'target_amount', 'achieved_amount',
        'status', 'evaluated_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'evaluated_at' => 'datetime',
            'target_amount' => 'decimal:2',
            'achieved_amount' => 'decimal:2',
            'status' => HealthStatus::class,
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

    public function sales(): HasMany
    {
        return $this->hasMany(ProjectSale::class);
    }
}
