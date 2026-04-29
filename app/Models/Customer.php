<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
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

    public function activeAgreement(): ?Agreement
    {
        return $this->agreements()->latest('start_date')->first();
    }
}
