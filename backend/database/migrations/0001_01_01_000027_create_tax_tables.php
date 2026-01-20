<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('country_code', 2)->index();
            $table->string('name');
            $table->boolean('is_vat')->default(true);
            $table->timestamps();

            $table->unique(['country_code', 'name'], 'tax_profiles_country_name_unique');
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_profile_id')->constrained('tax_profiles')->cascadeOnDelete();
            $table->decimal('rate_percent', 5, 2);
            $table->boolean('is_active')->default(true)->index();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->index(['tax_profile_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('tax_profiles');
    }
};
