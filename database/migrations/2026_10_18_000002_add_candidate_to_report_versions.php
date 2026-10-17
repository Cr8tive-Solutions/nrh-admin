<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reports are now issued per individual candidate. Tag each report_versions row
 * with the candidate it covers. Nullable so any legacy request-level rows stay
 * valid; new rows always set it. Delta migration against the shared DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('report_versions') || Schema::hasColumn('report_versions', 'request_candidate_id')) {
            return;
        }

        Schema::table('report_versions', function (Blueprint $table) {
            $table->foreignId('request_candidate_id')
                ->nullable()
                ->after('screening_request_id')
                ->constrained('request_candidates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('report_versions', 'request_candidate_id')) {
            Schema::table('report_versions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('request_candidate_id');
            });
        }
    }
};
