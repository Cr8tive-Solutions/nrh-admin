<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScreeningRequest extends Model
{
    protected $fillable = [
        'customer_id', 'customer_user_id', 'reference',
        'status', 'type', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

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

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'new'         => 'badge-blue',
            'in_progress' => 'badge-yellow',
            'flagged'     => 'badge-red',
            'complete'    => 'badge-green',
            default       => 'badge-gray',
        };
    }
}
