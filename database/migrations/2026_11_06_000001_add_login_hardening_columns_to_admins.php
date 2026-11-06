<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * two_factor_last_used_timestep — the TOTP time-step of the last accepted
     * code, so the same code can never be replayed within its validity window.
     *
     * must_change_password — set on accounts created by another admin, so the
     * initial password they were handed is replaced at first sign-in. Defaults
     * to false: existing admins are not forced through a change.
     */
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->unsignedBigInteger('two_factor_last_used_timestep')->nullable()->after('two_factor_confirmed_at');
            $table->boolean('must_change_password')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['two_factor_last_used_timestep', 'must_change_password']);
        });
    }
};
