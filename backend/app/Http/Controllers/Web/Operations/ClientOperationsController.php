<?php

namespace App\Http\Controllers\Web\Operations;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyJob;
use App\Models\Tenant;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;

class ClientOperationsController extends Controller
{
    public function index(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        return view('tenant.operations.clients.index', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Clients'),
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
            ->where('role', 'customer')
            ->orderByDesc('id');

        return DataTables::eloquent($query)
            ->addColumn('first_name', fn (User $u) => (string) ($u->first_name ?? ''))
            ->addColumn('last_name', fn (User $u) => (string) ($u->last_name ?? ''))
            ->addColumn('phone_display', fn (User $u) => (string) ($u->phone ?? ''))
            ->addColumn('company_display', fn (User $u) => (string) ($u->company ?? ''))
            ->addColumn('tax_id_display', fn (User $u) => (string) ($u->tax_id ?? ''))
            ->addColumn('address_display', function (User $u) {
                $parts = [];

                $line1 = is_string($u->address_line1) ? trim((string) $u->address_line1) : '';
                $city = is_string($u->address_city) ? trim((string) $u->address_city) : '';
                $postal = is_string($u->address_postal_code) ? trim((string) $u->address_postal_code) : '';
                $state = is_string($u->address_state) ? trim((string) $u->address_state) : '';
                $country = is_string($u->address_country) ? trim((string) $u->address_country) : '';

                if ($line1 !== '') {
                    $parts[] = $line1;
                }
                if ($city !== '') {
                    $parts[] = $city;
                }
                if ($postal !== '') {
                    $parts[] = $postal;
                }
                if ($state !== '') {
                    $parts[] = $state;
                }
                if ($country !== '') {
                    $parts[] = $country;
                }

                $address = implode(', ', $parts);

                return '<span class="d-inline-block text-truncate" style="max-width: 320px; white-space: nowrap;" title="' . e($address) . '">' . e($address) . '</span>';
            })
            ->addColumn('actions_display', function (User $client) use ($tenant) {
                $editUrl = route('tenant.operations.clients.edit', ['business' => $tenant->slug, 'client' => $client->id]);
                $deleteUrl = route('tenant.operations.clients.delete', ['business' => $tenant->slug, 'client' => $client->id]);
                $csrf = csrf_field();

                return '<div class="d-inline-flex gap-2">'
                    . '<a class="btn btn-sm btn-outline-primary" href="' . e($editUrl) . '" title="' . e(__('Edit')) . '" aria-label="' . e(__('Edit')) . '"><i class="bi bi-pencil"></i></a>'
                    . '<form method="post" action="' . e($deleteUrl) . '">' . $csrf
                    . '<button type="submit" class="btn btn-sm btn-outline-danger" title="' . e(__('Delete')) . '" aria-label="' . e(__('Delete')) . '"><i class="bi bi-trash"></i></button>'
                    . '</form>'
                    . '</div>';
            })
            ->rawColumns(['actions_display', 'address_display'])
            ->toJson();
    }

    public function create(Request $request, string $business)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $recentClients = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->where('role', 'customer')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('tenant.operations.clients.create', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Add Client'),
            'recentClients' => $recentClients,
        ]);
    }

    public function edit(Request $request, string $business, int $client)
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->where('role', 'customer')
            ->whereKey($client)
            ->firstOrFail();

        return view('tenant.operations.clients.edit', [
            'tenant' => $tenant,
            'user' => $request->user(),
            'activeNav' => 'operations',
            'pageTitle' => __('Edit Client'),
            'client' => $model,
        ]);
    }

    public function store(Request $request, string $business): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tax_id' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_postal_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address_country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
        ]);

        $firstName = trim((string) $validated['first_name']);
        $lastName = array_key_exists('last_name', $validated) && is_string($validated['last_name'])
            ? trim((string) $validated['last_name'])
            : null;
        $fullName = trim($firstName.' '.($lastName ?? ''));

        User::query()->create([
            'tenant_id' => $tenant->id,
            'is_admin' => false,
            'role' => 'customer',
            'role_id' => null,
            'status' => 'active',

            'name' => $fullName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => trim((string) $validated['email']),
            'phone' => array_key_exists('phone', $validated) && is_string($validated['phone']) ? trim((string) $validated['phone']) : null,
            'company' => array_key_exists('company', $validated) && is_string($validated['company']) ? trim((string) $validated['company']) : null,
            'tax_id' => array_key_exists('tax_id', $validated) && is_string($validated['tax_id']) ? trim((string) $validated['tax_id']) : null,
            'address_line1' => array_key_exists('address_line1', $validated) && is_string($validated['address_line1']) ? trim((string) $validated['address_line1']) : null,
            'address_line2' => array_key_exists('address_line2', $validated) && is_string($validated['address_line2']) ? trim((string) $validated['address_line2']) : null,
            'address_city' => array_key_exists('address_city', $validated) && is_string($validated['address_city']) ? trim((string) $validated['address_city']) : null,
            'address_state' => array_key_exists('address_state', $validated) && is_string($validated['address_state']) ? trim((string) $validated['address_state']) : null,
            'address_postal_code' => array_key_exists('address_postal_code', $validated) && is_string($validated['address_postal_code']) ? trim((string) $validated['address_postal_code']) : null,
            'address_country' => array_key_exists('address_country', $validated) && is_string($validated['address_country']) ? trim((string) $validated['address_country']) : null,
            'address_country_code' => array_key_exists('address_country_code', $validated) && is_string($validated['address_country_code']) ? strtoupper(trim((string) $validated['address_country_code'])) : null,
            'currency' => array_key_exists('currency', $validated) && is_string($validated['currency']) ? strtoupper(trim((string) $validated['currency'])) : null,

            'password' => bcrypt(str()->random(48)),
            'email_verified_at' => null,
        ]);

        return redirect()
            ->route('tenant.operations.clients.index', ['business' => $tenant->slug])
            ->with('status', __('Client added.'));
    }

    public function update(Request $request, string $business, int $client): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $existing = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->where('role', 'customer')
            ->whereKey($client)
            ->firstOrFail();

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($existing->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tax_id' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_postal_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address_country' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'currency' => ['sometimes', 'nullable', 'string', 'size:3'],
        ]);

        $firstName = trim((string) $validated['first_name']);
        $lastName = array_key_exists('last_name', $validated) && is_string($validated['last_name'])
            ? trim((string) $validated['last_name'])
            : null;
        $fullName = trim($firstName.' '.($lastName ?? ''));

        $existing->forceFill([
            'role' => 'customer',
            'role_id' => null,
            'is_admin' => false,
            'status' => is_string($existing->status) && $existing->status !== '' ? $existing->status : 'active',

            'name' => $fullName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => trim((string) $validated['email']),
            'phone' => array_key_exists('phone', $validated) && is_string($validated['phone']) ? trim((string) $validated['phone']) : null,
            'company' => array_key_exists('company', $validated) && is_string($validated['company']) ? trim((string) $validated['company']) : null,
            'tax_id' => array_key_exists('tax_id', $validated) && is_string($validated['tax_id']) ? trim((string) $validated['tax_id']) : null,
            'address_line1' => array_key_exists('address_line1', $validated) && is_string($validated['address_line1']) ? trim((string) $validated['address_line1']) : null,
            'address_line2' => array_key_exists('address_line2', $validated) && is_string($validated['address_line2']) ? trim((string) $validated['address_line2']) : null,
            'address_city' => array_key_exists('address_city', $validated) && is_string($validated['address_city']) ? trim((string) $validated['address_city']) : null,
            'address_state' => array_key_exists('address_state', $validated) && is_string($validated['address_state']) ? trim((string) $validated['address_state']) : null,
            'address_postal_code' => array_key_exists('address_postal_code', $validated) && is_string($validated['address_postal_code']) ? trim((string) $validated['address_postal_code']) : null,
            'address_country' => array_key_exists('address_country', $validated) && is_string($validated['address_country']) ? trim((string) $validated['address_country']) : null,
            'address_country_code' => array_key_exists('address_country_code', $validated) && is_string($validated['address_country_code']) ? strtoupper(trim((string) $validated['address_country_code'])) : null,
            'currency' => array_key_exists('currency', $validated) && is_string($validated['currency']) ? strtoupper(trim((string) $validated['currency'])) : null,
        ])->save();

        return redirect()
            ->route('tenant.operations.clients.index', ['business' => $tenant->slug])
            ->with('status', __('Client updated.'));
    }

    public function delete(Request $request, string $business, int $client): RedirectResponse
    {
        $tenant = TenantContext::tenant();

        if (! $tenant instanceof Tenant) {
            abort(400, 'Tenant is missing.');
        }

        $model = User::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_admin', false)
            ->where('role', 'customer')
            ->whereKey($client)
            ->firstOrFail();

        $jobsCount = (int) RepairBuddyJob::query()
            ->where('tenant_id', $tenant->id)
            ->where('customer_id', $model->id)
            ->count();

        if ($jobsCount > 0) {
            return redirect()
                ->route('tenant.operations.clients.index', ['business' => $tenant->slug])
                ->withErrors(['client' => __('Client is in use and cannot be deleted.')]);
        }

        $model->delete();

        return redirect()
            ->route('tenant.operations.clients.index', ['business' => $tenant->slug])
            ->with('status', __('Client deleted.'));
    }
}
