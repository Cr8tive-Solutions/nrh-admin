<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerScopePrice extends Model
{
    protected $fillable = ['customer_id', 'scope_type_id', 'price'];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeType(): BelongsTo
    {
        return $this->belongsTo(ScopeType::class);
    }
}
