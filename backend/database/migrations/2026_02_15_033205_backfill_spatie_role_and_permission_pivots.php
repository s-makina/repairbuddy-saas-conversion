<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Backfill Spatie pivots using existing legacy RBAC tables.
        // - role_permissions -> role_has_permissions
        // - users.role_id -> model_has_roles (tenant_id scoped)
        // This is idempotent.

        if (DB::getSchemaBuilder()->hasTable('role_permissions') && DB::getSchemaBuilder()->hasTable('role_has_permissions')) {
            $pairs = DB::table('role_permissions')
                ->select(['role_id', 'permission_id'])
                ->orderBy('id')
                ->get();

            foreach ($pairs as $p) {
                $roleId = is_numeric($p->role_id ?? null) ? (int) $p->role_id : null;
                $permId = is_numeric($p->permission_id ?? null) ? (int) $p->permission_id : null;

                if (! $roleId || ! $permId) {
                    continue;
                }

                DB::table('role_has_permissions')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permId,
                ], []);
            }
        }

        if (DB::getSchemaBuilder()->hasTable('users') && DB::getSchemaBuilder()->hasTable('model_has_roles')) {
            $users = DB::table('users')
                ->select(['id', 'tenant_id', 'role_id', 'is_admin'])
                ->whereNotNull('role_id')
                ->get();

            foreach ($users as $u) {
                $userId = is_numeric($u->id ?? null) ? (int) $u->id : null;
                $tenantId = is_numeric($u->tenant_id ?? null) ? (int) $u->tenant_id : null;
                $roleId = is_numeric($u->role_id ?? null) ? (int) $u->role_id : null;
                $isAdmin = (bool) ($u->is_admin ?? false);

                if (! $userId || ! $roleId || $isAdmin) {
                    continue;
                }

                // In teams mode, Spatie pivot primary key includes tenant_id.
                DB::table('model_has_roles')->updateOrInsert([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId,
                    'tenant_id' => $tenantId,
                ], []);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // no-op
    }
};
