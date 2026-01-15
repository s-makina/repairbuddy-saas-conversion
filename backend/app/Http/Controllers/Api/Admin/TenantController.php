<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TenantController extends Controller
{
    public function index()
    {
        return response()->json([
            'tenants' => Tenant::query()->orderBy('id', 'desc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:64', 'unique:tenants,slug'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8'],
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        $slug = $slug ?: 'tenant';

        $tenant = Tenant::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'status' => 'active',
            'contact_email' => $validated['contact_email'] ?? $validated['owner_email'],
        ]);

        $owner = User::query()->create([
            'name' => $validated['owner_name'],
            'email' => $validated['owner_email'],
            'password' => Hash::make($validated['owner_password']),
            'tenant_id' => $tenant->id,
            'role' => 'owner',
            'is_admin' => false,
        ]);

        return response()->json([
            'tenant' => $tenant,
            'owner' => $owner,
        ], 201);
    }
}
