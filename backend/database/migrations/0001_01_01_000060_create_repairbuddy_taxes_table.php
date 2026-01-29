<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('name', 255);
            $table->decimal('rate', 8, 3);
            $table->boolean('is_default')->default(false);

            $table->string('default_for_scope', 64)->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'branch_id', 'name']);
            $table->unique(['default_for_scope']);
            $table->index(['tenant_id', 'branch_id', 'is_default']);
        });

        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE rb_taxes MODIFY default_for_scope VARCHAR(64) GENERATED ALWAYS AS (CASE WHEN is_default = 1 THEN CONCAT(tenant_id, '|', branch_id) ELSE NULL END) STORED");
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_taxes');
    }
};
