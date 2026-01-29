<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\RepairBuddyCustomerDevice;
use App\Models\RepairBuddyJob;
use App\Models\RepairBuddyJobDevice;
use Illuminate\Http\Request;

class RepairBuddyJobDeviceController extends Controller
{
    public function index(Request $request, string $business, $jobId)
    {
        if (! is_numeric($jobId)) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $job = RepairBuddyJob::query()->whereKey((int) $jobId)->first();

        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $items = RepairBuddyJobDevice::query()
            ->where('job_id', $job->id)
            ->orderBy('id', 'desc')
            ->limit(200)
            ->get();

        return response()->json([
            'job_devices' => $items->map(function (RepairBuddyJobDevice $jd) {
                return [
                    'id' => $jd->id,
                    'job_id' => $jd->job_id,
                    'customer_device_id' => $jd->customer_device_id,
                    'label' => $jd->label_snapshot,
                    'serial' => $jd->serial_snapshot,
                    'notes' => $jd->notes_snapshot,
                    'created_at' => $jd->created_at,
                ];
            }),
        ]);
    }

    public function store(Request $request, string $business, $jobId)
    {
        if (! is_numeric($jobId)) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $job = RepairBuddyJob::query()->whereKey((int) $jobId)->first();

        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $validated = $request->validate([
            'customer_device_id' => ['required', 'integer'],
        ]);

        $customerDeviceId = (int) $validated['customer_device_id'];

        $cd = RepairBuddyCustomerDevice::query()
            ->whereKey($customerDeviceId)
            ->first();

        if (! $cd) {
            return response()->json([
                'message' => 'Customer device is invalid.',
            ], 422);
        }

        if ((int) $cd->customer_id !== (int) $job->customer_id) {
            return response()->json([
                'message' => 'Customer device does not belong to the job customer.',
            ], 422);
        }

        $existing = RepairBuddyJobDevice::query()
            ->where('job_id', $job->id)
            ->where('customer_device_id', $cd->id)
            ->first();

        if ($existing) {
            return response()->json([
                'job_device' => [
                    'id' => $existing->id,
                    'job_id' => $existing->job_id,
                    'customer_device_id' => $existing->customer_device_id,
                    'label' => $existing->label_snapshot,
                    'serial' => $existing->serial_snapshot,
                    'notes' => $existing->notes_snapshot,
                    'created_at' => $existing->created_at,
                ],
            ], 200);
        }

        $jd = RepairBuddyJobDevice::query()->create([
            'job_id' => $job->id,
            'customer_device_id' => $cd->id,
            'label_snapshot' => $cd->label,
            'serial_snapshot' => $cd->serial,
            'pin_snapshot' => $cd->pin,
            'notes_snapshot' => $cd->notes,
        ]);

        return response()->json([
            'job_device' => [
                'id' => $jd->id,
                'job_id' => $jd->job_id,
                'customer_device_id' => $jd->customer_device_id,
                'label' => $jd->label_snapshot,
                'serial' => $jd->serial_snapshot,
                'notes' => $jd->notes_snapshot,
                'created_at' => $jd->created_at,
            ],
        ], 201);
    }

    public function destroy(Request $request, string $business, $jobId, $jobDeviceId)
    {
        if (! is_numeric($jobId) || ! is_numeric($jobDeviceId)) {
            return response()->json([
                'message' => 'Job device not found.',
            ], 404);
        }

        $job = RepairBuddyJob::query()->whereKey((int) $jobId)->first();

        if (! $job) {
            return response()->json([
                'message' => 'Job not found.',
            ], 404);
        }

        $jd = RepairBuddyJobDevice::query()
            ->whereKey((int) $jobDeviceId)
            ->where('job_id', $job->id)
            ->first();

        if (! $jd) {
            return response()->json([
                'message' => 'Job device not found.',
            ], 404);
        }

        $jd->delete();

        return response()->json([
            'message' => 'Deleted.',
        ]);
    }
}
