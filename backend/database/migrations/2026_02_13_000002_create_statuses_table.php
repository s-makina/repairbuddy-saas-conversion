<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();

            $table->string('status_type', 64);
            $table->string('label', 255);

            $table->boolean('email_enabled')->default(false);
            $table->text('email_template')->nullable();

            $table->boolean('sms_enabled')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['tenant_id', 'status_type', 'label']);
            $table->index(['tenant_id', 'status_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};
