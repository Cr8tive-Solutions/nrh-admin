<?php

namespace App\Models;

use App\Services\BusinessHours;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\Pivot;

class CandidateScopeType extends Pivot
{
    protected $table = 'candidate_scope_type';

    public $timestamps = false; // pivot has no created_at / updated_at columns

    public $incrementing = false;

    protected $fillable = [
        'request_candidate_id', 'scope_type_id', 'status',
        'assigned_at', 'started_at', 'completed_at', 'findings',
    ];

    protected $casts = [
        'assigned_at'  => 'datetime',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'findings'     => 'array',
    ];

    /**
     * Whether this scope is still being worked on (i.e. has not reached
     * a terminal state of `complete` or `flagged`).
     */
    public function isRunning(): bool
    {
        return ! in_array($this->status, ['complete', 'flagged'], true);
    }

    /**
     * Business-hours TAT from assignment to completion (or now, if still running).
     * Returns 0 if assigned_at is unknown.
     */
    public function tatHours(): float
    {
        if (! $this->assigned_at) {
            return 0;
        }
        $end = $this->completed_at ?? now();
        return BusinessHours::hoursBetween($this->assigned_at, $end);
    }

    public function slaState(?int $targetHours): string
    {
        if (! $targetHours) {
            return 'no_target';
        }
        if (! $this->assigned_at) {
            return 'unknown';
        }
        return $this->tatHours() > $targetHours ? 'over' : 'within';
    }

    public function slaProgressPct(?int $targetHours): int
    {
        if (! $targetHours) {
            return 0;
        }
        $elapsed = $this->tatHours();
        return min(100, max(0, (int) round(($elapsed / $targetHours) * 100)));
    }
}
