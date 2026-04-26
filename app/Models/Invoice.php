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
        // Order by numeric tail so mixed-width sequences (e.g. legacy 3-digit + new 4-digit) sort correctly.
        $last = self::where('number', 'like', "INV-{$year}-%")
            ->orderByRaw('LENGTH(number) DESC, number DESC')
            ->first();
        $seq = $last ? ((int) substr(strrchr($last->number, '-'), 1)) + 1 : 1;
        return "INV-{$year}-" . str_pad($seq, 4, '0', STR_PAD_LEFT);
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
