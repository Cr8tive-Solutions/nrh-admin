<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link cash-billed screening requests to the invoice that gates their
 * processing. When the invoice is verified-paid, every linked request flips
 * from 'new' to 'in_progress' so the TAT clock starts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('screening_requests', function (Blueprint $table) {
            $table->foreignId('invoice_id')->nullable()
                ->after('customer_user_id')
                ->constrained()
                ->nullOnDelete();
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::table('screening_requests', function (Blueprint $table) {
            $table->dropForeign(['invoice_id']);
            $table->dropIndex(['invoice_id']);
            $table->dropColumn('invoice_id');
        });
    }
};
