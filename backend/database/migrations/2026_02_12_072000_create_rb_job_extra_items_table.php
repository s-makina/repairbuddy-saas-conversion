<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_job_extra_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('job_id')->constrained('rb_jobs')->cascadeOnDelete();

            $table->timestamp('occurred_at')->nullable();
            $table->string('label', 255);
            $table->text('data_text')->nullable();
            $table->text('description')->nullable();
            $table->string('item_type', 32)->default('text');
            $table->string('visibility', 16)->default('private');

            $table->json('meta_json')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'job_id'], 'rb_job_extra_items_job_idx');
            $table->index(['tenant_id', 'branch_id', 'occurred_at'], 'rb_job_extra_items_occurred_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_job_extra_items');
    }
};
