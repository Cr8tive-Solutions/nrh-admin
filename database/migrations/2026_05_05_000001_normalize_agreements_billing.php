<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-shot data normalisation: collapse free-text agreements.billing values
 * into the canonical strings the client portal recognises ('monthly' or
 * 'per_request'). The column type stays varchar — unknown values are left
 * alone and the client portal treats them as credit (safe default).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Credit / monthly aliases
        DB::table('agreements')
            ->whereRaw("LOWER(TRIM(billing)) IN ('monthly', 'credit', 'invoice', 'postpaid', 'post-paid', 'monthly invoice', 'monthly invoice (credit)')")
            ->update(['billing' => 'monthly']);

        // Cash / per-request aliases
        DB::table('agreements')
            ->whereRaw("LOWER(TRIM(billing)) IN ('per_request', 'per request', 'cash', 'pay_per_use', 'pay per use', 'prepaid', 'pre-paid', 'pay per request', 'pay per request (cash)')")
            ->update(['billing' => 'per_request']);
    }

    public function down(): void
    {
        // No-op — the original free-text values are not preserved.
    }
};
