<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'year']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('tenant_subscription_id')->nullable()->constrained('tenant_subscriptions')->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('status')->index();
            $table->string('currency', 3);

            $table->unsignedInteger('subtotal_cents')->default(0);
            $table->unsignedInteger('tax_cents')->default(0);
            $table->unsignedInteger('total_cents')->default(0);

            $table->string('seller_country', 2);
            $table->string('billing_country', 2)->nullable();
            $table->string('billing_vat_number')->nullable();
            $table->json('billing_address_json')->nullable();

            $table->json('tax_details_json')->nullable();

            $table->timestamp('issued_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('unit_amount_cents');
            $table->unsignedInteger('subtotal_cents');
            $table->decimal('tax_rate_percent', 5, 2)->nullable();
            $table->unsignedInteger('tax_cents')->default(0);
            $table->unsignedInteger('total_cents');
            $table->json('tax_meta_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('invoice_sequences');
    }
};
