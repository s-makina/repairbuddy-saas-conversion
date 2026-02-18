<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AjaxController extends Controller
{
    /**
     * Handle generic AJAX actions from legacy scripts.
     */
    public function handle(Request $request): JsonResponse
    {
        $action = $request->input('action');

        return match ($action) {
            'wcrb_return_customer_data_select2' => $this->returnCustomerDataSelect2($request),
            'wcrb_get_chart_data' => $this->getChartData($request),
            default => response()->json(['error' => 'Unknown action'], 400),
        };
    }

    /**
     * Search for customers for Select2 dropdowns.
     */
    protected function returnCustomerDataSelect2(Request $request): JsonResponse
    {
        $tenantId = TenantContext::tenantId();
        $branchId = BranchContext::branchId();
        $term = $request->input('q');

        $query = User::query()
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('phone_number', 'like', "%{$term}%");
            })
            ->limit(20);

        $customers = $query->get()->map(function ($user) {
            return [$user->id, $user->name . ' (' . ($user->email ?: $user->phone_number) . ')'];
        });

        return response()->json($customers);
    }

    /**
     * Return placeholder chart data to prevent errors in wcrbscript.js.
     */
    protected function getChartData(Request $request): JsonResponse
    {
        // Placeholder data to satisfy wcrbscript.js
        return response()->json([
            'success' => true,
            'data' => [
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                'datasets' => [
                    [
                        'label' => 'Jobs',
                        'data' => [12, 19, 3, 5, 2, 3],
                    ]
                ]
            ]
        ]);
    }
}
