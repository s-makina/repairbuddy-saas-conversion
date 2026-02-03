<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('case_number', 64);
            $table->string('title', 255)->default('');

            $table->string('status', 32)->default('pending');

            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->date('pickup_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->text('case_detail')->nullable();

            $table->foreignId('assigned_technician_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->foreignId('converted_job_id')->nullable()->constrained('rb_jobs')->nullOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'case_number']);
            $table->index(['tenant_id', 'branch_id', 'status']);
            $table->index(['tenant_id', 'branch_id', 'updated_at']);
        });

        Schema::create('rb_estimate_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('estimate_id')->constrained('rb_estimates')->cascadeOnDelete();
            $table->foreignId('customer_device_id')->nullable()->constrained('rb_customer_devices')->nullOnDelete();

            $table->string('label_snapshot', 255);
            $table->string('serial_snapshot', 255)->nullable();
            $table->string('pin_snapshot', 255)->nullable();
            $table->text('notes_snapshot')->nullable();
            $table->json('extra_fields_snapshot_json')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'estimate_id'], 'rb_estimate_devices_estimate_idx');
        });

        Schema::create('rb_estimate_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('estimate_id')->constrained('rb_estimates')->cascadeOnDelete();

            $table->string('item_type', 32);
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->string('name_snapshot', 255);

            $table->integer('qty')->default(1);

            $table->integer('unit_price_amount_cents');
            $table->string('unit_price_currency', 3)->nullable();

            $table->foreignId('tax_id')->nullable()->constrained('rb_taxes')->nullOnDelete();

            $table->json('meta_json')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'estimate_id'], 'rb_estimate_items_estimate_idx');
            $table->index(['tenant_id', 'branch_id', 'item_type'], 'rb_estimate_items_type_idx');
        });

        Schema::create('rb_estimate_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('estimate_id')->constrained('rb_estimates')->cascadeOnDelete();

            $table->string('purpose', 32);
            $table->string('token_hash', 255);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'estimate_id'], 'rb_estimate_tokens_estimate_idx');
            $table->unique(['token_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_estimate_tokens');
        Schema::dropIfExists('rb_estimate_items');
        Schema::dropIfExists('rb_estimate_devices');
        Schema::dropIfExists('rb_estimates');
    }
};
