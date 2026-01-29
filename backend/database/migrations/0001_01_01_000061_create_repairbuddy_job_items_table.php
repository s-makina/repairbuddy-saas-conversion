<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_job_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('job_id')->constrained('rb_jobs')->cascadeOnDelete();

            $table->string('item_type', 32);
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('name_snapshot', 255);

            $table->integer('qty')->default(1);

            $table->integer('unit_price_amount_cents');
            $table->string('unit_price_currency', 3)->nullable();

            $table->foreignId('tax_id')->nullable()->constrained('rb_taxes')->nullOnDelete();

            $table->json('meta_json')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'job_id'], 'rb_job_items_job_idx');
            $table->index(['tenant_id', 'branch_id', 'item_type'], 'rb_job_items_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_job_items');
    }
};
