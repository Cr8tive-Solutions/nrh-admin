<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['name', 'code', 'flag', 'region'];

    public function scopeTypes(): HasMany
    {
        return $this->hasMany(ScopeType::class);
    }
}
