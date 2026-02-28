<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_appointments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('appointment_setting_id');
            $table->unsignedBigInteger('job_id')->nullable();
            $table->unsignedBigInteger('estimate_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->string('title')->nullable();
            $table->date('appointment_date');
            $table->time('time_slot_start');
            $table->time('time_slot_end');
            $table->enum('status', [
                'scheduled',
                'confirmed',
                'completed',
                'cancelled',
                'no_show',
            ])->default('scheduled');
            $table->text('notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('reminder_sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('appointment_setting_id')
                ->references('id')
                ->on('rb_appointment_settings')
                ->onDelete('restrict');

            $table->foreign('job_id')
                ->references('id')
                ->on('rb_jobs')
                ->onDelete('set null');

            $table->foreign('estimate_id')
                ->references('id')
                ->on('rb_estimates')
                ->onDelete('set null');

            $table->foreign('customer_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->index(['appointment_date', 'branch_id']);
            $table->index(['appointment_date', 'appointment_setting_id']);
            $table->index(['status', 'appointment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_appointments');
    }
};
