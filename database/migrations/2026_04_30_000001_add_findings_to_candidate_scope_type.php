<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_scope_type', function (Blueprint $table) {
            $table->jsonb('findings')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('candidate_scope_type', function (Blueprint $table) {
            $table->dropColumn('findings');
        });
    }
};
