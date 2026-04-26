<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CustomerUser extends Model
{
    protected $fillable = [
        'customer_id', 'name', 'email', 'password',
        'role', 'status', 'avatar',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(CustomerUserInvitation::class);
    }

    public function latestInvitation(): HasOne
    {
        return $this->hasOne(CustomerUserInvitation::class)->latestOfMany();
    }
}
