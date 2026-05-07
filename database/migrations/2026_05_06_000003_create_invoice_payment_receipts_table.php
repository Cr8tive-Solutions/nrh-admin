<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer-uploaded payment receipts (cash-billed flow). The client portal
 * writes pending rows when an Accounts user uploads a receipt PDF/image; admin
 * reviews and either verifies (→ creates transactions row, flips invoice paid,
 * cascades request status) or rejects.
 *
 * Per 2026-05-05 client meeting + handoff #3 from nrh-intelligence.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_customer_user_id')->nullable()
                ->constrained('customer_users')->nullOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->decimal('amount_claimed', 10, 2)->nullable();
            $table->date('paid_on')->nullable();
            // Bank transaction reference / cheque number — opaque string.
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            // pending | verified | rejected — admin-owned. Default 'pending'.
            $table->string('status')->default('pending');
            $table->foreignId('verified_by_admin_id')->nullable()
                ->constrained('admins')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->timestamps();
            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_receipts');
    }
};
