<?php

namespace App\Models;

use App\Traits\HasHashid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * An invite-to-register onboarding record (see the create migration). The
 * client portal has its own copy of this model for the invitee's side.
 */
class CustomerRegistration extends Model
{
    use HasHashid;

    public const STATUSES = ['invited', 'submitted', 'approved', 'rejected', 'cancelled'];

    public const INVITE_DAYS = 14;

    protected $fillable = [
        'email', 'status', 'token_hash', 'expires_at', 'sent_count', 'last_sent_at',
        'invited_by_admin_id', 'invite_note',
        'company_name', 'registration_no', 'address', 'country', 'industry',
        'contact_name', 'contact_designation', 'contact_phone', 'password_hash', 'submitted_at',
        'reviewed_by_admin_id', 'reviewed_at', 'review_note', 'customer_id', 'customer_user_id',
    ];

    protected $hidden = ['token_hash', 'password_hash'];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'invited_by_admin_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by_admin_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Mint a fresh link token (replacing any previous one, which stops working)
     * and push the expiry out. Returns the plain token — only its hash is stored,
     * so this is the one chance to build the URL.
     */
    public function issueToken(): string
    {
        $token = Str::random(64);

        $this->token_hash = self::hashToken($token);
        $this->expires_at = now()->addDays(self::INVITE_DAYS);
        $this->last_sent_at = now();

        return $token;
    }

    public static function urlFor(string $token): string
    {
        return rtrim(config('services.client_portal.url'), '/').'/register/'.$token;
    }

    public function isExpired(): bool
    {
        return $this->status === 'invited' && $this->expires_at->isPast();
    }

    /** Invited and still waiting on the invitee (link may have expired). */
    public function isOpenInvite(): bool
    {
        return $this->status === 'invited';
    }

    public function isAwaitingReview(): bool
    {
        return $this->status === 'submitted';
    }

    /** Status for display: an invite past its expiry reads as 'expired'. */
    public function displayStatus(): string
    {
        return $this->isExpired() ? 'expired' : $this->status;
    }
}
