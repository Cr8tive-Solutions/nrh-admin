<?php

namespace App\Models;

use App\Models\Concerns\EncryptsIdentityNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RequestCandidate extends Model
{
    use EncryptsIdentityNumber;

    protected $fillable = [
        'screening_request_id', 'identity_type_id',
        'name', 'identity_number', 'nationality', 'date_of_birth', 'mobile', 'remarks', 'status',
        'redacted_at', 'redacted_reason',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'redacted_at' => 'datetime',
    ];

    public function screeningRequest(): BelongsTo
    {
        return $this->belongsTo(ScreeningRequest::class);
    }

    public function identityType(): BelongsTo
    {
        return $this->belongsTo(IdentityType::class);
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'new' => 'badge-blue',
            'in_progress' => 'badge-yellow',
            'flagged' => 'badge-red',
            'complete' => 'badge-green',
            default => 'badge-gray',
        };
    }

    public function scopeTypes(): BelongsToMany
    {
        return $this->belongsToMany(ScopeType::class, 'candidate_scope_type', 'request_candidate_id', 'scope_type_id')
            ->using(CandidateScopeType::class)
            ->withPivot('status', 'assigned_at', 'started_at', 'completed_at', 'findings')
            ->orderBy('scope_types.sort_order')
            ->orderBy('scope_types.id');
    }

    public function consentRecords(): HasMany
    {
        return $this->hasMany(ConsentRecord::class);
    }

    /** Customer-uploaded documents (NRIC, resume, consent…) from the client portal. */
    public function documents(): HasMany
    {
        return $this->hasMany(CandidateDocument::class);
    }

    public function latestConsent(): HasOne
    {
        return $this->hasOne(ConsentRecord::class)->latestOfMany('consented_at');
    }

    public function isRedacted(): bool
    {
        return $this->redacted_at !== null;
    }
}
