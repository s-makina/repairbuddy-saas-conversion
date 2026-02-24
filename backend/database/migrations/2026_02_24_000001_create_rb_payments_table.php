<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('job_id')->index();
            $table->unsignedBigInteger('received_by')->nullable();   // user who recorded it
            $table->string('method', 60)->nullable();                // cash, bank-transfer, card-swipe, etc.
            $table->string('payment_status', 60)->default('paid');   // paid, pending, refunded…
            $table->string('transaction_id', 200)->nullable();
            $table->bigInteger('amount_cents')->default(0);          // stored in cents
            $table->string('currency', 10)->default('USD');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('job_id')->references('id')->on('rb_jobs')->cascadeOnDelete();
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_payments');
    }
};
