<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScopeType extends Model
{
    protected $fillable = [
        'country_id', 'name', 'category', 'turnaround',
        'price', 'price_on_request', 'description',
    ];

    protected $casts = [
        'price'            => 'decimal:2',
        'price_on_request' => 'boolean',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function customerScopePrice(int $customerId): ?CustomerScopePrice
    {
        return CustomerScopePrice::where('customer_id', $customerId)
            ->where('scope_type_id', $this->id)
            ->first();
    }

    public function effectivePrice(int $customerId): string
    {
        $custom = $this->customerScopePrice($customerId);
        if ($custom) {
            return number_format($custom->price, 2);
        }
        if ($this->price_on_request) {
            return 'Price on request';
        }
        return number_format($this->price, 2);
    }
}
