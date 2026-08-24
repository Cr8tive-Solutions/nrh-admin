<?php

namespace App\Models;

use App\Traits\HasHashid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    use HasHashid;

    protected $fillable = [
        'name', 'registration_no', 'address', 'country',
        'industry', 'contact_name', 'contact_email', 'contact_phone', 'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function agreements(): HasMany
    {
        return $this->hasMany(Agreement::class);
    }

    public function customerUsers(): HasMany
    {
        return $this->hasMany(CustomerUser::class);
    }

    /**
     * The first customer_user created for this customer — typically the
     * primary contact provisioned at customer creation time. Used on the
     * customer list to surface invitation status at a glance.
     */
    public function primaryUser(): HasOne
    {
        return $this->hasOne(CustomerUser::class)->oldestOfMany();
    }

    public function screeningRequests(): HasMany
    {
        return $this->hasMany(ScreeningRequest::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function scopePrices(): HasMany
    {
        return $this->hasMany(CustomerScopePrice::class);
    }

    public function packages(): HasMany
    {
        return $this->hasMany(Package::class);
    }

    /**
     * The agreement that governs billing right now: the unexpired agreement
     * with the latest expiry date, falling back to the most recently expired
     * one so a lapsed customer keeps their billing mode instead of silently
     * flipping to monthly/credit. Keep in sync with the client portal's
     * Customer::currentAgreement().
     */
    public function activeAgreement(): ?Agreement
    {
        $agreements = $this->relationLoaded('agreements') ? $this->agreements : $this->agreements()->get();

        return $agreements
            ->filter(fn ($a) => $a->expiry_date && $a->expiry_date->isFuture())
            ->sortByDesc('expiry_date')
            ->first()
            ?? $agreements->sortByDesc('expiry_date')->first();
    }
}
