<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Report version numbering is now per candidate, so the uniqueness that used to
 * be (screening_request_id, type, version) must include the candidate. Swap the
 * unique index to (request_candidate_id, type, version). Legacy rows (NULL
 * candidate) are unaffected — Postgres treats NULLs as distinct.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('report_versions')) {
            return;
        }

        // Drop the old request-level unique if present (named conventionally).
        DB::statement('ALTER TABLE report_versions DROP CONSTRAINT IF EXISTS report_versions_screening_request_id_type_version_unique');

        $exists = DB::selectOne(
            "SELECT 1 FROM pg_indexes WHERE tablename = 'report_versions' AND indexname = ?",
            ['report_versions_request_candidate_id_type_version_unique']
        );

        if (! $exists) {
            Schema::table('report_versions', function (Blueprint $table) {
                $table->unique(['request_candidate_id', 'type', 'version']);
            });
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE report_versions DROP CONSTRAINT IF EXISTS report_versions_request_candidate_id_type_version_unique');

        Schema::table('report_versions', function (Blueprint $table) {
            $table->unique(['screening_request_id', 'type', 'version']);
        });
    }
};
