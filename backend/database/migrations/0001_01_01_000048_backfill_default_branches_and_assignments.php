<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $tenants = DB::table('tenants')->select(['id', 'default_branch_id'])->orderBy('id')->get();

            foreach ($tenants as $tenant) {
                $branchId = $tenant->default_branch_id;

                if (! $branchId) {
                    $branchId = DB::table('branches')->where('tenant_id', $tenant->id)->orderBy('id')->value('id');

                    if (! $branchId) {
                        $baseCode = 'MAIN';
                        $code = $baseCode;
                        $i = 1;

                        while (DB::table('branches')->where('tenant_id', $tenant->id)->where('code', $code)->exists()) {
                            $code = $baseCode.(string) $i;
                            $i++;
                            if ($i > 999) {
                                $code = Str::upper(Str::random(8));
                                break;
                            }
                        }

                        $branchId = DB::table('branches')->insertGetId([
                            'tenant_id' => $tenant->id,
                            'name' => 'Main Branch',
                            'code' => $code,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    DB::table('tenants')->where('id', $tenant->id)->update([
                        'default_branch_id' => $branchId,
                        'updated_at' => now(),
                    ]);
                }

                if (Schema::hasTable('users')) {
                    $users = DB::table('users')
                        ->where('tenant_id', $tenant->id)
                        ->where('is_admin', false)
                        ->pluck('id');

                    foreach ($users as $userId) {
                        DB::table('branch_user')->updateOrInsert([
                            'branch_id' => $branchId,
                            'user_id' => $userId,
                        ], [
                            'tenant_id' => $tenant->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                if (Schema::hasColumn('personal_access_tokens', 'active_branch_id')) {
                    $userIds = DB::table('users')
                        ->where('tenant_id', $tenant->id)
                        ->where('is_admin', false)
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->all();

                    if (count($userIds) > 0) {
                        DB::table('personal_access_tokens')
                            ->whereNull('active_branch_id')
                            ->where('tokenable_type', 'App\\Models\\User')
                            ->whereIn('tokenable_id', $userIds)
                            ->update([
                                'active_branch_id' => $branchId,
                                'updated_at' => now(),
                            ]);
                    }
                }

                if (Schema::hasColumn('impersonation_sessions', 'active_branch_id')) {
                    DB::table('impersonation_sessions')
                        ->where('tenant_id', $tenant->id)
                        ->whereNull('active_branch_id')
                        ->whereNull('ended_at')
                        ->update([
                            'active_branch_id' => $branchId,
                            'updated_at' => now(),
                        ]);
                }

                if (Schema::hasColumn('invoices', 'branch_id')) {
                    DB::table('invoices')
                        ->where('tenant_id', $tenant->id)
                        ->whereNull('branch_id')
                        ->update([
                            'branch_id' => $branchId,
                            'updated_at' => now(),
                        ]);
                }
            }
        });
    }

    public function down(): void
    {
        // no-op
    }
};
