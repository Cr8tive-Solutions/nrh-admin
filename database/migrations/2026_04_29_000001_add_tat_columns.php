<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SLA target on scope types — numeric so we can compare to actual TAT.
        Schema::table('scope_types', function (Blueprint $table) {
            $table->unsignedInteger('turnaround_hours')->nullable()->after('turnaround');
        });

        // Per-scope TAT timestamps on the pivot.
        Schema::table('candidate_scope_type', function (Blueprint $table) {
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
        });

        // Backfill assigned_at for existing rows from the candidate's created_at.
        DB::statement("
            UPDATE candidate_scope_type AS cst
            SET assigned_at = rc.created_at
            FROM request_candidates AS rc
            WHERE cst.request_candidate_id = rc.id
              AND cst.assigned_at IS NULL
        ");

        // Holiday list, admin-managed (Malaysian public holidays etc.)
        Schema::create('business_holidays', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('label', 120);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_holidays');

        Schema::table('candidate_scope_type', function (Blueprint $table) {
            $table->dropColumn(['assigned_at', 'started_at', 'completed_at']);
        });

        Schema::table('scope_types', function (Blueprint $table) {
            $table->dropColumn('turnaround_hours');
        });
    }
};
