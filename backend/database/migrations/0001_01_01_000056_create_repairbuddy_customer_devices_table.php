<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_customer_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained('rb_devices')->nullOnDelete();

            $table->string('label', 255);
            $table->string('serial', 255)->nullable();
            $table->string('pin', 255)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'customer_id'], 'rb_customer_devices_customer_idx');
            $table->index(['tenant_id', 'branch_id', 'device_id'], 'rb_customer_devices_device_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_customer_devices');
    }
};
