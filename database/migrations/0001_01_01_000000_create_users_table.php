<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Shared Supabase DB — users table is managed by the client portal (nrh-intelligence).
        // This admin portal does not use the users table; admins have their own table.
        // Sessions use the file driver — no sessions table needed.
    }

    public function down(): void
    {
        //
    }
};
