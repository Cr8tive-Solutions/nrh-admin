<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->string('currency', 3)->default('USD')->after('region');
        });

        // Set MYR for Malaysia
        DB::table('countries')->where('name', 'Malaysia')->update(['currency' => 'MYR']);
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
