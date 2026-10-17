<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Invite-to-register onboarding. NRH staff invite a prospective customer by
 * email; the invitee fills in their company and contact details on the client
 * portal (/register/{token}); staff review and approve, which creates the
 * `customers` row and an active Owner login. Shared table — the client portal
 * reads the invitation and writes the submitted details.
 *
 * status: invited → submitted → approved | rejected; invited/submitted can
 * also be cancelled by staff.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('status', 20)->default('invited');
            // SHA-256 of the emailed token; the token itself is never stored.
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->unsignedInteger('sent_count')->default(1);
            $table->timestamp('last_sent_at')->nullable();
            $table->foreignId('invited_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('invite_note')->nullable();

            // Filled in by the invitee (staff may prefill company/contact name).
            $table->string('company_name')->nullable();
            $table->string('registration_no', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_designation', 100)->nullable();
            $table->string('contact_phone', 50)->nullable();
            // Bcrypt hash chosen by the invitee; copied to customer_users on approval.
            $table->string('password_hash')->nullable();
            $table->timestamp('submitted_at')->nullable();

            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('customer_user_id')->nullable()->constrained('customer_users')->nullOnDelete();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_registrations');
    }
};
