<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 64)->nullable()->after('avatar_path');
            $table->string('address_line1')->nullable()->after('phone');
            $table->string('address_line2')->nullable()->after('address_line1');
            $table->string('address_city')->nullable()->after('address_line2');
            $table->string('address_state')->nullable()->after('address_city');
            $table->string('address_postal_code', 64)->nullable()->after('address_state');
            $table->string('address_country', 255)->nullable()->after('address_postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'address_line1',
                'address_line2',
                'address_city',
                'address_state',
                'address_postal_code',
                'address_country',
            ]);
        });
    }
};
