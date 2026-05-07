<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-scope flag: when true, customers cannot use the standard PDPA checkbox
 * to declare consent — they must upload an individually-signed consent form
 * per candidate. The client portal already reads this flag and wires the
 * stricter upload + validation flow when set.
 *
 * Use for: Social Media checks, Education verification, Employment
 * verification, Reference Checks (per 2026-05-05 client meeting).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scope_types', function (Blueprint $table) {
            $table->boolean('requires_signed_consent')
                ->default(false)
                ->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('scope_types', function (Blueprint $table) {
            $table->dropColumn('requires_signed_consent');
        });
    }
};
