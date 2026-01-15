<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Support\TenantContext;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'tenant' => TenantContext::tenant(),
            'user' => $request->user(),
            'metrics' => [
                'notes_count' => \App\Models\TenantNote::query()->count(),
            ],
        ]);
    }
}
