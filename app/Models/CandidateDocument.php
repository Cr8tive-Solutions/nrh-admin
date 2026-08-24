<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Candidate documents (NRIC, resume, certificates, signed consent) uploaded by
 * the customer during request submission on the client portal. Files live on
 * the client portal's private disk — read them via the client_local mount.
 * Mirrors the client portal's CandidateDocument model.
 */
class CandidateDocument extends Model
{
    protected $fillable = [
        'request_candidate_id',
        'screening_request_id',
        'type',
        'file_path',
        'original_name',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RequestCandidate::class, 'request_candidate_id');
    }

    public function screeningRequest(): BelongsTo
    {
        return $this->belongsTo(ScreeningRequest::class);
    }
}
