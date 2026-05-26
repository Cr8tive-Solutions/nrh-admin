<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->date('period_date')->nullable()->after('period');
        });

        // Backfill from the existing free-text period column.
        // Accepts "April 2026", "Apr 2026", "2026-04".
        $invoices = DB::table('invoices')->whereNull('period_date')->get(['id', 'period']);

        foreach ($invoices as $row) {
            $date = null;
            foreach (['F Y', 'M Y', 'Y-m'] as $fmt) {
                try {
                    $d = Carbon::createFromFormat($fmt, $row->period);
                    if ($d) { $date = $d->startOfMonth()->toDateString(); break; }
                } catch (\Exception) {}
            }

            if ($date) {
                DB::table('invoices')->where('id', $row->id)->update(['period_date' => $date]);
            }
        }

        // Add unique constraint. Will fail if duplicate (customer_id, period_date) pairs
        // exist in the data — resolve those manually before running this migration.
        Schema::table('invoices', function (Blueprint $table) {
            $table->unique(['customer_id', 'period_date'], 'invoices_customer_period_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_customer_period_unique');
            $table->dropColumn('period_date');
        });
    }
};
