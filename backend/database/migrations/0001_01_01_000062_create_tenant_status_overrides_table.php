<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_status_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('domain', 64);
            $table->string('code', 64);

            $table->string('label', 255)->nullable();
            $table->string('color', 32)->nullable();
            $table->integer('sort_order')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'domain', 'code']);
            $table->index(['tenant_id', 'branch_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_status_overrides');
    }
};
