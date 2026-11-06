<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Invitation tokens are now stored as hash('sha256', $rawToken) — 64-char
     * lowercase hex — and the raw token only ever travels in the emailed URL.
     * Both portals look invitations up by the hash of the URL token, so hashing
     * the existing plaintext rows keeps links that were already emailed working.
     *
     * Rows already in the hashed format are skipped, so a re-run is harmless.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            UPDATE customer_user_invitations
            SET token = encode(sha256(convert_to(token, 'UTF8')), 'hex')
            WHERE token !~ '^[0-9a-f]{64}$'
        SQL);
    }

    /**
     * Irreversible: a SHA-256 hash cannot be turned back into the raw token.
     */
    public function down(): void
    {
        //
    }
};
