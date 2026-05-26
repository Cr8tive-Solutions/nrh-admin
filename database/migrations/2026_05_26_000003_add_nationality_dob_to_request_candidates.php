<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_candidates', function (Blueprint $table) {
            $table->string('nationality')->nullable()->after('identity_number');
            $table->date('date_of_birth')->nullable()->after('nationality');
        });
    }

    public function down(): void
    {
        Schema::table('request_candidates', function (Blueprint $table) {
            $table->dropColumn(['nationality', 'date_of_birth']);
        });
    }
};
