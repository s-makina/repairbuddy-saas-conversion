<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthEvent;
use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantDiagnosticsController extends Controller
{
    public function show(Request $request, Tenant $tenant)
    {
        $limit = 50;

        $authEvents = AuthEvent::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $audit = PlatformAuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return response()->json([
            'tenant' => $tenant,
            'recent_auth_events' => $authEvents,
            'recent_failed_jobs' => [],
            'recent_outbound_communications' => [],
            'recent_platform_audit' => $audit,
            'capabilities' => [
                'failed_jobs_supported' => false,
                'outbound_communications_supported' => false,
            ],
        ]);
    }
}
