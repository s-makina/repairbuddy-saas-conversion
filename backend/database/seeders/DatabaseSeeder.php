<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate([
            'slug' => 'demo',
        ], [
            'name' => 'Demo Tenant',
            'status' => 'active',
            'contact_email' => 'demo-owner@example.com',
        ]);

        User::query()->updateOrCreate([
            'email' => 'admin@example.com',
        ], [
            'name' => 'Platform Admin',
            'password' => Hash::make('Password123!@#Aa'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        User::query()->updateOrCreate([
            'email' => 'demo-owner@example.com',
        ], [
            'name' => 'Demo Owner',
            'password' => Hash::make('Password123!@#Aa'),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
    }
}
