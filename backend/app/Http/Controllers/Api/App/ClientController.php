<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'query' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $q = is_string($validated['query'] ?? null) ? trim((string) $validated['query']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 100;
        $perPage = is_int($validated['per_page'] ?? null) ? (int) $validated['per_page'] : null;

        $tenantId = $this->tenantId();
        $branchId = $this->branchId();

        $query = User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_admin', false)
            ->where(function ($sub) use ($tenantId, $branchId) {
                $sub->whereNull('role_id')
                    ->orWhere('role', 'customer')
                    ->orWhereExists(function ($exists) use ($tenantId, $branchId) {
                        $exists->select(DB::raw(1))
                            ->from('rb_jobs')
                            ->whereColumn('rb_jobs.customer_id', 'users.id')
                            ->where('rb_jobs.tenant_id', $tenantId)
                            ->where('rb_jobs.branch_id', $branchId);
                    });
            })
            ->orderBy('id', 'desc');

        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function ($sub) use ($q, $like) {
                $sub->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('company', 'like', $like)
                    ->orWhere('id', $q);
            });
        }

        $usePagination = is_int($perPage) || is_int($validated['page'] ?? null);

        if ($usePagination) {
            $page = is_int($validated['page'] ?? null) ? (int) $validated['page'] : 1;
            $perPage = is_int($perPage) ? $perPage : 10;

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);
            $items = collect($paginator->items());

            $ids = $items->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
            $counts = [];
            if (! empty($ids)) {
                $counts = DB::table('rb_jobs')
                    ->select(['customer_id', DB::raw('count(*) as jobs_count')])
                    ->where('tenant_id', $tenantId)
                    ->where('branch_id', $branchId)
                    ->whereIn('customer_id', $ids)
                    ->groupBy('customer_id')
                    ->pluck('jobs_count', 'customer_id')
                    ->all();
            }

            return response()->json([
                'clients' => $items->map(function (User $u) use ($counts) {
                    return $this->serializeClient($u, (int) ($counts[$u->id] ?? 0));
                }),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        $clients = $query->limit($limit)->get();

        $ids = $clients->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();
        $counts = [];
        if (! empty($ids)) {
            $counts = DB::table('rb_jobs')
                ->select(['customer_id', DB::raw('count(*) as jobs_count')])
                ->where('tenant_id', $tenantId)
                ->where('branch_id', $branchId)
                ->whereIn('customer_id', $ids)
                ->groupBy('customer_id')
                ->pluck('jobs_count', 'customer_id')
                ->all();
        }

        return response()->json([
            'clients' => $clients->map(function (User $u) use ($counts) {
                return $this->serializeClient($u, (int) ($counts[$u->id] ?? 0));
            }),
        ]);
    }

    public function store(Request $request, string $business)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tax_id' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_postal_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address_country' => ['sometimes', 'nullable', 'string', 'size:2'],
        ]);

        $tenantId = $this->tenantId();

        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'is_admin' => false,
            'role' => 'customer',
            'role_id' => null,
            'status' => 'active',

            'name' => trim((string) $validated['name']),
            'email' => trim((string) $validated['email']),
            'phone' => array_key_exists('phone', $validated) && is_string($validated['phone']) ? trim((string) $validated['phone']) : null,
            'company' => array_key_exists('company', $validated) && is_string($validated['company']) ? trim((string) $validated['company']) : null,
            'tax_id' => array_key_exists('tax_id', $validated) && is_string($validated['tax_id']) ? trim((string) $validated['tax_id']) : null,
            'address_line1' => array_key_exists('address_line1', $validated) && is_string($validated['address_line1']) ? trim((string) $validated['address_line1']) : null,
            'address_line2' => array_key_exists('address_line2', $validated) && is_string($validated['address_line2']) ? trim((string) $validated['address_line2']) : null,
            'address_city' => array_key_exists('address_city', $validated) && is_string($validated['address_city']) ? trim((string) $validated['address_city']) : null,
            'address_state' => array_key_exists('address_state', $validated) && is_string($validated['address_state']) ? trim((string) $validated['address_state']) : null,
            'address_postal_code' => array_key_exists('address_postal_code', $validated) && is_string($validated['address_postal_code']) ? trim((string) $validated['address_postal_code']) : null,
            'address_country' => array_key_exists('address_country', $validated) && is_string($validated['address_country']) ? strtoupper(trim((string) $validated['address_country'])) : null,

            'password' => Hash::make(Str::random(48)),
            'email_verified_at' => null,
        ]);

        return response()->json([
            'client' => $this->serializeClient($user, 0),
        ], 201);
    }

    public function update(Request $request, string $business, $clientId)
    {
        if (! is_numeric($clientId)) {
            return response()->json([
                'message' => 'Client not found.',
            ], 404);
        }

        $client = User::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_admin', false)
            ->whereKey((int) $clientId)
            ->first();

        if (! $client) {
            return response()->json([
                'message' => 'Client not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($client->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:64'],
            'company' => ['sometimes', 'nullable', 'string', 'max:255'],
            'tax_id' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address_line1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_postal_code' => ['sometimes', 'nullable', 'string', 'max:64'],
            'address_country' => ['sometimes', 'nullable', 'string', 'size:2'],
        ]);

        $client->forceFill([
            'role' => 'customer',
            'role_id' => null,
            'is_admin' => false,
            'status' => $client->status ?: 'active',

            'name' => trim((string) $validated['name']),
            'email' => trim((string) $validated['email']),
            'phone' => array_key_exists('phone', $validated) && is_string($validated['phone']) ? trim((string) $validated['phone']) : null,
            'company' => array_key_exists('company', $validated) && is_string($validated['company']) ? trim((string) $validated['company']) : null,
            'tax_id' => array_key_exists('tax_id', $validated) && is_string($validated['tax_id']) ? trim((string) $validated['tax_id']) : null,
            'address_line1' => array_key_exists('address_line1', $validated) && is_string($validated['address_line1']) ? trim((string) $validated['address_line1']) : null,
            'address_line2' => array_key_exists('address_line2', $validated) && is_string($validated['address_line2']) ? trim((string) $validated['address_line2']) : null,
            'address_city' => array_key_exists('address_city', $validated) && is_string($validated['address_city']) ? trim((string) $validated['address_city']) : null,
            'address_state' => array_key_exists('address_state', $validated) && is_string($validated['address_state']) ? trim((string) $validated['address_state']) : null,
            'address_postal_code' => array_key_exists('address_postal_code', $validated) && is_string($validated['address_postal_code']) ? trim((string) $validated['address_postal_code']) : null,
            'address_country' => array_key_exists('address_country', $validated) && is_string($validated['address_country']) ? strtoupper(trim((string) $validated['address_country'])) : null,
        ])->save();

        $jobsCount = (int) DB::table('rb_jobs')
            ->where('tenant_id', $this->tenantId())
            ->where('branch_id', $this->branchId())
            ->where('customer_id', $client->id)
            ->count();

        return response()->json([
            'client' => $this->serializeClient($client->fresh(), $jobsCount),
        ]);
    }

    public function destroy(Request $request, string $business, $clientId)
    {
        if (! is_numeric($clientId)) {
            return response()->json([
                'message' => 'Client not found.',
            ], 404);
        }

        $client = User::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_admin', false)
            ->whereKey((int) $clientId)
            ->first();

        if (! $client) {
            return response()->json([
                'message' => 'Client not found.',
            ], 404);
        }

        $jobsCount = (int) DB::table('rb_jobs')
            ->where('tenant_id', $this->tenantId())
            ->where('customer_id', $client->id)
            ->count();

        if ($jobsCount > 0) {
            return response()->json([
                'message' => 'Client cannot be deleted because they have jobs.',
            ], 422);
        }

        $client->delete();

        return response()->json([
            'message' => 'Client deleted.',
        ]);
    }

    public function show(Request $request, string $business, $clientId)
    {
        if (! is_numeric($clientId)) {
            return response()->json([
                'message' => 'Client not found.',
            ], 404);
        }

        $client = User::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_admin', false)
            ->whereKey((int) $clientId)
            ->first();

        if (! $client) {
            return response()->json([
                'message' => 'Client not found.',
            ], 404);
        }

        $jobsCount = (int) DB::table('rb_jobs')
            ->where('tenant_id', $this->tenantId())
            ->where('branch_id', $this->branchId())
            ->where('customer_id', $client->id)
            ->count();

        return response()->json([
            'client' => $this->serializeClient($client, $jobsCount),
        ]);
    }

    public function jobs(Request $request, string $business, $clientId)
    {
        if (! is_numeric($clientId)) {
            return response()->json([
                'message' => 'Client not found.',
            ], 404);
        }

        $client = User::query()
            ->where('tenant_id', $this->tenantId())
            ->where('is_admin', false)
            ->whereKey((int) $clientId)
            ->first();

        if (! $client) {
            return response()->json([
                'message' => 'Client not found.',
            ], 404);
        }

        $jobs = RepairBuddyJob::query()
            ->where('customer_id', $client->id)
            ->orderBy('id', 'desc')
            ->limit(200)
            ->get();

        return response()->json([
            'jobs' => $jobs->map(function (RepairBuddyJob $job) {
                return [
                    'id' => $job->id,
                    'case_number' => $job->case_number,
                    'title' => $job->title,
                    'status' => $job->status_slug,
                    'updated_at' => $job->updated_at,
                ];
            }),
        ]);
    }

    private function serializeClient(User $client, int $jobsCount): array
    {
        return [
            'id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'phone' => $client->phone,
            'company' => $client->company,
            'tax_id' => $client->tax_id,
            'address_line1' => $client->address_line1,
            'address_line2' => $client->address_line2,
            'address_city' => $client->address_city,
            'address_state' => $client->address_state,
            'address_postal_code' => $client->address_postal_code,
            'address_country' => $client->address_country,
            'created_at' => $client->created_at,
            'jobs_count' => $jobsCount,
        ];
    }
}
