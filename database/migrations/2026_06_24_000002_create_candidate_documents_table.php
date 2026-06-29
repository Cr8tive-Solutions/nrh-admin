<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-candidate supporting documents uploaded by the customer at request time
 * (NRIC copy, resume, certificate). Consent forms remain in `consent_records`.
 *
 * The client portal writes these; the admin portal reads them (files live on the
 * client portal's storage, reachable via the `client_local` disk).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_candidate_id')->constrained('request_candidates')->cascadeOnDelete();
            $table->foreignId('screening_request_id')->nullable()->constrained('screening_requests')->cascadeOnDelete();
            $table->string('type'); // nric | resume | certificate (consent lives in consent_records)
            $table->string('file_path');
            $table->string('original_name')->nullable();
            $table->timestamps();

            $table->index(['request_candidate_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_documents');
    }
};
