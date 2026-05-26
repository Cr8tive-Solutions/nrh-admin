<?php

namespace App\Models;

use App\Traits\HasHashid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agreement extends Model
{
    use HasHashid;
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

    /**
     * Canonical billing mode. The client portal alias-matches multiple
     * legacy synonyms ('cash', 'invoice', etc.) — anything that isn't
     * explicitly 'per_request' is treated as monthly/credit (the safer
     * default), so this resolver mirrors that behaviour.
     */
    public function billingMode(): string
    {
        return $this->billing === 'per_request' ? 'per_request' : 'monthly';
    }

    public function isPerRequest(): bool
    {
        return $this->billingMode() === 'per_request';
    }

    public function isMonthly(): bool
    {
        return $this->billingMode() === 'monthly';
    }
}
