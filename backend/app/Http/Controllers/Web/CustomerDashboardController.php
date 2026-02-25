<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyEstimate;
use App\Models\RepairBuddyJob;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerDashboardController extends Controller
{
    /* ──────────────────────────── Portal (single page) ──────────────────────── */

    public function portal(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user   = Auth::user();

        if (! $tenant || ! $user) {
            abort(404);
        }

        // ── Job stats ──
        $jobsQuery     = RepairBuddyJob::where('tenant_id', $tenant->id)->where('customer_id', $user->id);
        $totalJobs     = (clone $jobsQuery)->count();
        $openJobs      = (clone $jobsQuery)->whereNull('closed_at')->count();
        $completedJobs = (clone $jobsQuery)->whereNotNull('closed_at')->count();

        // Recent jobs (last 10)
        $jobs = (clone $jobsQuery)->orderByDesc('created_at')->limit(10)->get();

        // ── Estimate stats ──
        $estimatesQuery    = RepairBuddyEstimate::where('tenant_id', $tenant->id)->where('customer_id', $user->id);
        $totalEstimates    = (clone $estimatesQuery)->count();
        $pendingEstimates  = (clone $estimatesQuery)->where('status', 'pending')->count();
        $approvedEstimates = (clone $estimatesQuery)->where('status', 'approved')->count();
        $rejectedEstimates = (clone $estimatesQuery)->where('status', 'rejected')->count();
        $estimates         = (clone $estimatesQuery)->orderByDesc('created_at')->limit(10)->get();

        // ── Devices ──
        $devices = RepairBuddyCustomerDevice::where('tenant_id', $tenant->id)
            ->where('customer_id', $user->id)
            ->with('device')
            ->orderByDesc('created_at')
            ->get();

        return view('tenant.customer.portal', [
            'tenant'           => $tenant,
            'business'         => $business,
            'user'             => $user,
            'totalJobs'        => $totalJobs,
            'openJobs'         => $openJobs,
            'completedJobs'    => $completedJobs,
            'jobs'             => $jobs,
            'totalEstimates'   => $totalEstimates,
            'pendingEstimates' => $pendingEstimates,
            'approvedEstimates'=> $approvedEstimates,
            'rejectedEstimates'=> $rejectedEstimates,
            'estimates'        => $estimates,
            'devices'          => $devices,
            'section'          => $request->get('section', 'dashboard'),
        ]);
    }

    /* ──────────────────────────── Update Account ────────────────────────────── */

    public function updateAccount(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();
        $user   = Auth::user();

        if (! $tenant || ! $user) {
            abort(404);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'nullable|string|max:100',
            'email'      => 'required|email|max:255',
            'phone'      => 'nullable|string|max:30',
            'company'    => 'nullable|string|max:150',
            'address'    => 'nullable|string|max:255',
            'city'       => 'nullable|string|max:100',
            'state'      => 'nullable|string|max:100',
            'zip'        => 'nullable|string|max:20',
            'country'    => 'nullable|string|max:100',
        ]);

        $user->update(array_merge($validated, [
            'name' => trim($validated['first_name'] . ' ' . ($validated['last_name'] ?? '')),
        ]));

        return redirect()
            ->route('tenant.customer.portal', ['business' => $business, 'section' => 'account'])
            ->with('success', 'Account details updated successfully.');
    }
}
