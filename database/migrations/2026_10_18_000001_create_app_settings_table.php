<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared key/value settings, editable from the admin portal and read by both
 * portals (e.g. the SST rate). Delta migration against the shared DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('app_settings')) {
            return;
        }

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
