<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rb_job_statuses') || ! Schema::hasTable('rb_payment_statuses') || ! Schema::hasTable('tenant_status_overrides')) {
            return;
        }

        if (Schema::hasTable('rb_job_statuses__tenant')) {
            Schema::drop('rb_job_statuses__tenant');
        }
        if (Schema::hasTable('rb_payment_statuses__tenant')) {
            Schema::drop('rb_payment_statuses__tenant');
        }
        if (Schema::hasTable('tenant_status_overrides__tenant')) {
            Schema::drop('tenant_status_overrides__tenant');
        }

        Schema::create('rb_job_statuses__tenant', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');

                $table->string('slug', 64);
                $table->string('label', 255);

                $table->boolean('email_enabled')->default(false);
                $table->text('email_template')->nullable();
                $table->boolean('sms_enabled')->default(false);

                $table->string('invoice_label', 255)->nullable();
                $table->boolean('is_active')->default(true);

                $table->timestamps();

                $table->unique(['tenant_id', 'slug']);
                $table->index(['tenant_id', 'is_active']);

                $table->foreign('tenant_id', 'rb_job_statuses__tenant_tenant_fk')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();
            });

        Schema::create('rb_payment_statuses__tenant', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');

                $table->string('slug', 64);
                $table->string('label', 255);
                $table->text('email_template')->nullable();
                $table->boolean('is_active')->default(true);

                $table->timestamps();

                $table->unique(['tenant_id', 'slug']);
                $table->index(['tenant_id', 'is_active']);

                $table->foreign('tenant_id', 'rb_payment_statuses__tenant_tenant_fk')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();
            });

        Schema::create('tenant_status_overrides__tenant', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');

                $table->string('domain', 64);
                $table->string('code', 64);

                $table->string('label', 255)->nullable();
                $table->string('color', 32)->nullable();
                $table->integer('sort_order')->nullable();

                $table->timestamps();

                $table->unique(['tenant_id', 'domain', 'code']);
                $table->index(['tenant_id', 'domain']);

                $table->foreign('tenant_id', 'tenant_status_overrides__tenant_tenant_fk')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();
            });

        $jobBatch = [];
        foreach (DB::table('rb_job_statuses')->orderBy('id')->cursor() as $row) {
            $jobBatch[] = [
                'tenant_id' => (int) $row->tenant_id,
                'slug' => (string) $row->slug,
                'label' => (string) $row->label,
                'email_enabled' => (bool) $row->email_enabled,
                'email_template' => $row->email_template,
                'sms_enabled' => (bool) $row->sms_enabled,
                'invoice_label' => $row->invoice_label,
                'is_active' => (bool) $row->is_active,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (count($jobBatch) >= 500) {
                DB::table('rb_job_statuses__tenant')->insertOrIgnore($jobBatch);
                $jobBatch = [];
            }
        }
        if (! empty($jobBatch)) {
            DB::table('rb_job_statuses__tenant')->insertOrIgnore($jobBatch);
        }

        $paymentBatch = [];
        foreach (DB::table('rb_payment_statuses')->orderBy('id')->cursor() as $row) {
            $paymentBatch[] = [
                'tenant_id' => (int) $row->tenant_id,
                'slug' => (string) $row->slug,
                'label' => (string) $row->label,
                'email_template' => $row->email_template,
                'is_active' => (bool) $row->is_active,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (count($paymentBatch) >= 500) {
                DB::table('rb_payment_statuses__tenant')->insertOrIgnore($paymentBatch);
                $paymentBatch = [];
            }
        }
        if (! empty($paymentBatch)) {
            DB::table('rb_payment_statuses__tenant')->insertOrIgnore($paymentBatch);
        }

        $overrideBatch = [];
        foreach (DB::table('tenant_status_overrides')->orderBy('id')->cursor() as $row) {
            $overrideBatch[] = [
                'tenant_id' => (int) $row->tenant_id,
                'domain' => (string) $row->domain,
                'code' => (string) $row->code,
                'label' => $row->label,
                'color' => $row->color,
                'sort_order' => is_numeric($row->sort_order) ? (int) $row->sort_order : null,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ];

            if (count($overrideBatch) >= 500) {
                DB::table('tenant_status_overrides__tenant')->insertOrIgnore($overrideBatch);
                $overrideBatch = [];
            }
        }
        if (! empty($overrideBatch)) {
            DB::table('tenant_status_overrides__tenant')->insertOrIgnore($overrideBatch);
        }

        Schema::drop('rb_job_statuses');
        Schema::drop('rb_payment_statuses');
        Schema::drop('tenant_status_overrides');

        Schema::rename('rb_job_statuses__tenant', 'rb_job_statuses');
        Schema::rename('rb_payment_statuses__tenant', 'rb_payment_statuses');
        Schema::rename('tenant_status_overrides__tenant', 'tenant_status_overrides');
    }

    public function down(): void
    {
        // no-op
    }
};
