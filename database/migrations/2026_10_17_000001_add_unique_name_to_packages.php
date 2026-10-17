<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A customer's package names must be unambiguous within a country — the client
 * portal's request wizard filters packages by the selected country and shows
 * only the name, so two identically-named packages are indistinguishable there.
 *
 * `packages` is created by the client portal; this is the admin-side addition
 * that backs PackageController's uniqueness rule.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->renameDuplicates();

        Schema::table('packages', function (Blueprint $table) {
            $table->unique(['customer_id', 'country_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropUnique(['customer_id', 'country_id', 'name']);
        });
    }

    /**
     * Pre-existing rows were never constrained, so suffix any collision before
     * the index is created rather than failing the deploy.
     */
    private function renameDuplicates(): void
    {
        $groups = DB::table('packages')
            ->select('customer_id', 'country_id', 'name')
            ->groupBy('customer_id', 'country_id', 'name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $ids = DB::table('packages')
                ->where('customer_id', $group->customer_id)
                ->where('country_id', $group->country_id)
                ->where('name', $group->name)
                ->orderBy('id')
                ->pluck('id');

            // Leave the oldest untouched; number the rest.
            foreach ($ids->skip(1)->values() as $index => $id) {
                DB::table('packages')->where('id', $id)->update([
                    'name' => $group->name.' ('.($index + 2).')',
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
