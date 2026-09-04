<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentHistory extends Model
{

    const UPDATED_AT = null;

    protected $fillable = [
        'project_id',
        'payment_id',
        'action',
        'changed_amount',
        'old_paid_amount',
        'new_paid_amount',
        'changed_by',
    ];

    protected $casts = [
        'changed_amount' => 'decimal:2',
        'old_paid_amount' => 'decimal:2',
        'new_paid_amount' => 'decimal:2',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
