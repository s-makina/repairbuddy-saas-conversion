<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    public function index(Request $request, string $business)
    {
        $validated = $request->validate([
            'query' => ['sometimes', 'nullable', 'string', 'max:255'],
            'limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $q = is_string($validated['query'] ?? null) ? trim((string) $validated['query']) : '';
        $limit = is_int($validated['limit'] ?? null) ? (int) $validated['limit'] : 100;

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

        $clients = $query->limit($limit)->get();

        $counts = DB::table('rb_jobs')
            ->select(['customer_id', DB::raw('count(*) as jobs_count')])
            ->where('tenant_id', $tenantId)
            ->where('branch_id', $branchId)
            ->whereIn('customer_id', $clients->pluck('id')->all())
            ->groupBy('customer_id')
            ->pluck('jobs_count', 'customer_id')
            ->all();

        return response()->json([
            'clients' => $clients->map(function (User $u) use ($counts) {
                return $this->serializeClient($u, (int) ($counts[$u->id] ?? 0));
            }),
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
