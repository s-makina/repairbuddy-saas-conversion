<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rb_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('entity_type', 64);
            $table->unsignedBigInteger('entity_id');

            $table->string('visibility', 16)->default('private');
            $table->string('event_type', 64);
            $table->json('payload_json')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'branch_id', 'entity_type', 'entity_id', 'created_at'], 'rb_events_entity_idx');
            $table->index(['tenant_id', 'branch_id', 'visibility', 'created_at'], 'rb_events_visibility_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rb_events');
    }
};
