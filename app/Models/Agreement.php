<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agreement extends Model
{
    protected $fillable = [
        'customer_id', 'type', 'start_date', 'expiry_date',
        'sla_tat', 'billing', 'payment', 'terms',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'expiry_date' => 'date',
        'terms'       => 'array',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function getDaysLeftAttribute(): int
    {
        return max(0, (int) now()->diffInDays($this->expiry_date, false));
    }

    public function isExpiringSoon(): bool
    {
        return $this->days_left <= 60;
    }

    public function isExpiringSoonCritical(): bool
    {
        return $this->days_left <= 14;
    }
}
