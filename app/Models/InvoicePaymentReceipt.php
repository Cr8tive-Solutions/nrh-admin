<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Receipt-of-payment uploaded by a customer's Accounts user against an
 * invoice. Lifecycle: pending → verified (admin) | rejected (admin).
 *
 * verification_note is admin-only; never expose to the client portal.
 */
class InvoicePaymentReceipt extends Model
{
    protected $fillable = [
        'invoice_id', 'uploaded_by_customer_user_id',
        'file_path', 'file_name',
        'amount_claimed', 'paid_on', 'reference', 'notes',
        'status', 'verified_by_admin_id', 'verified_at', 'verification_note',
    ];

    protected $casts = [
        'amount_claimed' => 'decimal:2',
        'paid_on'        => 'date',
        'verified_at'    => 'datetime',
    ];

    public const STATUSES = ['pending', 'verified', 'rejected'];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(CustomerUser::class, 'uploaded_by_customer_user_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'verified_by_admin_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending'  => 'badge-yellow',
            'verified' => 'badge-green',
            'rejected' => 'badge-red',
            default    => 'badge-gray',
        };
    }
}
