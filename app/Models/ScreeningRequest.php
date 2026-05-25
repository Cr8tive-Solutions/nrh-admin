<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class ScreeningRequest extends Model
{
    protected $fillable = [
        'customer_id', 'customer_user_id', 'invoice_id', 'reference',
        'status', 'type', 'meta', 'rejection_reason',
        'payment_slip_path', 'payment_slip_uploaded_at',
        'payment_verified_at', 'payment_verified_by',
    ];

    protected $casts = [
        'meta' => 'array',
        'payment_slip_uploaded_at' => 'datetime',
        'payment_verified_at' => 'datetime',
    ];

    public function hasPaymentSlip(): bool
    {
        return ! empty($this->payment_slip_path);
    }

    public function isPaymentVerified(): bool
    {
        return ! is_null($this->payment_verified_at);
    }

    public function paymentVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'payment_verified_by');
    }

    /** Canonical list of every status value the workflow accepts. */
    public const STATUSES = ['new', 'in_progress', 'rejected', 'complete', 'updated'];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerUser(): BelongsTo
    {
        return $this->belongsTo(CustomerUser::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(RequestCandidate::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'new' => 'badge-blue',
            'in_progress' => 'badge-yellow',
            'rejected' => 'badge-red',
            'complete' => 'badge-green',
            'updated' => 'badge-green',
            default => 'badge-gray',
        };
    }

    /** TAT clock should not run while a request is rejected. */
    public function isTatPaused(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Sum of effective scope prices across every candidate's assigned scopes.
     * Uses customer-specific pricing where set, otherwise the scope_types
     * default. Malaysia "price on request" scopes without a customer override
     * contribute 0 — the cash-payment flow assumes pricing has been set.
     */
    public function calculateTotal(): float
    {
        $candidateIds = $this->candidates()->pluck('id');
        if ($candidateIds->isEmpty()) {
            return 0.0;
        }

        $scopeIds = DB::table('candidate_scope_type')
            ->whereIn('request_candidate_id', $candidateIds)
            ->pluck('scope_type_id');

        if ($scopeIds->isEmpty()) {
            return 0.0;
        }

        $customerPrices = DB::table('customer_scope_prices')
            ->where('customer_id', $this->customer_id)
            ->whereIn('scope_type_id', $scopeIds)
            ->pluck('price', 'scope_type_id');

        $defaultPrices = DB::table('scope_types')
            ->whereIn('id', $scopeIds)
            ->pluck('price', 'id');

        $total = 0.0;
        foreach ($scopeIds as $sid) {
            $total += (float) ($customerPrices[$sid] ?? $defaultPrices[$sid] ?? 0);
        }

        return round($total, 2);
    }
}
