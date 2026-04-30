<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screening_request_id')->constrained('screening_requests')->cascadeOnDelete();
            $table->string('type', 20); // basic | prelim | full
            $table->unsignedSmallInteger('version'); // per-type counter, starting at 1
            $table->timestamp('generated_at');
            $table->foreignId('generated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('file_path');
            $table->string('file_sha256', 64);
            $table->string('content_hash', 64); // SHA-256 of canonical snapshot
            $table->jsonb('snapshot');
            $table->foreignId('supersedes_id')->nullable()->constrained('report_versions')->nullOnDelete();
            $table->text('supersede_reason')->nullable();
            $table->timestamps();

            $table->unique(['screening_request_id', 'type', 'version']);
            $table->index(['screening_request_id', 'type']);
            $table->index('content_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_versions');
    }
};
