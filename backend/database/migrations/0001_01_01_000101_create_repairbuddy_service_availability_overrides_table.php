<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_service_availability_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('service_id')->constrained('rb_services')->cascadeOnDelete();

            $table->string('scope_type', 32);
            $table->unsignedBigInteger('scope_ref_id');

            $table->string('status', 16)->default('inactive');

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'service_id', 'scope_type', 'scope_ref_id'], 'rb_service_availability_overrides_unique');
            $table->index(['tenant_id', 'branch_id', 'service_id', 'scope_type', 'scope_ref_id', 'status'], 'rb_service_availability_overrides_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_service_availability_overrides');
    }
};
