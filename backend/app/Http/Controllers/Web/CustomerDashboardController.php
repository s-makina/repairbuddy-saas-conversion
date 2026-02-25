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
    /* ──────────────────────────── helpers ──────────────────────────── */

    private function resolveContext(string $business): ?array
    {
        $tenant = TenantContext::tenant();
        $user   = Auth::user();

        if (! $tenant || ! $user) {
            return null;
        }

        return [
            'tenant'   => $tenant,
            'business' => $business,
            'user'     => $user,
        ];
    }

    /* ──────────────────────── Dashboard ──────────────────────────── */

    public function dashboard(Request $request, string $business)
    {
        $ctx = $this->resolveContext($business);
        if (! $ctx) {
            abort(404);
        }

        ['tenant' => $tenant, 'user' => $user] = $ctx;

        // Job stats
        $jobsQuery = RepairBuddyJob::where('tenant_id', $tenant->id)
            ->where('customer_id', $user->id);

        $totalJobs     = (clone $jobsQuery)->count();
        $openJobs      = (clone $jobsQuery)->whereNull('closed_at')->count();
        $completedJobs = (clone $jobsQuery)->whereNotNull('closed_at')->count();

        // Recent jobs (last 5)
        $recentJobs = (clone $jobsQuery)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Estimate stats
        $estimatesQuery = RepairBuddyEstimate::where('tenant_id', $tenant->id)
            ->where('customer_id', $user->id);

        $totalEstimates   = (clone $estimatesQuery)->count();
        $pendingEstimates = (clone $estimatesQuery)->where('status', 'pending')->count();

        // Devices
        $totalDevices = RepairBuddyCustomerDevice::where('tenant_id', $tenant->id)
            ->where('customer_id', $user->id)
            ->count();

        return view('tenant.customer.dashboard', array_merge($ctx, [
            'activeMenu'       => 'dashboard',
            'totalJobs'        => $totalJobs,
            'openJobs'         => $openJobs,
            'completedJobs'    => $completedJobs,
            'recentJobs'       => $recentJobs,
            'totalEstimates'   => $totalEstimates,
            'pendingEstimates' => $pendingEstimates,
            'totalDevices'     => $totalDevices,
        ]));
    }

    /* ──────────────────────── My Jobs ──────────────────────────── */

    public function jobs(Request $request, string $business)
    {
        $ctx = $this->resolveContext($business);
        if (! $ctx) {
            abort(404);
        }

        ['tenant' => $tenant, 'user' => $user] = $ctx;

        $statusFilter = $request->get('status', 'all');

        $query = RepairBuddyJob::where('tenant_id', $tenant->id)
            ->where('customer_id', $user->id)
            ->orderByDesc('created_at');

        if ($statusFilter === 'open') {
            $query->whereNull('closed_at');
        } elseif ($statusFilter === 'completed') {
            $query->whereNotNull('closed_at');
        }

        $jobs = $query->paginate(15);

        // Counts for filter pills
        $allCount       = RepairBuddyJob::where('tenant_id', $tenant->id)->where('customer_id', $user->id)->count();
        $openCount      = RepairBuddyJob::where('tenant_id', $tenant->id)->where('customer_id', $user->id)->whereNull('closed_at')->count();
        $completedCount = RepairBuddyJob::where('tenant_id', $tenant->id)->where('customer_id', $user->id)->whereNotNull('closed_at')->count();

        return view('tenant.customer.jobs', array_merge($ctx, [
            'activeMenu'     => 'jobs',
            'jobs'           => $jobs,
            'statusFilter'   => $statusFilter,
            'allCount'       => $allCount,
            'openCount'      => $openCount,
            'completedCount' => $completedCount,
        ]));
    }

    /* ──────────────────────── My Estimates ──────────────────────── */

    public function estimates(Request $request, string $business)
    {
        $ctx = $this->resolveContext($business);
        if (! $ctx) {
            abort(404);
        }

        ['tenant' => $tenant, 'user' => $user] = $ctx;

        $estimates = RepairBuddyEstimate::where('tenant_id', $tenant->id)
            ->where('customer_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('tenant.customer.estimates', array_merge($ctx, [
            'activeMenu' => 'estimates',
            'estimates'  => $estimates,
        ]));
    }

    /* ──────────────────────── My Devices ──────────────────────── */

    public function devices(Request $request, string $business)
    {
        $ctx = $this->resolveContext($business);
        if (! $ctx) {
            abort(404);
        }

        ['tenant' => $tenant, 'user' => $user] = $ctx;

        $devices = RepairBuddyCustomerDevice::where('tenant_id', $tenant->id)
            ->where('customer_id', $user->id)
            ->with('device')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('tenant.customer.devices', array_merge($ctx, [
            'activeMenu' => 'devices',
            'devices'    => $devices,
        ]));
    }

    /* ──────────────────────── My Account ──────────────────────── */

    public function account(Request $request, string $business)
    {
        $ctx = $this->resolveContext($business);
        if (! $ctx) {
            abort(404);
        }

        return view('tenant.customer.account', array_merge($ctx, [
            'activeMenu' => 'account',
        ]));
    }

    /* ──────────────────────── Update Account ──────────────────── */

    public function updateAccount(Request $request, string $business)
    {
        $ctx = $this->resolveContext($business);
        if (! $ctx) {
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

        $user = $ctx['user'];
        $user->update(array_merge($validated, [
            'name' => trim($validated['first_name'] . ' ' . ($validated['last_name'] ?? '')),
        ]));

        return redirect()
            ->route('tenant.customer.account', ['business' => $business])
            ->with('success', 'Account details updated successfully.');
    }
}
