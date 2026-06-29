<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-scope list of documents the customer must upload when requesting the
     * scope (e.g. ["consent","nric","resume","certificate"]). The client portal
     * reads this and blocks submission until the required documents are attached
     * — mirroring how it already reads `requires_signed_consent`.
     */
    public function up(): void
    {
        Schema::table('scope_types', function (Blueprint $table) {
            $table->jsonb('required_documents')->nullable()->after('requires_signed_consent');
        });
    }

    public function down(): void
    {
        Schema::table('scope_types', function (Blueprint $table) {
            $table->dropColumn('required_documents');
        });
    }
};
