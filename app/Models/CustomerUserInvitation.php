<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerUserInvitation extends Model
{
    protected $fillable = [
        'customer_user_id', 'token', 'expires_at',
        'accepted_at', 'sent_count', 'last_sent_at',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'accepted_at'  => 'datetime',
        'last_sent_at' => 'datetime',
    ];

    protected $hidden = ['token'];

    public function customerUser(): BelongsTo
    {
        return $this->belongsTo(CustomerUser::class);
    }

    public function isAccepted(): bool
    {
        return $this->accepted_at !== null;
    }

    public function isExpired(): bool
    {
        return ! $this->isAccepted() && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return ! $this->isAccepted() && ! $this->isExpired();
    }

    public function status(): string
    {
        return match (true) {
            $this->isAccepted() => 'accepted',
            $this->isExpired()  => 'expired',
            default             => 'pending',
        };
    }

    public function url(): string
    {
        return rtrim(config('services.client_portal.url'), '/').'/invitation/'.$this->token;
    }
}
