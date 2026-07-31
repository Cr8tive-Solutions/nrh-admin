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
     * Legacy synonyms the client portal accepts as cash/per-request billing
     * (config('billing.cash_aliases') on nrh-intelligence). Kept in sync
     * manually since the two apps don't share code — the admin form only
     * ever writes 'per_request', but this keeps both sides agreeing if the
     * column is ever touched another way.
     */
    private const CASH_ALIASES = ['per_request', 'per request', 'cash', 'pay_per_use', 'pay per use', 'prepaid', 'pre-paid'];

    /**
     * Canonical billing mode. Anything that isn't a recognised cash/per-request
     * alias is treated as monthly/credit (the safer default).
     */
    public function billingMode(): string
    {
        $value = strtolower(trim((string) $this->billing));

        return in_array($value, self::CASH_ALIASES, true) ? 'per_request' : 'monthly';
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
