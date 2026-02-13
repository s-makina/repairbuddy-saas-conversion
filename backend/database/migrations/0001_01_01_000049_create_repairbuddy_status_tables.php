<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_job_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('slug', 64);
            $table->string('label', 255);

            $table->boolean('email_enabled')->default(false);
            $table->text('email_template')->nullable();

            $table->boolean('sms_enabled')->default(false);

            $table->string('invoice_label', 255)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'slug']);
            $table->index(['tenant_id', 'branch_id', 'is_active']);
        });

        Schema::create('rb_payment_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('slug', 64);
            $table->string('label', 255);
            $table->text('email_template')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'slug']);
            $table->index(['tenant_id', 'branch_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_payment_statuses');
        Schema::dropIfExists('rb_job_statuses');
    }
};
