<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expand screening_requests.status enum to include 'rejected', 'prelim',
 * 'updated' (per 2026-05-05 client meeting). Postgres CHECK constraint
 * must be dropped + recreated.
 *
 * Adds nullable rejection_reason column so admin can capture why a request
 * was rejected; the client portal surfaces this on the request detail page.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE screening_requests DROP CONSTRAINT IF EXISTS screening_requests_status_check");
        DB::statement("ALTER TABLE screening_requests ADD CONSTRAINT screening_requests_status_check CHECK (status IN ('new','in_progress','rejected','prelim','complete','updated','flagged'))");

        Schema::table('screening_requests', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('meta');
        });
    }

    public function down(): void
    {
        // Revert any out-of-original-set rows to 'new' before tightening the constraint.
        DB::statement("UPDATE screening_requests SET status='new' WHERE status IN ('rejected','prelim','updated')");
        DB::statement("ALTER TABLE screening_requests DROP CONSTRAINT IF EXISTS screening_requests_status_check");
        DB::statement("ALTER TABLE screening_requests ADD CONSTRAINT screening_requests_status_check CHECK (status IN ('new','in_progress','complete','flagged'))");

        Schema::table('screening_requests', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
