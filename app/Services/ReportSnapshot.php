<?php

namespace App\Services;

use App\Models\ScreeningRequest;

/**
 * Build a deterministic, canonical snapshot of a screening request's
 * report-relevant state. The snapshot is JSONB-serialised on the version
 * row, and a SHA-256 of its canonical encoding becomes the content_hash
 * used to detect whether anything has changed since the last version of
 * the same report type.
 */
class ReportSnapshot
{
    /**
     * Build the snapshot array. Keys are sorted recursively so two semantically
     * identical inputs produce the same JSON encoding (and therefore the same hash).
     */
    public static function build(ScreeningRequest $request): array
    {
        $request->loadMissing(['customer', 'customerUser', 'candidates.identityType', 'candidates.scopeTypes']);

        $candidates = $request->candidates->map(function ($c) {
            $scopes = $c->scopeTypes->map(fn ($s) => [
                'scope_type_id'    => $s->id,
                'name'             => $s->name,
                'category'         => $s->category,
                'turnaround_hours' => $s->turnaround_hours,
                'status'           => $s->pivot->status,
                'assigned_at'      => optional($s->pivot->assigned_at)->toIso8601String(),
                'started_at'       => optional($s->pivot->started_at)->toIso8601String(),
                'completed_at'     => optional($s->pivot->completed_at)->toIso8601String(),
                'findings'         => $s->pivot->findings,
            ])->sortBy('scope_type_id')->values()->all();

            return [
                'candidate_id'      => $c->id,
                'name'              => $c->name,
                'identity_type'     => optional($c->identityType)->name,
                'identity_number'   => $c->identity_number,
                'mobile'            => $c->mobile,
                'remarks'           => $c->remarks,
                'status'            => $c->status,
                'scopes'            => $scopes,
            ];
        })->sortBy('candidate_id')->values()->all();

        $snapshot = [
            'request' => [
                'id'           => $request->id,
                'reference'    => $request->reference,
                'type'         => $request->type,
                'status'       => $request->status,
                'created_at'   => optional($request->created_at)->toIso8601String(),
                'submitted_by' => optional($request->customerUser)->name,
            ],
            'customer' => $request->customer ? [
                'id'              => $request->customer->id,
                'name'            => $request->customer->name,
                'registration_no' => $request->customer->registration_no,
                'industry'        => $request->customer->industry,
                'contact_name'    => $request->customer->contact_name,
                'contact_email'   => $request->customer->contact_email,
                'contact_phone'   => $request->customer->contact_phone,
            ] : null,
            'meta'       => $request->meta ?? [],
            'candidates' => $candidates,
        ];

        return self::ksortRecursive($snapshot);
    }

    /**
     * SHA-256 of the canonical JSON encoding (sorted keys).
     */
    public static function hash(array $snapshot): string
    {
        return hash('sha256', json_encode($snapshot, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private static function ksortRecursive(array $arr): array
    {
        ksort($arr);
        foreach ($arr as $k => $v) {
            if (is_array($v)) {
                $arr[$k] = self::ksortRecursive($v);
            }
        }
        return $arr;
    }
}
