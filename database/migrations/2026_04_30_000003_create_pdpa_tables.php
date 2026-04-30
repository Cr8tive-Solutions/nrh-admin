<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Consent records — proof that the data subject consented to processing ──
        Schema::create('consent_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_candidate_id')->constrained('request_candidates')->cascadeOnDelete();
            $table->timestamp('consented_at');
            $table->string('consent_version', 40);              // e.g. "v1-2026-04"
            $table->text('consent_text_snapshot');              // exact text the subject agreed to
            $table->string('evidence_type', 40);                // digital_form | paper_signed | email | verbal_recorded
            $table->string('evidence_file_path')->nullable();   // private disk path to scan/recording
            $table->string('captured_ip', 45)->nullable();
            $table->text('captured_user_agent')->nullable();
            $table->foreignId('captured_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('request_candidate_id');
            $table->index('consented_at');
        });

        // ── Data subject requests (DSAR) — access, erasure, rectification, etc. ──
        Schema::create('data_subject_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 32)->unique();          // DSR-2026-0001
            $table->foreignId('request_candidate_id')->nullable()->constrained('request_candidates')->nullOnDelete();
            $table->string('subject_name');                     // who is the requestor
            $table->string('subject_email')->nullable();
            $table->string('subject_identity_number')->nullable();
            $table->string('relation', 60);                     // self | authorised_representative | guardian | parent
            $table->string('type', 40);                         // access | erasure | rectification | portability | cease_processing
            $table->string('status', 40);                       // received | verifying_identity | in_progress | completed | rejected
            $table->string('received_via', 40);                 // email | post | phone | in_person
            $table->timestamp('received_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('due_at')->nullable();            // PDPA: respond within 21 working days
            $table->text('description');
            $table->text('outcome')->nullable();                // what we did / why rejected
            $table->string('evidence_file_path')->nullable();   // verification document (IC scan, etc.)
            $table->foreignId('handled_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('type');
            $table->index('due_at');
        });

        // ── Retention policies — configurable per entity type ──
        Schema::create('retention_policies', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 60)->unique();        // candidate | audit_log | invoice | consent_record
            $table->unsignedInteger('retention_days');           // e.g. 2555 = 7 years
            $table->text('description');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        // ── Redaction columns — track when PII was redacted and why ──
        Schema::table('request_candidates', function (Blueprint $table) {
            $table->timestamp('redacted_at')->nullable();
            $table->string('redacted_reason', 100)->nullable(); // retention_expiry | erasure_request_DSR-X
        });

        // Customer-portal users on shared DB — admin-only fields, namespaced to avoid client-portal collision
        Schema::table('customer_users', function (Blueprint $table) {
            $table->timestamp('admin_redacted_at')->nullable();
            $table->string('admin_redacted_reason', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customer_users', function (Blueprint $table) {
            $table->dropColumn(['admin_redacted_at', 'admin_redacted_reason']);
        });
        Schema::table('request_candidates', function (Blueprint $table) {
            $table->dropColumn(['redacted_at', 'redacted_reason']);
        });
        Schema::dropIfExists('retention_policies');
        Schema::dropIfExists('data_subject_requests');
        Schema::dropIfExists('consent_records');
    }
};
