<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PII-at-rest support for request_candidates.identity_number:
 *   - widen the column to TEXT (encrypted payloads exceed varchar(255))
 *   - add a blind-index column identity_number_hash for exact-match lookups
 *
 * Idempotent: both portals share one database and each runs its own copy of
 * this migration, so it must be safe to apply twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        $type = DB::selectOne(
            "select data_type from information_schema.columns
             where table_name = 'request_candidates' and column_name = 'identity_number'"
        );

        if ($type && $type->data_type !== 'text') {
            DB::statement('ALTER TABLE request_candidates ALTER COLUMN identity_number TYPE text');
        }

        if (! Schema::hasColumn('request_candidates', 'identity_number_hash')) {
            Schema::table('request_candidates', function (Blueprint $table) {
                $table->string('identity_number_hash', 64)->nullable()->after('identity_number');
                $table->index('identity_number_hash');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('request_candidates', 'identity_number_hash')) {
            Schema::table('request_candidates', function (Blueprint $table) {
                $table->dropIndex(['identity_number_hash']);
                $table->dropColumn('identity_number_hash');
            });
        }
    }
};
