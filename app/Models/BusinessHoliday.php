<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessHoliday extends Model
{
    protected $fillable = ['date', 'label'];

    protected $casts = [
        'date' => 'date',
    ];
}
