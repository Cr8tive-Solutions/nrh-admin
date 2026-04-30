<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataSubjectRequest extends Model
{
    protected $fillable = [
        'reference', 'request_candidate_id',
        'subject_name', 'subject_email', 'subject_identity_number',
        'relation', 'type', 'status', 'received_via',
        'received_at', 'verified_at', 'completed_at', 'due_at',
        'description', 'outcome', 'evidence_file_path',
        'handled_by_admin_id',
    ];

    protected $casts = [
        'received_at'  => 'datetime',
        'verified_at'  => 'datetime',
        'completed_at' => 'datetime',
        'due_at'       => 'datetime',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(RequestCandidate::class, 'request_candidate_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'handled_by_admin_id');
    }

    public static function types(): array
    {
        return [
            'access'           => 'Access (DSAR — request copy of data)',
            'erasure'          => 'Erasure / Right to be forgotten',
            'rectification'    => 'Rectification — correct inaccurate data',
            'portability'      => 'Portability — provide data in machine-readable form',
            'cease_processing' => 'Cease processing',
        ];
    }

    public static function statuses(): array
    {
        return [
            'received'           => 'Received',
            'verifying_identity' => 'Verifying identity',
            'in_progress'        => 'In progress',
            'completed'          => 'Completed',
            'rejected'           => 'Rejected',
        ];
    }

    public static function relations(): array
    {
        return [
            'self'                     => 'Self',
            'authorised_representative'=> 'Authorised representative',
            'guardian'                 => 'Guardian (minor)',
            'parent'                   => 'Parent',
        ];
    }

    public static function nextReference(): string
    {
        $year = now()->year;
        $last = self::where('reference', 'like', "DSR-{$year}-%")
            ->orderByRaw('LENGTH(reference) DESC, reference DESC')
            ->first();
        $seq = $last ? ((int) substr(strrchr($last->reference, '-'), 1)) + 1 : 1;
        return "DSR-{$year}-".str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function isOverdue(): bool
    {
        return $this->due_at
            && in_array($this->status, ['received', 'verifying_identity', 'in_progress'], true)
            && $this->due_at->isPast();
    }

    public function daysToRespond(): ?int
    {
        if (! $this->due_at) return null;
        return (int) round(now()->diffInDays($this->due_at, false));
    }
}
