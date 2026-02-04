<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_estimate_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('estimate_id')->constrained('rb_estimates')->cascadeOnDelete();
            $table->foreignId('uploader_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('visibility', 16)->default('public');

            $table->string('original_filename', 255);
            $table->string('mime_type', 255)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);

            $table->string('storage_disk', 32);
            $table->string('storage_path', 1024);
            $table->string('url', 1024)->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'estimate_id'], 'rb_estimate_attachments_estimate_idx');
            $table->index(['tenant_id', 'branch_id', 'created_at'], 'rb_estimate_attachments_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_estimate_attachments');
    }
};
