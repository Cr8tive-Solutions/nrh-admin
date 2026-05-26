<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('period_end')->nullable()->after('period_date');
        });

        // Backfill period_end as the last day of the month for existing rows
        DB::statement("
            UPDATE invoices
            SET period_end = (date_trunc('month', period_date) + interval '1 month' - interval '1 day')::date
            WHERE period_date IS NOT NULL AND period_end IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('period_end');
        });
    }
};
