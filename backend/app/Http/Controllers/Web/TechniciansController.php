<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\RepairBuddyJob;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\OneTimePasswordNotification;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class TechniciansController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.technicians.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'technicians',
            'pageTitle' => __('Technicians'),
        ]);
    }

    public function datatable(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            return response()->json(['message' => 'Tenant is missing.'], 400);
        }

        $query = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->whereHas('roles', fn ($q) => $q->where('name', 'Technician'))
            ->orderBy('name');

        return DataTables::eloquent($query)
            ->addColumn('tech_rate_display', function (User $u) {
                $cents = is_numeric($u->tech_hourly_rate_cents) ? (int) $u->tech_hourly_rate_cents : null;
                if ($cents === null) {
                    return '';
                }

                return number_format($cents / 100, 2, '.', '');
            })
            ->addColumn('client_rate_display', function (User $u) {
                $cents = is_numeric($u->client_hourly_rate_cents) ? (int) $u->client_hourly_rate_cents : null;
                if ($cents === null) {
                    return '';
                }

                return number_format($cents / 100, 2, '.', '');
            })
            ->addColumn('jobs_count', function (User $u) use ($tenant) {
                return (int) RepairBuddyJob::query()
                    ->where('tenant_id', $tenant->id)
                    ->where(function ($q) use ($u) {
                        $q->where('assigned_technician_id', $u->id)
                            ->orWhereHas('technicians', fn ($tq) => $tq->whereKey($u->id));
                    })
                    ->distinct('id')
                    ->count('id');
            })
            ->addColumn('hourly_rates_display', function (User $u) {
                $techRate = is_numeric($u->tech_hourly_rate_cents) ? number_format(((int) $u->tech_hourly_rate_cents) / 100, 2, '.', '') : '';
                $clientRate = is_numeric($u->client_hourly_rate_cents) ? number_format(((int) $u->client_hourly_rate_cents) / 100, 2, '.', '') : '';

                return '<div class="d-flex align-items-center gap-2 justify-content-end">'
                    . '<span class="badge text-bg-light border">' . e(__('Tech')) . ': ' . e($techRate !== '' ? $techRate : '--') . '</span>'
                    . '<span class="badge text-bg-light border">' . e(__('Client')) . ': ' . e($clientRate !== '' ? $clientRate : '--') . '</span>'
                    . '</div>';
            })
            ->rawColumns(['hourly_rates_display'])
            ->toJson();
    }

    public function store(Request $request, string $business): RedirectResponse|JsonResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_postal_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address_country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'branch_ids' => ['required', 'array', 'min:1'],
            'branch_ids.*' => ['integer'],
        ]);

        $roleId = Role::query()
            ->where('tenant_id', (int) $tenant->id)
            ->where('name', 'Technician')
            ->value('id');

        $roleId = is_numeric($roleId) ? (int) $roleId : null;

        if (! $roleId) {
            $msg = 'Technician role is missing.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        $branchIds = array_values(array_unique(array_map('intval', (array) $validated['branch_ids'])));

        $validBranchIds = Branch::query()
            ->where('is_active', true)
            ->whereIn('id', $branchIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        sort($branchIds);
        sort($validBranchIds);

        if (count($branchIds) === 0 || $branchIds !== $validBranchIds) {
            $msg = 'Shop selection is invalid.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        $oneTimePassword = Str::password(16);
        $oneTimePasswordExpiresAt = now()->addMinutes(60 * 24);

        $user = User::query()->create([
            'name' => trim((string) $validated['name']),
            'email' => trim((string) $validated['email']),
            'phone' => array_key_exists('phone', $validated) && is_string($validated['phone']) ? trim((string) $validated['phone']) : null,
            'address_line1' => array_key_exists('address_line1', $validated) && is_string($validated['address_line1']) ? trim((string) $validated['address_line1']) : null,
            'address_line2' => array_key_exists('address_line2', $validated) && is_string($validated['address_line2']) ? trim((string) $validated['address_line2']) : null,
            'address_city' => array_key_exists('address_city', $validated) && is_string($validated['address_city']) ? trim((string) $validated['address_city']) : null,
            'address_state' => array_key_exists('address_state', $validated) && is_string($validated['address_state']) ? trim((string) $validated['address_state']) : null,
            'address_postal_code' => array_key_exists('address_postal_code', $validated) && is_string($validated['address_postal_code']) ? trim((string) $validated['address_postal_code']) : null,
            'address_country' => array_key_exists('address_country', $validated) && is_string($validated['address_country']) ? trim((string) $validated['address_country']) : null,
            'address_country_code' => array_key_exists('address_country_code', $validated) && is_string($validated['address_country_code']) ? strtoupper(trim((string) $validated['address_country_code'])) : null,
            'password' => Hash::make(Str::random(72)),
            'must_change_password' => true,
            'one_time_password_hash' => Hash::make($oneTimePassword),
            'one_time_password_expires_at' => $oneTimePasswordExpiresAt,
            'one_time_password_used_at' => null,
            'tenant_id' => (int) $tenant->id,
            'role_id' => $roleId,
            'role' => null,
            'status' => 'active',
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        $sync = [];
        foreach ($validBranchIds as $id) {
            $sync[$id] = ['tenant_id' => (int) $tenant->id];
        }
        $user->branches()->sync($sync);

        try {
            $user->notify(new OneTimePasswordNotification($oneTimePassword, 60 * 24));
        } catch (\Throwable $e) {
            Log::error('technician.onetime_password_notification_failed', [
                'user_id' => $user->id,
                'tenant_id' => (int) $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }

        if ($request->expectsJson()) {
            $label = $user->email ? ($user->name.' ('.$user->email.')') : $user->name;

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'label' => $label,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ], 201);
        }

        return redirect()
            ->route('tenant.technicians.index', ['business' => $tenant->slug])
            ->with('status', __('Technician created.'));
    }

    public function updateHourlyRates(Request $request, string $business, int $user): RedirectResponse
    {
        abort(404);
    }
}
