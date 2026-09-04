<?php

namespace App\Models;

use App\DTOs\MilestoneProgress;
use App\Enums\BusinessStatus;
use App\Enums\HealthStatus;
use App\Enums\ProjectType;
use App\Models\Concerns\CreatedBetween;
use App\Observers\ProjectObserver;
use App\Traits\Searchable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([ProjectObserver::class])]
final class Project extends Model
{
    use CreatedBetween, Searchable, SoftDeletes;

    public ?MilestoneProgress $milestoneProgress = null;

    protected $fillable = [
        'company_id', 'department_id', 'client_id', 'team_id', 'assigned_employee_id',
        'project_name', 'business_name', 'website_url', 'description',
        'start_date', 'contract_months', 'contract_days', 'end_date',
        'contact_person', 'contact_email', 'contact_phone',
        'package_amount', 'total_amount', 'amount_paid', 'amount_due', 'next_payment_date', 'last_payment_date',
        'project_type', 'sales_target', 'target_start_date', 'target_months', 'target_days', 'target_deadline',
        'achieved_sales', 'health_status', 'health_evaluated_at',
        'business_status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'target_start_date' => 'date',
            'target_deadline' => 'date',
            'next_payment_date' => 'date',
            'last_payment_date' => 'date',
            'health_evaluated_at' => 'datetime',
            'business_status' => BusinessStatus::class,
            'package_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'amount_due' => 'decimal:2',
            'sales_target' => 'decimal:2',
            'achieved_sales' => 'decimal:2',
            'contract_months' => 'integer',
            'contract_days' => 'integer',
            'target_months' => 'integer',
            'target_days' => 'integer',
            'project_type' => ProjectType::class,
            'health_status' => HealthStatus::class,
        ];
    }

    public function scopeWithLiveClient(Builder $query): Builder
    {
        return $query->whereHas('client');
    }

    public static function currentMonthRange(): array
    {
        return [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()];
    }

    public function currentMonthAchievedSales(): float
    {
        [$start, $end] = self::currentMonthRange();

        return (float) $this->salesReports()->inPeriod($start, $end)->sum('total_sales');
    }

    public function monthlyTarget(): float
    {
        $months = (int) ($this->target_months ?: $this->contract_months ?: 12);

        return (float) ($this->sales_target ?? 0) / max($months, 1);
    }

    public static function monthlyHealthStatus(float $achieved, float $target): HealthStatus
    {
        if ($target <= 0.0) {
            return HealthStatus::Upcoming;
        }

        return HealthStatus::fromRatio($achieved / $target, true);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function assignedEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_employee_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class)->latest('assigned_at');
    }

    public function currentAssignment(): HasOne
    {
        return $this->hasOne(ProjectAssignment::class)->whereNull('unassigned_at');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(ProjectMilestone::class)->orderBy('sequence');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(ProjectSale::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class)->latest();
    }

    public function salesReports(): HasMany
    {
        return $this->hasMany(SalesReport::class);
    }

    // payment relationships
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentHistories(): HasMany
    {
        return $this->hasMany(PaymentHistory::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function latestInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latest();
    }
}
