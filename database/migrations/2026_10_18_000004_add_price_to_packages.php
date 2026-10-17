<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Packages gain an explicit bundle price. Previously a package's price was
 * implied as the sum of its scopes at the customer's effective prices; it is
 * now a required figure the admin sets, and it is what the customer is charged
 * when they order the package (overriding the per-scope sum in both the cash
 * invoice on the client portal and the monthly invoice prefill here).
 *
 * Shared DB, so this runs once. Existing rows are backfilled with their old
 * computed total (unpriced price-on-request scopes counted as 0) so nothing
 * regresses to RM0 silently — the admin can revise each from the UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('packages') || Schema::hasColumn('packages', 'price')) {
            return;
        }

        Schema::table('packages', function (Blueprint $table) {
            $table->decimal('price', 10, 2)->default(0)->after('name');
        });

        // Backfill each package with the sum of its scopes at the customer's
        // effective price (override, else list price), matching the old
        // effectiveTotal(). Price-on-request scopes with no override add 0.
        $packages = DB::table('packages')->get(['id', 'customer_id']);

        foreach ($packages as $package) {
            $scopeIds = DB::table('package_scope_type')
                ->where('package_id', $package->id)
                ->pluck('scope_type_id');

            if ($scopeIds->isEmpty()) {
                continue;
            }

            $overrides = DB::table('customer_scope_prices')
                ->where('customer_id', $package->customer_id)
                ->whereIn('scope_type_id', $scopeIds)
                ->pluck('price', 'scope_type_id');

            $listPrices = DB::table('scope_types')
                ->whereIn('id', $scopeIds)
                ->pluck('price', 'id');

            $total = 0.0;
            foreach ($scopeIds as $sid) {
                $total += (float) ($overrides[$sid] ?? $listPrices[$sid] ?? 0);
            }

            DB::table('packages')->where('id', $package->id)->update(['price' => round($total, 2)]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('packages') && Schema::hasColumn('packages', 'price')) {
            Schema::table('packages', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
};
