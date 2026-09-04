<?php

namespace App\Models;

use App\Enums\PaymentTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model
{

    protected $fillable = [
        'project_id',
        'payment_type',
        'amount',
        'payment_date',
        'reference_number',
        'proof',
        'gateway_response',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'gateway_response' => 'array',
        'payment_type' => PaymentTypeEnum::class,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function history(): HasOne
    {
        return $this->hasOne(PaymentHistory::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
