<?php

namespace App\Http\Controllers\Compliance;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\RequestCandidate;
use App\Models\RetentionPolicy;
use App\Services\RedactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RetentionController extends Controller
{
    public function index()
    {
        $policies = RetentionPolicy::orderBy('id')->get();

        // Quick metrics: how many candidates are past retention right now?
        $candidatePolicy = $policies->firstWhere('entity_type', 'candidate');
        $threshold = $candidatePolicy && $candidatePolicy->enabled
            ? now()->subDays($candidatePolicy->retention_days)
            : null;

        $eligibleForPurge = $threshold
            ? RequestCandidate::whereNull('redacted_at')
                ->where('created_at', '<', $threshold)
                ->count()
            : 0;

        return view('compliance.retention.index', compact('policies', 'threshold', 'eligibleForPurge'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'policies'                => 'required|array',
            'policies.*.id'           => 'required|integer|exists:retention_policies,id',
            'policies.*.retention_days' => 'required|integer|min:30|max:36500',
            'policies.*.enabled'      => 'sometimes|boolean',
        ]);

        $before = RetentionPolicy::pluck('retention_days', 'id')->all();

        DB::transaction(function () use ($data) {
            foreach ($data['policies'] as $row) {
                RetentionPolicy::where('id', $row['id'])->update([
                    'retention_days' => (int) $row['retention_days'],
                    'enabled'        => isset($row['enabled']) && $row['enabled'],
                ]);
            }
        });

        $after = RetentionPolicy::pluck('retention_days', 'id')->all();

        AdminAuditLog::record('pdpa.retention_updated', null, [
            'before' => $before,
            'after'  => $after,
        ]);

        return back()->with('success', 'Retention policies updated.');
    }

    /**
     * Manual one-shot purge — runs the same logic as the scheduled command.
     * Useful for super admins to trigger immediately or to test.
     */
    public function purgeNow(Request $request)
    {
        $policy = RetentionPolicy::where('entity_type', 'candidate')->where('enabled', true)->first();
        if (! $policy) {
            return back()->with('error', 'Candidate retention policy is disabled.');
        }

        $threshold = now()->subDays($policy->retention_days);
        $candidates = RequestCandidate::whereNull('redacted_at')
            ->where('created_at', '<', $threshold)
            ->limit(1000) // hard cap per run
            ->get();

        foreach ($candidates as $c) {
            RedactionService::redactCandidate($c, 'retention_expiry');
        }

        AdminAuditLog::record('pdpa.retention_purge_manual', null, [
            'threshold' => $threshold->toIso8601String(),
            'count'     => $candidates->count(),
        ]);

        return back()->with('success', "Purged {$candidates->count()} candidates past retention threshold.");
    }
}
