<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'customer_id', 'number', 'period', 'status',
        'issued_at', 'due_at', 'subtotal', 'tax', 'total',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at'    => 'date',
        'subtotal'  => 'decimal:2',
        'tax'       => 'decimal:2',
        'total'     => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public static function generateNumber(): string
    {
        $year = now()->year;
        $last = self::where('number', 'like', "INV-{$year}-%")->orderByDesc('number')->first();
        $seq = $last ? ((int) substr($last->number, -3)) + 1 : 1;
        return "INV-{$year}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'unpaid'  => 'badge-yellow',
            'paid'    => 'badge-green',
            'overdue' => 'badge-red',
            default   => 'badge-gray',
        };
    }
}
